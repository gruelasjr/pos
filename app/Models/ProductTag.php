<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'slug', 'active'];
    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $tag): void {
            $tag->id ??= (string) Str::uuid();
            $tag->slug = Str::slug($tag->slug ?: $tag->name);
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_tag')->withTimestamps();
    }
}
