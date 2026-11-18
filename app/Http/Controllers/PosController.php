<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
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
        ])->toJson();

        $systemPrompt = "
        You are a strict hardware store inventory matcher.
        Analyze the Malaysian contractor message and match items ONLY to the exact product in the provided JSON catalog.

        STRICT MATCHING RULES:
        1. Respond ONLY with a valid JSON object formatted exactly like this:
           { \"status\": \"success\", \"items\": [ { \"product_id\": 1, \"qty\": 10, \"sale_mode\": \"BASE\" } ], \"unmatched\": [\"item name\"] }
        2. EXACT PRODUCT TYPE ONLY: Never substitute a different tool, material, or dimension just because it is in a similar category.
           - Example: 'spanar' (wrench/spanner) is NOT 'pliers' (playar).
           - Example: 'tukul' (hammer) is NOT 'paku' (nails).
           - Example: 'besi Y12' is NOT 'besi Y10'.
           - If the exact product type is not in the catalog, you MUST put it in the \"unmatched\" array and DO NOT add any substitute to \"items\".
        3. Direct Malay-to-English translations are allowed ONLY for the exact same item:
           - simen = cement, bata = brick, besi = steel/rebar, paku = nail, dawai = wire, cat = paint, paip = pipe, gam = glue/solvent, playar = pliers, spanar = wrench/spanner, skru = screw.
        4. Unit translations: beg = bag, btg/batang = piece/rod/length, kotak = box, tong = bucket/tin.
        5. If the user asks for a bulk unit ('kotak', 'pallet', 'tong', 'roll', 'bundle') AND the matched product has a non-null 'bulk_unit_type', set 'sale_mode' to 'BULK'. Otherwise, set 'sale_mode' to 'BASE'.

        Catalog: $catalog
        ";

        $groqPayload = json_encode([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $request->raw_text]
            ],
            'temperature' => 0.0,
            'max_tokens' => 2048,
            'response_format' => ['type' => 'json_object']
        ]);

        $groqUrl = 'https://' . 'api.groq.com/openai/v1/chat/completions';
        $ch = curl_init($groqUrl);
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

        if ($httpCode >= 200 &&$httpCode < 300) {
            $data = json_decode($response, true);
            $rawContent =$data['choices'][0]['message']['content'] ?? '';

            $cleanJson = preg_replace('/<think>.*?<\/think>/is', '', $rawContent);$cleanJson = preg_replace('/```(?:json)?\s*/i', '', $cleanJson);
            $cleanJson = trim($cleanJson);

            $start = strpos($cleanJson, '{');
            $end = strrpos($cleanJson, '}');
            if ($start !== false && $end !== false) {
                $cleanJson = substr($cleanJson, $start, $end - $start + 1);
            }

            $parsedData = json_decode($cleanJson, true);

            if (!is_array($parsedData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AI returned unreadable format: ' . ($rawContent ?: 'Empty response from model')
                ], 422);
            }

            $items = isset($parsedData['items']) && is_array($parsedData['items'])
                ? $parsedData['items']
                : (array_is_list($parsedData) ? $parsedData : []);

            $unmatched = isset($parsedData['unmatched']) && is_array($parsedData['unmatched'])
                ? $parsedData['unmatched']
                : [];

            return response()->json([
                'status' => 'success',
                'items' => $items,
                'unmatched' => $unmatched
            ]);
        }

        $errBody = json_decode($response, true);
        $errMsg = $errBody['error']['message'] ?? ('Groq API Error (' . $httpCode . ')');
        return response()->json(['status' => 'error', 'message' => $errMsg], 500);
    }

    public function checkout(Request $request)
    {
        $cart = json_decode($request->input('cart_data'), true);
        $customerId = $request->input('customer_id');
        $paymentStatus = $request->input('payment_status', 'PAID');
        $paymentMethod = $request->input('payment_method', 'CASH');
        $deliveryType = $request->input('delivery_type', 'COUNTER_PICKUP');
        $deliveryAddress = $request->input('delivery_address');
        $invoiceType = $request->input('invoice_type', 'STANDARD_RECEIPT');
        $rawAiPrompt = $request->input('raw_ai_prompt');
        $customTelegramChatId = trim($request->input('telegram_chat_id', ''));

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
                if (!empty($customTelegramChatId) && $customer->telegram_chat_id !== $customTelegramChatId) {
                    $customer->telegram_chat_id = $customTelegramChatId;
                    $customer->save();
                }
            }

            if ($paymentStatus === 'CREDIT_HUTANG' && !$customer) {
                throw new \Exception("Credit (Hutang) checkout requires selecting a registered Contractor account.");
            }

            $lhdnStatus = $invoiceType === 'LHDN_EINVOICE' ? 'VALIDATED' : 'PENDING_CONSOLIDATION';
            $lhdnUin = $invoiceType === 'LHDN_EINVOICE' ? 'UIN-MY-' . strtoupper(uniqid()) : null;

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_id' => $customer ? $customer->id : null,
                'total_amount' => 0,
                'payment_method' => $paymentStatus === 'CREDIT_HUTANG' ? 'CREDIT_TERM' : $paymentMethod,
                'payment_status' => $paymentStatus,
                'delivery_type' => $deliveryType,
                'delivery_address' => $deliveryType === 'SITE_DELIVERY' ? $deliveryAddress : null,
                'invoice_type' => $invoiceType,
                'lhdn_status' => $lhdnStatus,
                'lhdn_uin' => $lhdnUin,
                'raw_ai_prompt' => $rawAiPrompt
            ]);

            foreach ($cart as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                $saleMode = $item['sale_mode'] ?? 'BASE';
                $qty = (int)$item['qty'];
                $negotiatedPrice = (float)$item['price'];

                if ($qty < 1) {
                    throw new \Exception("Invalid quantity for {$product->name}.");
                }

                if ($saleMode === 'BULK' && $product->bulk_unit_type && $product->bulk_conversion_rate > 1) {
                    $unitSold = $product->bulk_unit_type;
                    $conversionRate = (int)$product->bulk_conversion_rate;
                    $rawStockDeduction = $qty * $conversionRate;
                    $unitCostSnapshot = round($product->supplier_cost_price * $conversionRate, 2);
                    $minSafePrice = round($unitCostSnapshot * 1.08, 2);
                } else {
                    $unitSold = $product->unit_type;
                    $rawStockDeduction = $qty;
                    $unitCostSnapshot = (float)$product->supplier_cost_price;
                    $minSafePrice = (float)$product->minimum_safe_price;
                }

                if ($product->stock_quantity < $rawStockDeduction) {
                    throw new \Exception("Insufficient stock for {$product->name}. Needed: {$rawStockDeduction} {$product->unit_type}, Available: {$product->stock_quantity} {$product->unit_type}.");
                }

                if ($negotiatedPrice < $minSafePrice) {
                    throw new \Exception("Margin Guardrail Triggered: {$product->name} ({$unitSold}) cannot be sold below RM " . number_format($minSafePrice, 2) . " (Cost + 8%).");
                }

                $subtotal = round($negotiatedPrice * $qty, 2);
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_sold' => $unitSold,
                    'recorded_cost_price' => $unitCostSnapshot,
                    'unit_price' => $negotiatedPrice,
                    'subtotal' => $subtotal
                ]);

                $product->decrement('stock_quantity', $rawStockDeduction);

                $invoiceItemsText .= "- {$product->name} ({$qty} {$unitSold}) @ RM" . number_format($negotiatedPrice, 2) . " = RM" . number_format($subtotal, 2) . "\n";
            }

            if ($paymentStatus === 'CREDIT_HUTANG') {
                $projectedDebt = $customer->current_debt + $totalAmount;
                if ($projectedDebt > $customer->credit_limit) {
                    throw new \Exception("HUTANG GATEKEEPER BLOCKED: Order total (RM " . number_format($totalAmount, 2) . ") pushes {$customer->name}'s debt to RM " . number_format($projectedDebt, 2) . ", exceeding their RM " . number_format($customer->credit_limit, 2) . " credit limit!");
                }
                $customer->increment('current_debt', $totalAmount);
            } elseif ($customer) {
                $pointsEarned = floor($totalAmount / 10);
                $customer->increment('loyalty_points', $pointsEarned);
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            $targetChatId = $customTelegramChatId
                ?: ($customer && !empty($customer->telegram_chat_id) ? $customer->telegram_chat_id : trim(env('TELEGRAM_CHAT_ID', '')));

            $order->load(['customer', 'user', 'items.product']);
            $tgSent = $this->dispatchTelegramInvoice($order, $customer, $invoiceItemsText, $targetChatId);

            $successMsg = $tgSent
                ? "Order processed and PDF E-Invoice dispatched to Telegram (Chat ID: {$targetChatId})!"
                : "Order processed successfully!";

            return redirect()->route('invoice.show', $order->id)->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function sendTelegram(Request $request, $id)
    {
        $order = Order::with(['customer', 'user', 'items.product'])->findOrFail($id);
        $chatId = trim($request->input('telegram_chat_id', ''))
            ?: ($order->customer && !empty($order->customer->telegram_chat_id) ? $order->customer->telegram_chat_id : trim(env('TELEGRAM_CHAT_ID', '')));

        if (empty($chatId)) {
            return back()->with('error', 'Please enter a valid Customer Telegram Chat ID.');
        }

        if ($order->customer && $order->customer->telegram_chat_id !== $chatId) {
            $order->customer->update(['telegram_chat_id' => $chatId]);
        }

        $invoiceItemsText = "";
        foreach ($order->items as $item) {
            $prodName = $item->product->name ?? 'Hardware Item';
            $invoiceItemsText .= "- {$prodName} ({$item->quantity} {$item->unit_sold}) @ RM" . number_format($item->unit_price, 2) . " = RM" . number_format($item->subtotal, 2) . "\n";
        }

        $sent = $this->dispatchTelegramInvoice($order, $order->customer, $invoiceItemsText, $chatId);

        if ($sent) {
            return back()->with('success', "PDF E-Invoice sent to customer's Telegram (Chat ID: {$chatId})!");
        }

        return back()->with('error', 'Failed to send Telegram PDF E-Invoice. Check your TELEGRAM_BOT_TOKEN and ensure the customer has started the bot.');
    }

    private function dispatchTelegramInvoice($order, $customer, $invoiceItemsText, $targetChatId)
    {
        $telegramToken = trim(env('TELEGRAM_BOT_TOKEN', ''));

        if (empty($telegramToken) || empty($targetChatId)) {
            return false;
        }

        $invoiceNo = str_pad($order->id, 6, '0', STR_PAD_LEFT);
        $customerName = $customer ? $customer->name : 'Walk-in Customer';
        $paymentLabel = $order->payment_status === 'CREDIT_HUTANG' ? 'CREDIT (HUTANG)' : 'PAID (' . $order->payment_method . ')';

        $caption = "🧾 SME Hardware Hub - Official PDF E-Invoice\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "📌 Invoice: #{$invoiceNo}\n"
                 . "👤 Customer: {$customerName}\n"
                 . "💳 Status: {$paymentLabel}\n"
                 . "🚚 Fulfillment: {$order->delivery_type}\n"
                 . "💰 Grand Total: RM " . number_format($order->total_amount, 2) . "\n\n"
                 . "📄 Please find your official PDF E-Invoice attached.";

        try {
            // 1. Render PDF from Blade view and save to temporary file
            $pdf = Pdf::loadView('invoices.pdf', compact('order'))->setPaper('a4', 'portrait');
            $fileName = "E-Invoice_{$invoiceNo}.pdf";
            $tempFilePath = storage_path("app/{$fileName}");
            file_put_contents($tempFilePath, $pdf->output());

            // 2. Send PDF Document to Telegram via /sendDocument
            $tgDocEndpoint = 'https://' . 'api.telegram.org/bot' . $telegramToken . '/sendDocument';
            $postFields = [
                'chat_id' => $targetChatId,
                'document' => new \CURLFile($tempFilePath, 'application/pdf', $fileName),
                'caption' => $caption
            ];

            $chTg = curl_init($tgDocEndpoint);
            curl_setopt($chTg, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chTg, CURLOPT_POST, true);
            curl_setopt($chTg, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($chTg, CURLOPT_TIMEOUT, 30);
            curl_setopt($chTg, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($chTg, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chTg, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($chTg, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            $response = curl_exec($chTg);
            $httpCode = curl_getinfo($chTg, CURLINFO_HTTP_CODE);
            curl_close($chTg);

            // 3. Clean up temporary file
            if (file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            return ($httpCode >= 200 && $httpCode < 300);

        } catch (\Exception $e) {
            return false;
        }
    }
}