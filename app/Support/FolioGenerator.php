<?php

namespace App\Support;

use App\Models\FolioSequence;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;

class FolioGenerator
{
    public function __construct(private DatabaseManager $db)
    {
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
                    'prefijo' => strtoupper(substr($warehouse->codigo, 0, 3)),
                    'consecutivo' => 1,
                ]);
            }

            $folio = sprintf('%s-%06d', $sequence->prefijo, $sequence->consecutivo);
            $sequence->consecutivo += 1;
            $sequence->save();

            return $folio;
        });
    }
}
