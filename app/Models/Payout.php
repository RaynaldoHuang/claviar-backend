<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    protected $fillable = ['consignor_id', 'amount', 'status', 'paid_at', 'notes'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    public function consignor(): BelongsTo { return $this->belongsTo(Consignor::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
