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
        $products = Product::where('status', 'ACTIVE')->get();
        $customers = Customer::all();
        return view('pos.index', compact('products', 'customers'));
    }

    public function checkout(Request $request)
    {
        $cart = json_decode($request->input('cart_data'), true);
        $customerId = $request->input('customer_id');

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty.');
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $invoiceItemsText = "";
            
            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
                'total_amount' => 0, 
                'payment_method' => 'CASH'
            ]);

            foreach ($cart as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                
                if ($product->stock_quantity < $item['qty']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                $subtotal = $item['price'] * $item['qty'];
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal' => $subtotal
                ]);

                $product->decrement('stock_quantity', $item['qty']);
                
                $invoiceItemsText .= "- " . $product->name . " (x" . $item['qty'] . ") @ RM" . number_format($item['price'], 2) . " = RM" . number_format($subtotal, 2) . "\n";
            }

            $order->update(['total_amount' => $totalAmount]);

            $customerName = 'Walk-in Customer';
            if (!empty($customerId)) {
                $pointsEarned = floor($totalAmount / 10);
                $customer = Customer::findOrFail($customerId);
                $customer->increment('loyalty_points', $pointsEarned);
                $customerName = $customer->name;
            }

            DB::commit();

            $rawInvoiceData = "Order ID: #" . str_pad($order->id, 6, '0', STR_PAD_LEFT) . "\n" .
                              "Cashier: " . Auth::user()->name . "\n" .
                              "Customer: " . $customerName . "\n" .
                              "Items:\n" . $invoiceItemsText .
                              "Total Amount: RM" . number_format($totalAmount, 2);

            $groqApiKey = env('GROQ_API_KEY');
            $telegramToken = env('TELEGRAM_BOT_TOKEN');
            $telegramChatId = env('TELEGRAM_CHAT_ID');

            if (!empty($groqApiKey) && !empty($telegramToken) && !empty($telegramChatId)) {
                
                $groqEndpoint = "https://api.groq.com/openai/v1/chat/completions";
                $groqPayload = json_encode([
                    'model' => 'openai/gpt-oss-20b',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an AI for a hardware store. Format the provided order data into a clean, professional, and very concise text-based E-Invoice for a Telegram message. Use Markdown formatting. Do not add conversational filler.'],
                        ['role' => 'user', 'content' => $rawInvoiceData]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500
                ]);

                $chGroq = curl_init($groqEndpoint);
                curl_setopt($chGroq, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chGroq, CURLOPT_POST, true);
                curl_setopt($chGroq, CURLOPT_POSTFIELDS, $groqPayload);
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

                if ($groqHttpCode >= 200 && $groqHttpCode < 300) {
                    $groqData = json_decode($groqResponse, true);
                    $parsedInvoiceText .= $groqData['choices'][0]['message']['content'];
                } else {
                    $parsedInvoiceText .= $rawInvoiceData;
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
                curl_setopt($chTg, CURLOPT_POSTFIELDS, $tgPayload);
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

            return redirect()->route('invoice.show', $order->id)->with('success', 'Payment processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}