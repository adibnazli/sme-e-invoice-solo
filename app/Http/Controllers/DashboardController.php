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

    public function generateAiInsights(Request $request)
    {
        try {
            $apiKey = env('GROQ_API_KEY');
            
            if (empty($apiKey)) {
                return response()->json(['status' => 'error', 'message' => 'Missing Groq API Key'], 500);
            }

            $deadStock = \App\Models\Product::where('updated_at', '<', now()->subMonths(6))
                ->where('stock_quantity', '>', 0)
                ->get(['name', 'sku', 'stock_quantity', 'retail_selling_price']);

            $recentOrders = \App\Models\Order::with('items.product')
                ->latest()
                ->take(15)
                ->get();

            $currentMonth = now()->format('F');

            $promptData = json_encode([
                'current_month' => $currentMonth,
                'location' => 'Malaysia',
                'dead_stock_inventory' => $deadStock,
                'recent_contractor_orders' => $recentOrders
            ]);

            $endpoint = "https://api.groq.com/openai/v1/chat/completions";

            $payload = json_encode([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a predictive AI for a Malaysian hardware store. Return exactly three short sections in clean HTML format (using <h3> and <ul>, no markdown syntax): 1. Weather & Seasonal Forecasting (based on the current month), 2. Contractor Lifecycle Prediction (analyze the recent orders to predict what they need next), 3. Capital Optimization (suggest bundles to clear the dead stock provided).'],
                    ['role' => 'user', 'content' => $promptData]
                ],
                'temperature' => 0.4,
                'max_tokens' => 1024
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
            
            curl_setopt($ch, CURLOPT_TIMEOUT, 45);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
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
                return response()->json(['status' => 'error', 'message' => $curlError], 500);
            }

            $data = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json([
                    'status' => 'success',
                    'insights' => $data['choices'][0]['message']['content'] ?? 'No insights generated.'
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $data['error']['message'] ?? json_encode($data)], 500);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}