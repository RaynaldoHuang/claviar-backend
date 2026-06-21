<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consignor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'address', 'notes'];

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function payouts(): HasMany { return $this->hasMany(Payout::class); }
    public function intakeBatches(): HasMany { return $this->hasMany(IntakeBatch::class); }
}
