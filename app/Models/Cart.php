<?php

/**
 * Model: Cart.
 *
 * Represents a shopping cart with items prior to checkout.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Cart model.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a shopping cart with items prior to checkout.
 *
 * @property string                                                          $id
 * @property string                                                          $warehouse_id
 * @property string                                                          $user_id
 * @property string                                                          $status
 * @property string                                                          $total_gross
 * @property string                                                          $discount_total
 * @property string                                                          $total_net
 * @property-read Warehouse                                                  $warehouse
 * @property-read User                                                       $seller
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CartItem> $items
 *
 * @package   App\Models
 */
class Cart extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visual_key',
        'user_id',
        'warehouse_id',
        'status',
        'total_gross',
        'discount_total',
        'total_net',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cart) {
            $cart->id ??= (string) Str::uuid();
            $cart->visual_key ??= strtoupper(Str::random(6));
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
        $bruto = $this->items->sum(fn(CartItem $item) => (float) $item->subtotal);
        $this->total_gross = (string) $bruto;
        $descuento = (float) ($this->discount_total ?? 0);
        $this->total_net = (string) max(0, $bruto - $descuento);
    }
}
