<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 'ACTIVE')->get();
        return view('pos.index', compact('products'));
    }

    public function checkout(Request $request)
    {
        $cart = json_decode($request->input('cart_data'), true);

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty.');
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            
            $order = Order::create([
                'user_id' => Auth::id(),
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

            DB::commit();
            return redirect()->route('pos')->with('success', 'Payment processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}