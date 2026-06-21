<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    protected $fillable = ['code','customer_id','status','payment_method','total_amount','paid_at','notes'];
    protected $casts = ['total_amount'=>'decimal:2','paid_at'=>'datetime'];
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
