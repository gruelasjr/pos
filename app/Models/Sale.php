<?php

/**
 * Model: Sale.
 *
 * Represents a completed sale transaction with line items and totals.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Sale model.
 *
 * Represents a completed sale transaction.
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
 * Represents a sale and its aggregate data.
 */
/**
 * Represents a completed sale with totals and related items.
 *
 * @property string                                                          $id
 * @property string                                                          $folio
 * @property string                                                          $warehouse_id
 * @property string                                                          $user_id
 * @property string                                                          $customer_id
 * @property string|null                                                     $cash_session_id
 * @property string                                                          $payment_method
 * @property array<string, mixed>|null                                       $payment_details
 * @property string                                                          $total_gross
 * @property string                                                          $discount_total
 * @property string                                                          $total_net
 * @property-read Warehouse                                                  $warehouse
 * @property-read User                                                       $seller
 * @property-read Customer|null                                              $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SaleItem> $items
 *
 * @package   App\Models
 */
class Sale extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    public ?string $plainCustomerRegistrationToken = null;

    protected $fillable = [
        'id',
        'folio',
        'warehouse_id',
        'user_id',
        'customer_id',
        'cash_session_id',
        'payment_method',
        'payment_details',
        'total_gross',
        'discount_total',
        'total_net',
        'paid_at',
        'customer_registration_token_hash',
        'customer_registration_expires_at',
        'customer_registration_used_at',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'total_gross' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_net' => 'decimal:2',
        'paid_at' => 'datetime',
        'customer_registration_expires_at' => 'datetime',
        'customer_registration_used_at' => 'datetime',
    ];

    protected $appends = [
        'customer_registration_token',
    ];

    /**
     * Model boot callbacks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (self $sale) {
            $sale->id ??= (string) Str::uuid();
        });
    }

    public function getCustomerRegistrationTokenAttribute(): ?string
    {
        return $this->plainCustomerRegistrationToken;
    }

    public function issueCustomerRegistrationToken(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'customer_registration_token_hash' => hash('sha256', $token),
            'customer_registration_expires_at' => now()->addDays(
                (int) config('security.customer_registration_token_ttl_days', 30)
            ),
            'customer_registration_used_at' => null,
        ])->save();

        $this->plainCustomerRegistrationToken = $token;

        return $token;
    }

    /**
     * Warehouse relation.
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Seller (user) relation.
     *
     * @return BelongsTo
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Customer relation.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /**
     * Sale items relation.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
