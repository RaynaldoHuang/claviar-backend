<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderItem extends Model
{
    protected $fillable = ['order_id','product_id','purchase_price','sale_price','completed_at'];
    protected $casts = ['purchase_price'=>'decimal:2','sale_price'=>'decimal:2','completed_at'=>'datetime'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
