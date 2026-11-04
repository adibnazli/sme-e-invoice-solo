<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show($id)
    {
        $order = Order::with(['items.product', 'customer', 'user'])->findOrFail($id);
        return view('invoices.show', compact('order'));
    }
}