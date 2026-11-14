<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\ShopExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $todayRevenue = Order::whereDate('created_at', Carbon::today())->sum('total_amount');
        $totalRevenue = Order::sum('total_amount');
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();

        $totalCogs = OrderItem::select(DB::raw('SUM(quantity * recorded_cost_price) as total_cogs'))->value('total_cogs') ?? 0;
        $totalExpenses = ShopExpense::sum('amount');
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;

        $totalHutang = Customer::sum('current_debt');
        $debtorsCount = Customer::where('current_debt', '>', 0)->count();

        $pendingLhdnCount = Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->count();
        $pendingLhdnAmount = Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->sum('total_amount');
        $validatedLhdnCount = Order::whereIn('lhdn_status', ['VALIDATED', 'CONSOLIDATED'])->count();

        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('status', 'ACTIVE')
            ->get();

        $recentExpenses = ShopExpense::with('user')
            ->orderBy('expense_date', 'desc')
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard', compact(
            'todayRevenue',
            'totalRevenue',
            'todayOrders',
            'totalCogs',
            'totalExpenses',
            'grossProfit',
            'netProfit',
            'totalHutang',
            'debtorsCount',
            'pendingLhdnCount',
            'pendingLhdnAmount',
            'validatedLhdnCount',
            'lowStockProducts',
            'recentExpenses'
        ));
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'category' => 'required|in:RENT,UTILITIES,WAGES,TRANSPORT_FUEL,MAINTENANCE,OTHER',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255'
        ]);

        ShopExpense::create([
            'recorded_by' => Auth::id(),
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'description' => $request->description
        ]);

        return redirect()->route('dashboard')->with('success', 'Operating expense recorded and deducted from Net Profit.');
    }

    public function consolidateLhdn()
    {
        $pendingOrders = Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->get();

        if ($pendingOrders->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'No pending walk-in receipts require LHDN consolidation.');
        }

        $batchUin = 'UIN-CONSOL-' . now()->format('Ym') . '-' . strtoupper(substr(uniqid(), -5));
        $count = $pendingOrders->count();
        $totalVal = $pendingOrders->sum('total_amount');

        Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->update([
            'lhdn_status' => 'CONSOLIDATED',
            'lhdn_uin' => $batchUin
        ]);

        return redirect()->route('dashboard')->with(
            'success',
            "LHDN MyInvois Consolidation Complete! {$count} walk-in receipts (RM " . number_format($totalVal, 2) . ") submitted under Batch UIN: {$batchUin}."
        );
    }

    public function exportCsv()
    {
        $orders = Order::with(['customer', 'user', 'items'])->orderBy('created_at', 'desc')->get();
        $filename = 'SME_Hardware_Accounting_Ledger_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Order ID',
                'Date',
                'Customer / Contractor',
                'Payment Status',
                'Payment Method',
                'Delivery Mode',
                'LHDN Status',
                'LHDN UIN',
                'Revenue (RM)',
                'Recorded COGS (RM)',
                'Gross Profit (RM)'
            ]);

            foreach ($orders as $order) {
                $orderCogs = $order->items->sum(function ($item) {
                    return $item->quantity * $item->recorded_cost_price;
                });
                $gross = $order->total_amount - $orderCogs;

                fputcsv($file, [
                    '#' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer ? $order->customer->name : 'Walk-in Customer',
                    $order->payment_status,
                    $order->payment_method,
                    $order->delivery_type,
                    $order->lhdn_status,
                    $order->lhdn_uin ?? 'N/A',
                    number_format($order->total_amount, 2, '.', ''),
                    number_format($orderCogs, 2, '.', ''),
                    number_format($gross, 2, '.', '')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function generateAiInsights(Request $request)
    {
        try {
            $apiKey = env('GROQ_API_KEY');
            
            if (empty($apiKey)) {
                return response()->json(['status' => 'error', 'message' => 'Missing Groq API Key'], 500);
            }

            $deadStock = Product::where('updated_at', '<', now()->subMonths(6))
                ->where('stock_quantity', '>', 0)
                ->get(['name', 'sku', 'stock_quantity', 'retail_selling_price']);

            $recentOrders = Order::with('items.product')
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