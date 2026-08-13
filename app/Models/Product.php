<?php

/**
 * Model: Product.
 *
 * Represents a sellable product in the POS catalog.
 *
 * PHP 8.1+
 *
 * @property string $tenant_id
 * @property string $product_type_id
 * @property-read ProductType $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductTag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductMetadataValue> $metadataValues
 * @package   App\Models
 */

/**
 * Product model.
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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Represents a sellable product in the catalog.
 *
 * @package   App\Models
 */
class Product extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sku',
        'short_description',
        'long_description',
        'photo_url',
        'purchase_price',
        'sale_price',
        'entry_date',
        'stock_end_date',
        'product_type_id',
        'active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'entry_date' => 'datetime',
        'stock_end_date' => 'datetime',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            $product->id ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<ProductType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return BelongsToMany<ProductTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag')->withTimestamps();
    }

    /** @return HasMany<ProductMetadataValue, $this> */
    public function metadataValues(): HasMany
    {
        return $this->hasMany(ProductMetadataValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('short_description', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }
}
