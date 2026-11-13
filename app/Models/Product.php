<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'description',
        'image_url',
        'unit_type',
        'bulk_unit_type',
        'bulk_conversion_rate',
        'lhdn_class_code',
        'supplier_cost_price',
        'retail_selling_price',
        'bulk_selling_price',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'image_path'
    ];

    public function getFormattedRetailPriceAttribute()
    {
        return 'RM ' . number_format($this->retail_selling_price, 2);
    }

    public function getMinimumSafePriceAttribute()
    {
        return round($this->supplier_cost_price * 1.08, 2);
    }
}