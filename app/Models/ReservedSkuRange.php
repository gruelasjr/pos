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
        'prefijo',
        'desde',
        'hasta',
        'usado_hasta',
        'proposito',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $range) {
            $range->id ??= (string) Str::uuid();
        });
    }

    public function nextSku(): ?string
    {
        $next = $this->usado_hasta ? $this->usado_hasta + 1 : $this->desde;

        if ($next > $this->hasta) {
            return null;
        }

        return ($this->prefijo ?? '') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
