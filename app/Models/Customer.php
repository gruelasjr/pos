<?php

/**
 * Model: Customer.
 *
 * Represents a customer or guest used in sales and registrations.
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
 * Represents a customer used in sales and registrations.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $phone
 *
 * @package   App\Models
 */
class Customer extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'accepts_marketing',
    ];

    protected $casts = [
        'accepts_marketing' => 'boolean',
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
