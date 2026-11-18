<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'recorded_by',
        'category',
        'amount',
        'description',
        'expense_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}