<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductMetadataCodedValue extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'definition_id', 'value', 'code', 'active'];
    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $codedValue): void {
            $codedValue->id ??= (string) Str::uuid();
            $codedValue->code = strtoupper($codedValue->code);
        });
        static::updating(fn (self $codedValue) => $codedValue->code = strtoupper($codedValue->code));
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProductMetadataDefinition::class, 'definition_id');
    }
}
