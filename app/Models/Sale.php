<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = ['product_id', 'payout_id', 'customer_id', 'order_id', 'customer_name', 'customer_phone', 'sale_price', 'payment_method', 'sold_at'];
    protected $casts = ['sale_price' => 'decimal:2', 'sold_at' => 'datetime'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function payout(): BelongsTo { return $this->belongsTo(Payout::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
