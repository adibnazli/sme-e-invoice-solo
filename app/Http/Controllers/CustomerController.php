<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('current_debt', 'desc')
                             ->orderBy('name', 'asc')
                             ->get();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255|unique:customers,phone',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:WALK_IN,CONTRACTOR',
            'credit_limit' => 'required|numeric|min:0'
        ]);

        Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'customer_type' => $request->customer_type,
            'credit_limit' => $request->customer_type === 'CONTRACTOR' ? $request->credit_limit : 0.00,
            'current_debt' => 0.00,
            'loyalty_points' => 0
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer / Contractor account registered.');
    }

    public function repayDebt(Request $request, Customer $customer)
    {
        $request->validate([
            'repayment_amount' => 'required|numeric|min:0.01'
        ]);

        $amount = (float) $request->repayment_amount;

        if ($amount > $customer->current_debt) {
            return back()->withErrors(['repayment_amount' => 'Repayment amount cannot exceed current outstanding debt (RM ' . number_format($customer->current_debt, 2) . ').']);
        }

        $customer->decrement('current_debt', $amount);

        return redirect()->route('customers.index')->with('success', 'Hutang payment of RM ' . number_format($amount, 2) . ' recorded for ' . $customer->name . '.');
    }
}