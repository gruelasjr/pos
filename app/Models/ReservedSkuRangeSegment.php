<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReservedSkuRangeSegment extends Model
{
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'range_id', 'definition_id', 'coded_value_id', 'position'];

    protected static function booted(): void
    {
        static::creating(fn (self $segment) => $segment->id ??= (string) Str::uuid());
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(ReservedSkuRange::class, 'range_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProductMetadataDefinition::class, 'definition_id');
    }

    public function codedValue(): BelongsTo
    {
        return $this->belongsTo(ProductMetadataCodedValue::class, 'coded_value_id');
    }
}
