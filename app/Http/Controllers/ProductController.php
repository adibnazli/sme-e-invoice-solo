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
        try {
            $name = $request->query('name');
            $apiKey = env('GROQ_API_KEY');
            
            if (empty($apiKey)) {
                return response()->json(['status' => 'error', 'message' => 'Missing Groq API Key in .env'], 500);
            }

            $endpoint = "https://api.groq.com/openai/v1/chat/completions";

            $payload = json_encode([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional retail copywriter for a hardware store. Keep it concise.'],
                    ['role' => 'user', 'content' => "Write a concise, 2-sentence professional product description for a hardware store item named: " . $name]
                ],
                'temperature' => 0.7,
                'max_tokens' => 150
            ]);

            $ch = curl_init($endpoint);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'User-Agent: SME-Hardware-Hub/1.0',
                'Accept: application/json',
                'Connection: close'
            ]);
            
            // Network stability settings for your hotspot / UTeM Wi-Fi
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            curl_close($ch);

            if ($curlError) {
                return response()->json(['status' => 'error', 'message' => 'Network Drop: ' . $curlError], 500);
            }

            $data = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json([
                    'status' => 'success',
                    'description' => $data['choices'][0]['message']['content'] ?? 'No description generated.'
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Groq Error: ' . ($data['error']['message'] ?? json_encode($data))], 500);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'PHP Error: ' . $e->getMessage()], 500);
        }
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