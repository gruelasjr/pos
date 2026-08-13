<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property string $type */
class ProductMetadataDefinition extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'key', 'label', 'type', 'options', 'active'];
    protected $casts = ['options' => 'array', 'active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $definition): void {
            $definition->id ??= (string) Str::uuid();
            $definition->key = Str::snake($definition->key ?: $definition->label);
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductMetadataValue::class, 'definition_id');
    }

    public function codedValues(): HasMany
    {
        return $this->hasMany(ProductMetadataCodedValue::class, 'definition_id');
    }
}
