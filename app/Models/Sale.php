<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'folio',
        'warehouse_id',
        'user_id',
        'customer_id',
        'metodo_pago',
        'pagos_detalle',
        'total_bruto',
        'descuento_total',
        'total_neto',
        'pagado_en',
    ];

    protected $casts = [
        'pagos_detalle' => 'array',
        'total_bruto' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'total_neto' => 'decimal:2',
        'pagado_en' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sale) {
            $sale->id ??= (string) Str::uuid();
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
