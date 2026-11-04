<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $todayRevenue = Order::whereDate('created_at', Carbon::today())->sum('total_amount');
        $totalRevenue = Order::sum('total_amount');
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();
        
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('status', 'ACTIVE')
            ->get();

        return view('dashboard', compact('todayRevenue', 'totalRevenue', 'todayOrders', 'lowStockProducts'));
    }

    public function generateAiInsights()
    {
        $currentMonth = date('F');
        
        $deadStock = \App\Models\Product::where('updated_at', '<', now()->subDays(30))
            ->where('status', 'ACTIVE')
            ->pluck('name')
            ->toArray();

        $prompt = "You are an expert AI business advisor for a hardware store in Malaysia. 
        It is currently $currentMonth. 
        Here is the store's dead stock that hasn't sold in 30 days: " . implode(', ', $deadStock) . ".
        
        Provide exactly 3 short, punchy paragraphs of advice:
        1. Weather/Seasonal: Predict what contractors will need to buy this month based on Malaysian weather (e.g., monsoon season preparations) so the owner can restock.
        2. Contractor Lifecycle: Name a common construction phase, and list the sequence of materials they will need to buy next week.
        3. Capital Optimization: Suggest one clever way to bundle the dead stock with high-demand items to clear inventory.
        
        Do not use markdown, just plain text paragraphs.";

        $apiKey = trim(env('GEMINI_API_KEY'));
        
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            return response()->json(['status' => 'error', 'message' => 'API Key is missing in .env file.']);
        }

        try {
            // Hardcoded to the specific, stable 2.5 version to bypass overloaded 'latest' alias routers
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey;

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(10) // 10-second timeout so the server doesn't hang
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]);

            if ($response->successful()) {
                $insight = $response->json('candidates.0.content.parts.0.text');
                return response()->json(['status' => 'success', 'insight' => $insight]);
            }
            
            // Graceful fallback for 503 Server Overload
            if ($response->status() === 503) {
                return response()->json(['status' => 'error', 'message' => 'Google AI servers are currently at maximum capacity. Predictive analytics will resume shortly.']);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Gemini API Error: ' . $response->body()]);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'AI Service Offline. Please check your internet connection.']);
        }
    }
}