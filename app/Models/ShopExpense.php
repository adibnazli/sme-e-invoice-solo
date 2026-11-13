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
        'expense_date',
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}