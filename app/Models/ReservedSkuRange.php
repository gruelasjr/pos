<?php

/**
 * Model: ReservedSkuRange.
 *
 * Represents a range of reserved SKUs allocated for generation.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Reserved SKU ranges.
 *
 * Stores ranges of reserved SKUs for use by SKU generator services.
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
 * Model that holds reserved SKU numeric ranges.
 */
/**
 * Represents a reserved range of SKUs for generation.
 *
 * @package   App\Models
 */
class ReservedSkuRange extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'prefix',
        'from',
        'to',
        'used_up_to',
        'purpose',
    ];

    /**
     * Boot callbacks.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (self $range) {
            $range->id ??= (string) Str::uuid();
        });
    }

    /**
     * Return next available SKU in the range or null when exhausted.
     *
     * @return string|null
     */
    public function nextSku(): ?string
    {
        $next = $this->used_up_to ? $this->used_up_to + 1 : $this->from;

        if ($next > $this->to) {
            return null;
        }

        return ($this->prefix ?? '') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
