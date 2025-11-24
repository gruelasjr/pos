<?php

/**
 * Model: Warehouse.
 *
 * Represents a physical storage location for inventory.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Warehouse model.
 *
 * Represents a physical or logical storage location.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Application Warehouse entity.
 */
/**
 * Represents a physical warehouse location for inventory storage.
 *
 * @package   App\Models
 */
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

    /**
     * Model boot callbacks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (self $warehouse) {
            $warehouse->id ??= (string) Str::uuid();
        });
    }

    /**
     * Get inventories related to this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get carts that belong to this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get sales associated with this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
