<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = ['code', 'intake_batch_id', 'consignor_id', 'category_id', 'brand_id', 'name', 'description', 'purchase_price', 'selling_price', 'condition', 'status', 'is_draft'];
    protected $casts = ['purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'is_draft' => 'boolean'];

    public function consignor(): BelongsTo { return $this->belongsTo(Consignor::class); }
    public function intakeBatch(): BelongsTo { return $this->belongsTo(IntakeBatch::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function coverImage(): HasOne { return $this->hasOne(ProductImage::class)->where('is_cover', true); }
    public function sale(): HasOne { return $this->hasOne(Sale::class); }
    public function orderItem(): HasOne { return $this->hasOne(OrderItem::class); }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn (Builder $q) => $q->where(fn (Builder $nested) => $nested
            ->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")));
    }
}
