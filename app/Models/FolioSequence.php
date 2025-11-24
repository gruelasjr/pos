<?php

/**
 * Model: Folio sequence generator.
 *
 * Tracks sequential folio numbers for receipts and other sequential documents.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks folio sequences used to generate document numbers per warehouse.
 *
 * @property int         $id
 * @property int         $warehouse_id
 * @property string      $prefix
 * @property int         $sequence
 * @property-read Warehouse $warehouse
 */
class FolioSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'prefix',
        'sequence',
    ];

    /**
     * Warehouse that owns the folio sequence.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
