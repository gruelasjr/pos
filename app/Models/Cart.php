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

use Equidna\BeeHive\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Support\Money;

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
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visual_key',
        'user_id',
        'warehouse_id',
        'customer_id',
        'status',
        'total_gross',
        'discount_total',
        'promotion_discount',
        'applied_promotions',
        'total_net',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'promotion_discount' => 'decimal:2',
        'applied_promotions' => 'array',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculateTotals(): void
    {
        $grossCents = $this->items->sum(fn (CartItem $item) => Money::toCents($item->subtotal));
        $discountCents = Money::toCents($this->discount_total);
        $promotionCents = Money::toCents($this->promotion_discount);
        $this->total_gross = Money::fromCents($grossCents);
        $this->total_net = Money::fromCents(max(0, $grossCents - $discountCents - $promotionCents));
    }
}
