<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolioSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'prefix',
        'sequence',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
