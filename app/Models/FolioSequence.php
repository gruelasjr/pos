<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolioSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'prefijo',
        'consecutivo',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
