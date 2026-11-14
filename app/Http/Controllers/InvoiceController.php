<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'user', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('invoices.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'customer', 'user'])->findOrFail($id);
        return view('invoices.show', compact('order'));
    }
}