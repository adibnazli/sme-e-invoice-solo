<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Authentication Routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Require Login)
Route::middleware('auth')->group(function () {
    
    // Module 5: Owner Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Module 4: POS Checkout Placeholder
    Route::get('/pos', [\App\Http\Controllers\PosController::class, 'index'])->name('pos');
    Route::post('/pos/checkout', [\App\Http\Controllers\PosController::class, 'checkout'])->name('pos.checkout');

    Route::get('/invoice/{order}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice.show');

    // Module 2: Inventory
    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::post('/products/{product}/archive', [\App\Http\Controllers\ProductController::class, 'archive'])->name('products.archive');
    Route::get('/products/create', [\App\Http\Controllers\ProductController::class, 'create'])->name('products.create');
    Route::post('/products/scan', [\App\Http\Controllers\ProductController::class, 'scan'])->name('products.scan');
    Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    
});