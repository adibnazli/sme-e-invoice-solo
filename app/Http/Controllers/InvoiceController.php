<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Order $order)
    {
        $order->load(['items.product', 'user']);
        return view('invoices.show', compact('order'));
    }
}