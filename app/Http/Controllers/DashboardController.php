<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $todayRevenue = Order::whereDate('created_at', Carbon::today())->sum('total_amount');
        $totalRevenue = Order::sum('total_amount');
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();
        
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('status', 'ACTIVE')
            ->get();

        return view('dashboard', compact('todayRevenue', 'totalRevenue', 'todayOrders', 'lowStockProducts'));
    }
}