<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre',
        'email',
        'telefono',
        'acepta_marketing',
    ];

    protected $casts = [
        'acepta_marketing' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $customer) {
            $customer->id ??= (string) Str::uuid();
        });
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
