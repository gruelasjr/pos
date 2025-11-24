<?php

/**
 * Helper: folio sequence generator.
 *
 * Generates and reserves sequential folio numbers for receipts and documents.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

/**
 * Folio generation utilities.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

namespace App\Support;

use App\Models\FolioSequence;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;

/**
 * Utility: generate and reserve folio sequences.
 *
 * Provides next folio numbers per warehouse for receipts and documents.
 *
 * @package   App\Support
 */
class FolioGenerator
{
    public function __construct(private DatabaseManager $db)
    {
        // No body
    }

    public function next(Warehouse $warehouse): string
    {
        return $this->db->transaction(function () use ($warehouse) {
            $sequence = FolioSequence::query()
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $sequence = FolioSequence::create([
                    'warehouse_id' => $warehouse->id,
                    'prefix' => strtoupper(substr($warehouse->code, 0, 3)),
                    'sequence' => 1,
                ]);
            }

            $folio = sprintf('%s-%06d', $sequence->prefix, $sequence->sequence);
            $sequence->sequence += 1;
            $sequence->save();

            return $folio;
        });
    }
}
