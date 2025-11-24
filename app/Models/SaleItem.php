<?php

/**
 * Model: SaleItem.
 *
 * Line item attached to a `Sale` with quantity, price and discounts.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Sale item model.
 *
 * Represents a line item inside a sale.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model for items belonging to a sale.
 */
/**
 * Line item attached to a `Sale` with pricing and quantity.
 *
 * @property string   $id
 * @property string   $sale_id
 * @property string   $product_id
 * @property string   $sku
 * @property string   $description
 * @property int      $quantity
 * @property float    $unit_price
 * @property float    $discount
 * @property float    $subtotal
 * @property-read Sale    $sale
 * @property-read Product $product
 *
 * @package   App\Models
 */
class SaleItem extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'sku',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Boot callbacks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (self $item) {
            $item->id ??= (string) Str::uuid();
        });
    }

    /**
     * Belongs to Sale.
     *
     * @return BelongsTo
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Belongs to Product.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
