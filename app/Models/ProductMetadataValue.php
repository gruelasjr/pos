<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $definition_id
 * @property-read ProductMetadataDefinition|null $definition
 */
class ProductMetadataValue extends Model
{
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'product_id', 'definition_id', 'value_text', 'value_number', 'value_boolean'];
    protected $casts = ['value_number' => 'decimal:4', 'value_boolean' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn (self $value) => $value->id ??= (string) Str::uuid());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductMetadataDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProductMetadataDefinition::class, 'definition_id');
    }

    public function resolvedValue(): string|float|bool|null
    {
        return match ($this->definition?->type) {
            'number' => $this->value_number === null ? null : (float) $this->value_number,
            'boolean' => $this->value_boolean,
            default => $this->value_text,
        };
    }
}
