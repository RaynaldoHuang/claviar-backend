<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = ['image', 'is_cover', 'sort_order'];
    protected $casts = ['is_cover' => 'boolean'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
