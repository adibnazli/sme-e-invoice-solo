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
        /** @var \App\Models\User $user */
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

        // Switch to the stable v1 endpoint
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-3.5-flash:generateContent?key={$apiKey}";

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

        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
        
        if ($response->successful()) {
            $rawText = $response->json('candidates.0.content.parts.0.text');
            
            // Clean up Markdown if Gemini includes it
            $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
            $result = json_decode($cleanJson, true);
            
            return back()->with('ai_data', $result)->with('success', 'Image scanned successfully!');
        }

        // If it fails, interrogate Google for the valid model list
        $diagnostic = Http::get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}");
        $availableModels = collect($diagnostic->json('models'))->pluck('name')->filter(function($name) {
            return str_contains($name, 'gemini');
        })->implode(', ');

        return back()->with('error', 'Google API Error: ' . $response->json('error.message') . ' || AVAILABLE MODELS FOR YOUR KEY: ' . $availableModels);
    }

    public function generateDescription(Request $request)
    {
        $name = $request->query('name');
        $apiKey = env('GEMINI_API_KEY');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey;

        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->post($endpoint, [
                'contents' => [['parts' => [['text' => "Write a concise, 2-sentence professional product description for a hardware store item named: " . $name]]]]
            ]);

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'description' => $response->json('candidates.0.content.parts.0.text')
            ]);
        }

        return response()->json(['status' => 'error'], 500);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'supplier_cost_price' => 'required|numeric|min:0',
            'retail_selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category' => 'required|string',
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
            'supplier_cost_price' => $request->supplier_cost_price,
            'retail_selling_price' => $request->retail_selling_price,
            'stock_quantity' => $request->stock_quantity,
            'category' => $request->category,
            'description' => $request->description,
            'image_path' => $imagePath,
            'status' => 'ACTIVE',
            'unit_type' => 'PIECE',
            'lhdn_class_code' => '022', 
            'low_stock_threshold' => 5,
        ]);

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }
}