<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (self $range) {
            $range->id ??= (string) Str::uuid();
        });
    }

    public function nextSku(): ?string
    {
        $next = $this->used_up_to ? $this->used_up_to + 1 : $this->from;

        if ($next > $this->to) {
            return null;
        }

        return ($this->prefix ?? '') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
