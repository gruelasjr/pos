<?php

/**
 * Model: Inventory.
 *
 * Tracks stock levels for products per warehouse.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Inventory model.
 *
 * Tracks product stock per warehouse.
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
 * Represents stock for a product within a warehouse.
 */
/**
 * Tracks stock levels of a product in a warehouse.
 *
 * @package   App\Models
 */
class Inventory extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'warehouse_id',
        'stock',
        'reorder_point',
    ];

    protected $casts = [
        'stock' => 'integer',
        'reorder_point' => 'integer',
    ];

    /**
     * Boot callbacks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (self $inventory) {
            $inventory->id ??= (string) Str::uuid();
        });
    }

    /**
     * Product relation.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
}
