<?php

/**
 * Product type model.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductType extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'code'];

    protected static function booted(): void
    {
        static::creating(function (self $type) {
            $type->id ??= (string) Str::uuid();
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
