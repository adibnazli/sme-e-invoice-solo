<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Middleware\OwnerAccess;
use App\Http\Controllers\StaffController;

Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::middleware([OwnerAccess::class])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/api/ai-insights', [DashboardController::class, 'generateAiInsights'])->name('api.ai.insights');
        Route::post('/dashboard/expenses', [DashboardController::class, 'storeExpense'])->name('dashboard.expenses.store');
        Route::post('/dashboard/lhdn-consolidate', [DashboardController::class, 'consolidateLhdn'])->name('dashboard.lhdn.consolidate');
        Route::get('/dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export.csv');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products/scan', [ProductController::class, 'scan'])->name('products.scan');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
        Route::post('/products/{product}/restock', [ProductController::class, 'restock'])->name('products.restock');

        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/customers/{customer}/repay', [CustomerController::class, 'repayDebt'])->name('customers.repay');
    });

    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/parse-ai-order', [PosController::class, 'parseAiOrder'])->name('pos.parse.ai');

    Route::get('/orders', [InvoiceController::class, 'index'])->name('orders.index');
    Route::post('/orders/{id}/send-telegram', [App\Http\Controllers\PosController::class, 'sendTelegram'])->name('invoice.telegram');
    Route::get('/invoice/{order}', [InvoiceController::class, 'show'])->name('invoice.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
});