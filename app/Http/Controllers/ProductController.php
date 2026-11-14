<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            return redirect('/pos')->with('error', 'Unauthorized access.');
        }

        $products = Product::where('status', 'ACTIVE')
                           ->orderBy('stock_quantity', 'asc')
                           ->get();

        return view('products.index', compact('products'));
    }

    public function archive(Product $product)
    {
        $product->update(['status' => 'ARCHIVED']);
        return redirect()->route('products.index')->with('success', 'Product archived.');
    }

    public function create()
    {
        return view('products.create');
    }

    public function scan(Request $request)
    {
        $request->validate([
            'hardware_image' => 'required|image|max:4096',
        ]);

        $imagePath = $request->file('hardware_image')->getPathname();
        $base64Image = base64_encode(file_get_contents($imagePath));
        $mimeType = $request->file('hardware_image')->getMimeType();

        $apiKey = trim(env('GEMINI_API_KEY'));
        
        if (empty($apiKey)) {
            return back()->with('error', 'System Error: Gemini API Key is missing.');
        }

        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'You are a hardware store assistant. Analyze this image and return a JSON object with these exact keys: "name" (string), "category" (string), "sku" (string, generate a short logical SKU like HW-XXX), "unit_type" (string, choose from PIECE, BAG, KG, METER, BOX, TIN, ROLL, SET). Output ONLY raw JSON, no markdown formatting.'],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withoutVerifying()->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
        
        if ($response->successful()) {
            $rawText = $response->json('candidates.0.content.parts.0.text');
            $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
            $result = json_decode($cleanJson, true);
            
            return back()->with('ai_data', $result)->with('success', 'Image scanned successfully!');
        }

        $diagnostic = Http::withoutVerifying()->get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}");
        $availableModels = collect($diagnostic->json('models'))->pluck('name')->filter(function($name) {
            return str_contains($name, 'gemini');
        })->implode(', ');

        return back()->with('error', 'Google API Error: ' . $response->json('error.message') . ' || AVAILABLE MODELS: ' . $availableModels);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'category' => 'required|string',
            'unit_type' => 'required|in:PIECE,BAG,KG,METER,BOX,TIN,ROLL,SET',
            'bulk_unit_type' => 'nullable|string|max:20',
            'bulk_conversion_rate' => 'nullable|integer|min:2',
            'supplier_cost_price' => 'required|numeric|min:0',
            'retail_selling_price' => 'required|numeric|min:0',
            'bulk_selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
            'lhdn_class_code' => 'required|string|max:10',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'category' => $request->category,
            'unit_type' => $request->unit_type,
            'bulk_unit_type' => $request->filled('bulk_unit_type') ? strtoupper($request->bulk_unit_type) : null,
            'bulk_conversion_rate' => $request->bulk_conversion_rate,
            'supplier_cost_price' => $request->supplier_cost_price,
            'retail_selling_price' => $request->retail_selling_price,
            'bulk_selling_price' => $request->bulk_selling_price,
            'stock_quantity' => $request->stock_quantity,
            'low_stock_threshold' => $request->low_stock_threshold,
            'lhdn_class_code' => $request->lhdn_class_code,
            'description' => $request->description,
            'image_path' => $imagePath,
            'status' => 'ACTIVE',
        ]);

        return redirect()->route('products.index')->with('success', 'Product added with SME unit & margin protection rules.');
    }

    public function restock(Request $request, Product $product)
    {
        $request->validate([
            'added_quantity' => 'required|integer|min:1',
            'new_supplier_cost' => 'required|numeric|min:0.01',
            'auto_adjust_retail' => 'nullable|boolean'
        ]);

        $oldCost = (float) $product->supplier_cost_price;
        $newCost = (float) $request->new_supplier_cost;
        $currentRetail = (float) $product->retail_selling_price;
        $addedQty = (int) $request->added_quantity;

        $newMarginPercent = (($currentRetail - $newCost) / $newCost) * 100;
        $warningMessage = null;

        if ($newCost > $oldCost && $newMarginPercent < 15) {
            $recommendedRetail = round($newCost * 1.20, 2);

            if ($request->boolean('auto_adjust_retail')) {
                $currentRetail = $recommendedRetail;
                $warningMessage = "Supplier cost rose from RM " . number_format($oldCost, 2) . " to RM " . number_format($newCost, 2) . ". Retail price was automatically bumped to RM " . number_format($recommendedRetail, 2) . " to protect a 20% margin.";
            } else {
                $warningMessage = "MARGIN ALERT: Supplier cost rose to RM " . number_format($newCost, 2) . "! Your current retail price (RM " . number_format($currentRetail, 2) . ") leaves only a " . round($newMarginPercent, 1) . "% margin. Recommended new retail price: RM " . number_format($recommendedRetail, 2) . ".";
            }
        }

        $product->update([
            'stock_quantity' => $product->stock_quantity + $addedQty,
            'supplier_cost_price' => $newCost,
            'retail_selling_price' => $currentRetail
        ]);

        $redirect = redirect()->route('products.index')
            ->with('success', "GRN Restock Complete: +{$addedQty} {$product->unit_type} added to {$product->name}.");

        if ($warningMessage) {
            $redirect->with('margin_warning', $warningMessage);
        }

        return $redirect;
    }
}