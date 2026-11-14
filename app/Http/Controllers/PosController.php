<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 'ACTIVE')->get();$customers = Customer::orderBy('name', 'asc')->get();
        return view('pos.index', compact('products', 'customers'));
    }

    public function parseAiOrder(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string|max:2000'
        ]);

        $groqApiKey = trim(env('GROQ_API_KEY'));
        if (empty($groqApiKey)) {
            return response()->json(['status' => 'error', 'message' => 'Missing GROQ_API_KEY in .env'], 500);
        }

        $catalog = Product::where('status', 'ACTIVE')->get([
            'id', 'sku', 'name', 'unit_type', 'bulk_unit_type', 'bulk_conversion_rate'
        ])->toArray();

        $systemPrompt = "You are an order parser for a Malaysian hardware store. Match the user's messy message against this exact product catalog JSON: " . json_encode($catalog) . ". "
            . "Return ONLY a valid JSON array of matched items with keys: "
            . "\"product_id\" (integer), \"qty\" (integer), \"sale_mode\" (string: \"BASE\" or \"BULK\" - use BULK only if the user explicitly asks for the bulk_unit_type and the product has one). "
            . "Do not include markdown fences or extra text.";

        $groqPayload = json_encode([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $request->raw_text]
            ],
            'temperature' => 0.1,
            'max_tokens' => 500
        ]);

        $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$groqPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $groqApiKey,
            'User-Agent: SME-Hardware-Hub/1.0',
            'Connection: close'
        ]);
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
            return response()->json(['status' => 'error', 'message' => 'Network Error: ' . $curlError], 500);
        }

        if ($httpCode >= 200 && $httpCode < 300) {$data = json_decode($response, true);$rawContent = $data['choices'][0]['message']['content'] ?? '[]';$cleanJson = trim(str_replace(['```json', '```'], '', $rawContent));
            $parsedItems = json_decode($cleanJson, true);

            if (!is_array($parsedItems)) {
                return response()->json(['status' => 'error', 'message' => 'AI returned unreadable format: ' . $cleanJson], 422);
            }

            return response()->json([
                'status' => 'success',
                'items' => $parsedItems
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Groq API Error (' . $httpCode . ')'], 500);
    }

    public function checkout(Request $request)
    {
        $cart = json_decode($request->input('cart_data'), true);
        $customerId =$request->input('customer_id');
        $paymentStatus =$request->input('payment_status', 'PAID');
        $paymentMethod =$request->input('payment_method', 'CASH');
        $deliveryType =$request->input('delivery_type', 'COUNTER_PICKUP');
        $deliveryAddress =$request->input('delivery_address');
        $invoiceType =$request->input('invoice_type', 'STANDARD_RECEIPT');
        $rawAiPrompt =$request->input('raw_ai_prompt');

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty.');
        }

        if ($deliveryType === 'SITE_DELIVERY' && empty(trim($deliveryAddress))) {
            return back()->with('error', 'Site Delivery requires a valid construction site address for the Lorry DO.');
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $invoiceItemsText = "";
            $customer = null;

            if (!empty($customerId)) {
                $customer = Customer::lockForUpdate()->findOrFail($customerId);
            }

            if ($paymentStatus === 'CREDIT_HUTANG' && !$customer) {
                throw new \Exception("Credit (Hutang) checkout requires selecting a registered Contractor account.");
            }

            $lhdnStatus =$invoiceType === 'LHDN_EINVOICE' ? 'VALIDATED' : 'PENDING_CONSOLIDATION';
            $lhdnUin =$invoiceType === 'LHDN_EINVOICE' ? 'UIN-MY-' . strtoupper(uniqid()) : null;

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_id' => $customer ? $customer->id : null,
                'total_amount' => 0,
                'payment_method' => $paymentStatus === 'CREDIT_HUTANG' ? 'CREDIT_TERM' :$paymentMethod,
                'payment_status' => $paymentStatus,
                'delivery_type' => $deliveryType,
                'delivery_address' => $deliveryType === 'SITE_DELIVERY' ?$deliveryAddress : null,
                'invoice_type' => $invoiceType,
                'lhdn_status' => $lhdnStatus,
                'lhdn_uin' => $lhdnUin,
                'raw_ai_prompt' => $rawAiPrompt
            ]);

            foreach ($cart as$item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                $saleMode =$item['sale_mode'] ?? 'BASE';
                $qty = (int)$item['qty'];
                $negotiatedPrice = (float)$item['price'];

                if ($qty < 1) {
                    throw new \Exception("Invalid quantity for {$product->name}.");
                }

                if ($saleMode === 'BULK' && $product->bulk_unit_type &&$product->bulk_conversion_rate > 1) {
                    $unitSold =$product->bulk_unit_type;
                    $conversionRate = (int)$product->bulk_conversion_rate;
                    $rawStockDeduction = $qty * $conversionRate;
                    $unitCostSnapshot = round($product->supplier_cost_price * $conversionRate, 2);
                    $minSafePrice = round($unitCostSnapshot * 1.08, 2);
                } else {
                    $unitSold =$product->unit_type;
                    $rawStockDeduction =$qty;
                    $unitCostSnapshot = (float)$product->supplier_cost_price;
                    $minSafePrice = (float)$product->minimum_safe_price;
                }

                if ($product->stock_quantity <$rawStockDeduction) {
                    throw new \Exception("Insufficient stock for {$product->name}. Needed: {$rawStockDeduction} {$product->unit_type}, Available: {$product->stock_quantity} {$product->unit_type}.");
                }

                if ($negotiatedPrice <$minSafePrice) {
                    throw new \Exception("Margin Guardrail Triggered: {$product->name} ({$unitSold}) cannot be sold below RM " . number_format($minSafePrice, 2) . " (Cost + 8%).");
                }

                $subtotal = round($negotiatedPrice * $qty, 2);
                $totalAmount +=$subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_sold' => $unitSold,
                    'recorded_cost_price' => $unitCostSnapshot,
                    'unit_price' => $negotiatedPrice,
                    'subtotal' => $subtotal
                ]);

                $product->decrement('stock_quantity',$rawStockDeduction);

                $invoiceItemsText .= "- {$product->name} ({$qty} {$unitSold}) @ RM" . number_format($negotiatedPrice, 2) . " = RM" . number_format($subtotal, 2) . "\n";
            }

            if ($paymentStatus === 'CREDIT_HUTANG') {$projectedDebt = $customer->current_debt +$totalAmount;
                if ($projectedDebt >$customer->credit_limit) {
                    throw new \Exception("HUTANG GATEKEEPER BLOCKED: Order total (RM " . number_format($totalAmount, 2) . ") pushes {$customer->name}'s debt to RM " . number_format($projectedDebt, 2) . ", exceeding their RM " . number_format($customer->credit_limit, 2) . " credit limit!");
                }
                $customer->increment('current_debt',$totalAmount);
            } elseif ($customer) {
                $pointsEarned = floor($totalAmount / 10);
                $customer->increment('loyalty_points',$pointsEarned);
            }

            $order->update(['total_amount' =>$totalAmount]);

            DB::commit();

            $customerName =$customer ? $customer->name : 'Walk-in Customer';$rawInvoiceData = "Order ID: #" . str_pad($order->id, 6, '0', STR_PAD_LEFT) . "\n" .
                              "Cashier: " . Auth::user()->name . "\n" .
                              "Customer: " . $customerName . "\n" .
                              "Payment Status: " . $paymentStatus . " (" . $order->payment_method . ")\n" .
                              "Fulfillment: " . $deliveryType . ($deliveryType === 'SITE_DELIVERY' ? " to {$deliveryAddress}" : "") . "\n" .
                              "LHDN Status: " . $lhdnStatus . ($lhdnUin ? " ({$lhdnUin})" : "") . "\n" .
                              "Items:\n" . $invoiceItemsText .
                              "Total Amount: RM" . number_format($totalAmount, 2);

            $groqApiKey = env('GROQ_API_KEY');
            $telegramToken = env('TELEGRAM_BOT_TOKEN');$telegramChatId = env('TELEGRAM_CHAT_ID');

            if (!empty($groqApiKey) && !empty($telegramToken) && !empty($telegramChatId)) {$groqEndpoint = "https://api.groq.com/openai/v1/chat/completions";
                $groqPayload = json_encode([
                    'model' => 'openai/gpt-oss-20b',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an AI for a Malaysian hardware store. Format the provided order data into a clean, professional, and concise text-based E-Invoice for a Telegram message. Highlight if it is CREDIT_HUTANG or SITE_DELIVERY. Use Markdown formatting. Do not add conversational filler.'],
                        ['role' => 'user', 'content' => $rawInvoiceData]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500
                ]);

                $chGroq = curl_init($groqEndpoint);
                curl_setopt($chGroq, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chGroq, CURLOPT_POST, true);
                curl_setopt($chGroq, CURLOPT_POSTFIELDS,$groqPayload);
                curl_setopt($chGroq, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $groqApiKey,
                    'User-Agent: SME-Hardware-Hub/1.0',
                    'Connection: close'
                ]);
                curl_setopt($chGroq, CURLOPT_TIMEOUT, 30);
                curl_setopt($chGroq, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($chGroq, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($chGroq, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($chGroq, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($chGroq, CURLOPT_FORBID_REUSE, true);
                curl_setopt($chGroq, CURLOPT_FRESH_CONNECT, true);

                $groqResponse = curl_exec($chGroq);
                $groqHttpCode = curl_getinfo($chGroq, CURLINFO_HTTP_CODE);
                curl_close($chGroq);

                $parsedInvoiceText = "🧾 *SME Hardware Hub - E-Invoice*\n\n";

                if ($groqHttpCode >= 200 && $groqHttpCode < 300) {$groqData = json_decode($groqResponse, true);$parsedInvoiceText .= $groqData['choices'][0]['message']['content'] ?? $rawInvoiceData;
                } else {
                    $parsedInvoiceText .=$rawInvoiceData;
                }

                $tgEndpoint = "https://api.telegram.org/bot" . $telegramToken . "/sendMessage";
                $tgPayload = json_encode([
                    'chat_id' => $telegramChatId,
                    'text' => $parsedInvoiceText,
                    'parse_mode' => 'Markdown'
                ]);

                $chTg = curl_init($tgEndpoint);
                curl_setopt($chTg, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chTg, CURLOPT_POST, true);
                curl_setopt($chTg, CURLOPT_POSTFIELDS,$tgPayload);
                curl_setopt($chTg, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Connection: close'
                ]);
                curl_setopt($chTg, CURLOPT_TIMEOUT, 30);
                curl_setopt($chTg, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($chTg, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($chTg, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($chTg, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($chTg, CURLOPT_FORBID_REUSE, true);
                curl_setopt($chTg, CURLOPT_FRESH_CONNECT, true);

                curl_exec($chTg);
                curl_close($chTg);
            }

            return redirect()->route('invoice.show', $order->id)->with('success', 'Order processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}