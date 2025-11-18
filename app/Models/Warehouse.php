<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $warehouse) {
            $warehouse->id ??= (string) Str::uuid();
        });
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
