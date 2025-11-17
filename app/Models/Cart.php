<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'clave_visual',
        'user_id',
        'warehouse_id',
        'estado',
        'total_bruto',
        'descuento_total',
        'total_neto',
    ];

    protected $casts = [
        'total_bruto' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'total_neto' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cart) {
            $cart->id ??= (string) Str::uuid();
            $cart->clave_visual ??= strtoupper(Str::random(6));
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculateTotals(): void
    {
        $bruto = $this->items->sum(fn (CartItem $item) => $item->subtotal);
        $this->total_bruto = $bruto;
        $descuento = $this->descuento_total ?? 0;
        $this->total_neto = max(0, $bruto - $descuento);
    }
}
