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
    Route::get('/api/ai-insights', [\App\Http\Controllers\DashboardController::class, 'generateAiInsights'])->name('api.ai.insights');

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
    Route::get('/generate-description', [App\Http\Controllers\ProductController::class, 'generateDescription']);

    Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    
});

Route::get('/debug-ai', function () {
    // Make sure there are NO double quotes around the key in your .env
    $apiKey = trim(env('GEMINI_API_KEY')); 
    
    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
        ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
        
    return $response->json();
});