<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_sold',
        'recorded_cost_price',
        'unit_price',
        'subtotal'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}