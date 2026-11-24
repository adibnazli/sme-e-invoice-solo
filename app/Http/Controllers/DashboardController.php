<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

        $recentLogins = DB::table('user_login_logs')
            ->join('users', 'user_login_logs.user_id', '=', 'users.id')
            ->select('user_login_logs.*', 'users.name', 'users.role')
            ->orderBy('user_login_logs.login_at', 'desc')
            ->take(5)
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
            'recentExpenses',
            'recentLogins'
        ));
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'category' => 'required|in:RENT,UTILITIES,WAGES,TRANSPORT_FUEL,MAINTENANCE,OTHER',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        ShopExpense::create([
            'recorded_by' => Auth::id(),
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_date' => $request->input('expense_date', now()->toDateString()),
        ]);

        return redirect()->route('dashboard')->with('success', 'Operating expense recorded and deducted from Net Profit.');
    }

    public function consolidateLhdn()
    {
        $pendingOrders = Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->get();

        if ($pendingOrders->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'No pending walk-in receipts require LHDN consolidation.');
        }

        $batchUin = 'UIN-CONSOL-'.now()->format('Ym').'-'.strtoupper(substr(uniqid(), -5));
        $count = $pendingOrders->count();
        $totalVal = $pendingOrders->sum('total_amount');

        Order::where('lhdn_status', 'PENDING_CONSOLIDATION')->update([
            'lhdn_status' => 'CONSOLIDATED',
            'lhdn_uin' => $batchUin,
        ]);

        return redirect()->route('dashboard')->with(
            'success',
            "LHDN MyInvois Consolidation Complete! {$count} walk-in receipts (RM ".number_format($totalVal, 2).") submitted under Batch UIN: {$batchUin}."
        );
    }

    public function exportCsv()
    {
        $orders = Order::with(['customer', 'user', 'items'])->orderBy('created_at', 'desc')->get();
        $filename = 'SME_Hardware_Accounting_Ledger_'.now()->format('Y_m_d_His').'.csv';

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
                'Gross Profit (RM)',
            ]);

            foreach ($orders as $order) {
                $orderCogs = $order->items->sum(function ($item) {
                    return $item->quantity * $item->recorded_cost_price;
                });
                $gross = $order->total_amount - $orderCogs;

                fputcsv($file, [
                    '#'.str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    $order->created_at->format('Y-m-d H:i'), $order->customer ? $order->customer->name : 'Walk-in Customer', $order->payment_status,
                    $order->payment_method, $order->delivery_type,
                    $order->lhdn_status, $order->lhdn_uin ?? 'N/A',
                    number_format($order->total_amount, 2, '.', ''),
                    number_format($orderCogs, 2, '.', ''),
                    number_format($gross, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function generateAiInsights(Request $request)
    {
        try {
            $apiKey = trim((string) config('services.groq.api_key', ''));

            if (empty($apiKey)) {
                return response()->json(['status' => 'error', 'message' => 'Missing Groq API Key'], 500);
            }

            $activeCatalog = Product::where('status', 'ACTIVE')
                ->get(['name', 'sku', 'stock_quantity', 'low_stock_threshold', 'unit_type', 'retail_selling_price']);

            $deadStock = Product::where('updated_at', '<', now()->subMonths(6))
                ->where('stock_quantity', '>', 0)
                ->get(['name', 'sku', 'stock_quantity', 'retail_selling_price']);

            $recentOrders = Order::with(['customer:id,name,customer_type', 'items.product:id,name,unit_type'])
                ->latest()
                ->take(12)
                ->get()
                ->map(function ($order) {
                    return [
                        'customer' => $order->customer ? $order->customer->name : 'Walk-in',
                        'delivery_type' => $order->delivery_type,
                        'payment_status' => $order->payment_status,
                        'items' => $order->items->map(function ($i) {
                            return ($i->product->name ?? 'Item').' ('.$i->quantity.' '.$i->unit_sold.')';
                        })->values(),
                    ];
                });

            $promptData = json_encode([
                'current_month' => now()->format('F Y'),
                'location' => 'Senawang, Negeri Sembilan, Malaysia',
                'active_inventory' => $activeCatalog,
                'dead_stock_inventory' => $deadStock,
                'recent_contractor_orders' => $recentOrders,
            ]);

            $systemPrompt = "
You are an elite B2B hardware store inventory & contractor analyst in Senawang, Negeri Sembilan, Malaysia.
Analyze the store's live inventory, recent contractor orders, and dead stock, then output ONLY clean HTML using this exact 3-card structure (do NOT wrap in ```html or markdown code blocks):

<div class='d-flex justify-content-between align-items-center mb-3'>
    <div class='d-flex align-items-center gap-2'>
        <span class='badge rounded-pill px-3 py-1 fw-bold' style='background:#E0F2FE;color:#0284C7;'>LIVE GROQ INTELLIGENCE</span>
        <small class='text-muted'>Generated from active Senawang inventory & contractor order ledger</small>
    </div>
</div>
<div class='ai-insight-grid'>
    <div class='ai-mini-card'>
        <div class='ai-mini-header'>
            <div class='ai-mini-icon' style='background:#E0F2FE;color:#0284C7;'><i class='bi bi-cloud-rain-heavy-fill'></i></div>
            <div>
                <div class='fw-bold small text-dark'>Monsoon & Site Readiness</div>
                <small class='text-muted' style='font-size:0.7rem;'>Seasonal Stock Protection</small>
            </div>
        </div>
        <ul>
            <li>[Point 1: Specific Malaysian Northeast Monsoon (Monsun Timur Laut) storage action for moisture-sensitive stock in the catalog]</li>
            <li>[Point 2: Weather-driven contractor demand shift based on actual catalog items]</li>
            <li>[Point 3: Lorry DO site delivery advice for wet construction sites]</li>
        </ul>
    </div>

    <div class='ai-mini-card'>
        <div class='ai-mini-header'>
            <div class='ai-mini-icon' style='background:#DBEAFE;color:#1D4ED8;'><i class='bi bi-diagram-3-fill'></i></div>
            <div>
                <div class='fw-bold small text-dark'>Contractor Next-Phase Forecast</div>
                <small class='text-muted' style='font-size:0.7rem;'>Based on Recent Order Items</small>
            </div>
        </div>
        <ul>
            <li>[Point 1: Identify current project work from recent_contractor_orders using exact product names]</li>
            <li>[Point 2: Predict which exact catalog items these contractors will need next]</li>
            <li>[Point 3: Highlight any low-stock or fast-moving items that need GRN restocking]</li>
        </ul>
    </div>

    <div class='ai-mini-card'>
        <div class='ai-mini-header'>
            <div class='ai-mini-icon' style='background:#DCFCE7;color:#15803D;'><i class='bi bi-box2-heart-fill'></i></div>
            <div>
                <div class='fw-bold small text-dark'>Capital & Bundle Strategy</div>
                <small class='text-muted' style='font-size:0.7rem;'>Margin & Turnover Optimization</small>
            </div>
        </div>
        <ul>
            <li>[Point 1: Dead stock assessment and slow-moving capital status]</li>
            <li>[Point 2: Propose 1 practical Structural/Plumbing Bundle using exact catalog items with realistic contractor quantities]</li>
            <li>[Point 3: Propose 1 Finishing/Installation Bundle (NEVER include more than 1 hand tool per bundle; focus on consumables)]</li>
        </ul>
    </div>
</div>

STRICT RULES:
- NEVER mention generic weather trivia like temperature °C or humidity %. Focus 100% on hardware store operations.
- NEVER bundle multiple durable hand tools (like 3x pliers) in one kit. A kit may contain at most 1 hand tool paired with consumables.
- Bold key product names and quantities using <strong> tags.
";

            $endpoint = 'https://'.'api.groq.com/openai/v1/chat/completions';

            $payload = json_encode([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $promptData],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2048,
            ]);

            $ch = curl_init($endpoint);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$apiKey,
                'User-Agent: SME-Hardware-Hub/1.0',
                'Accept: application/json',
                'Connection: close',
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
                $rawHtml = $data['choices'][0]['message']['content'] ?? 'No insights generated.';
                $cleanHtml = preg_replace('/```(?:html)?\s*/i', '', $rawHtml);
                $cleanHtml = trim(str_replace('```', '', $cleanHtml));

                $scopedStyles = "
                <style>
                    .ai-insight-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
                    .ai-mini-card { background-color: #F8FAFC; border: 1px solid #EEF2F6; border-radius: 16px; padding: 1.15rem; height: 100%; }
                    .ai-mini-header { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.85rem; padding-bottom: 0.65rem; border-bottom: 1px solid #E2E8F0; }
                    .ai-mini-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
                    .ai-mini-card ul { list-style: none; padding-left: 0; margin-bottom: 0; }
                    .ai-mini-card ul li { position: relative; padding-left: 1.1rem; margin-bottom: 0.55rem; font-size: 0.82rem; color: #334155; line-height: 1.5; }
                    .ai-mini-card ul li:last-child { margin-bottom: 0; }
                    .ai-mini-card ul li::before { content: '•'; position: absolute; left: 0; top: 0; color: #2563EB; font-weight: 800; }
                </style>";

                return response()->json([
                    'status' => 'success',
                    'insights' => $scopedStyles.$cleanHtml,
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $data['error']['message'] ?? json_encode($data)], 500);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
