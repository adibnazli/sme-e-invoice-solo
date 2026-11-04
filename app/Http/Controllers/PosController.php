<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
            }

            $order->update(['total_amount' => $totalAmount]);

            if (!empty($customerId)) {
                $pointsEarned = floor($totalAmount / 10);
                Customer::where('id', $customerId)->increment('loyalty_points', $pointsEarned);
            }

            DB::commit();

            $telegramToken = env('TELEGRAM_BOT_TOKEN');
            $telegramChatId = env('TELEGRAM_CHAT_ID');

            if (!empty($telegramToken) && !empty($telegramChatId)) {
                $message = "SME Hardware Hub\nNew Sale Registered!\nOrder ID: #" . str_pad($order->id, 6, '0', STR_PAD_LEFT) . "\nTotal: RM " . number_format($totalAmount, 2) . "\nCashier: " . Auth::user()->name;
                
                $response = Http::post("https://api.telegram.org/bot{$telegramToken}/sendMessage", [
                    'chat_id' => $telegramChatId,
                    'text' => $message,
                ]);

                if (!$response->successful()) {
                    return redirect()->route('invoice.show', $order->id)
                        ->with('success', 'Payment processed successfully!')
                        ->with('error', 'Telegram Error: ' . $response->body());
                }
            } else {
                return redirect()->route('invoice.show', $order->id)
                    ->with('success', 'Payment processed successfully!')
                    ->with('error', 'Telegram Error: Credentials missing from .env');
            }

            return redirect()->route('invoice.show', $order->id)->with('success', 'Payment processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}