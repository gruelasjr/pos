<?php

namespace App\Services\Catalog;

use App\Models\ProductMetadataCodedValue;
use App\Models\ReservedSkuRange;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SkuRangeService
{
    public function save(array $data, ?ReservedSkuRange $range = null): ReservedSkuRange
    {
        return DB::transaction(function () use ($data, $range): ReservedSkuRange {
            $range ??= new ReservedSkuRange();
            $structural = array_intersect_key($data, array_flip(['segments', 'from', 'to']));
            if ($range->exists && $range->used_up_to !== null && $this->structureChanged($range, $structural)) {
                throw ValidationException::withMessages(['range' => ['Un rango utilizado solo puede activarse o desactivarse.']]);
            }

            if (!$range->exists || $range->used_up_to === null) {
                $range->loadMissing('segments');
                $segments = $data['segments'] ?? $range->segments->map(fn ($segment) => [
                    'definition_id' => $segment->definition_id,
                    'coded_value_id' => $segment->coded_value_id,
                ])->values()->all();
                $from = $data['from'] ?? $range->from;
                $to = $data['to'] ?? $range->to;
                $codedValues = $this->validatedCodedValues($segments);
                $prefix = $codedValues->pluck('code')->implode('-');
                $overlap = ReservedSkuRange::query()
                    ->where('composed_prefix', $prefix)
                    ->where('from', '<=', $to)
                    ->where('to', '>=', $from)
                    ->when($range->exists, fn ($query) => $query->whereKeyNot($range->getKey()))
                    ->exists();
                if ($overlap) {
                    throw ValidationException::withMessages(['from' => ['El intervalo se superpone con otro rango del mismo prefijo.']]);
                }
                $range->fill(['composed_prefix' => $prefix, 'from' => $from, 'to' => $to]);
                $range->save();
                $range->segments()->delete();
                foreach ($segments as $position => $segment) {
                    $range->segments()->create([
                        'definition_id' => $segment['definition_id'],
                        'coded_value_id' => $segment['coded_value_id'],
                        'position' => $position,
                    ]);
                }
            }

            if (array_key_exists('active', $data)) {
                $range->active = $data['active'];
                $range->save();
            }

            return $this->present($range);
        });
    }

    public function match(string $sku): ?ReservedSkuRange
    {
        return ReservedSkuRange::query()->where('active', true)->with('segments.codedValue', 'segments.definition')
            ->get()->first(function (ReservedSkuRange $range) use ($sku): bool {
                if (!preg_match('/^' . preg_quote($range->composed_prefix, '/') . '-(\d{6})$/', $sku, $matches)) {
                    return false;
                }
                $number = (int) $matches[1];
                return $number >= $range->from && $number <= $range->to;
            });
    }

    public function inheritedMetadata(ReservedSkuRange $range): array
    {
        return $range->segments->mapWithKeys(fn ($segment) => [
            $segment->definition_id => $segment->codedValue->value,
        ])->all();
    }

    public function present(ReservedSkuRange $range): ReservedSkuRange
    {
        $range->load('segments.definition', 'segments.codedValue');
        $range->setAttribute('example_sku', $range->composed_prefix . '-' . str_pad((string) $range->from, 6, '0', STR_PAD_LEFT));
        $range->setAttribute('locked', $range->used_up_to !== null);
        return $range;
    }

    private function validatedCodedValues(array $segments)
    {
        $definitionIds = collect($segments)->pluck('definition_id');
        if ($definitionIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['segments' => ['No se puede repetir una definición de metadata.']]);
        }
        $codedValues = ProductMetadataCodedValue::query()->where('active', true)
            ->whereIn('id', collect($segments)->pluck('coded_value_id'))->with('definition')->get()->keyBy('id');
        foreach ($segments as $index => $segment) {
            $coded = $codedValues->get($segment['coded_value_id']);
            if (!$coded || $coded->definition_id !== $segment['definition_id'] || !in_array($coded->definition->type, ['text', 'select'], true)) {
                throw ValidationException::withMessages(["segments.{$index}" => ['El valor codificado no corresponde a una metadata de texto o selección activa.']]);
            }
        }
        return collect($segments)->map(fn ($segment) => $codedValues->get($segment['coded_value_id']));
    }

    private function structureChanged(ReservedSkuRange $range, array $structural): bool
    {
        $range->loadMissing('segments');
        $current = $range->segments->map(fn ($segment) => [
            'definition_id' => $segment->definition_id,
            'coded_value_id' => $segment->coded_value_id,
        ])->values()->all();
        return (isset($structural['from']) && (int) $structural['from'] !== (int) $range->from)
            || (isset($structural['to']) && (int) $structural['to'] !== (int) $range->to)
            || (isset($structural['segments']) && array_values($structural['segments']) !== $current);
    }
}
