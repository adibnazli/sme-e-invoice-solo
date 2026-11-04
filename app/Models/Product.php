<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'category', 'description', 'image_url',
        'unit_type', 'lhdn_class_code', 'supplier_cost_price',
        'retail_selling_price', 'stock_quantity', 'low_stock_threshold', 'status'
    ];

    // Helper to format currency for the views
    public function getFormattedRetailPriceAttribute()
    {
        return 'RM ' . number_format($this->retail_selling_price, 2);
    }
}