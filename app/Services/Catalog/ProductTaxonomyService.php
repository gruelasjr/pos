<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductMetadataDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductTaxonomyService
{
    public function sync(Product $product, array $tagIds = [], array $metadata = []): Product
    {
        $tenantId = (string) $product->getAttribute('tenant_id');
        $product->tags()->sync(collect($tagIds)->mapWithKeys(
            fn (string $id): array => [$id => ['tenant_id' => $tenantId]]
        )->all());

        $definitions = ProductMetadataDefinition::query()
            ->whereIn('id', array_keys($metadata))
            ->get()
            ->keyBy('id');

        foreach ($metadata as $definitionId => $rawValue) {
            $definition = $definitions->get($definitionId);
            if (! $definition) {
                throw ValidationException::withMessages([
                    "metadata.{$definitionId}" => ['La definición de metadato no existe.'],
                ]);
            }

            $this->validateValue($definition, $rawValue);
            $values = ['value_text' => null, 'value_number' => null, 'value_boolean' => null];
            $column = match ($definition->type) {
                'number' => 'value_number',
                'boolean' => 'value_boolean',
                default => 'value_text',
            };
            $values[$column] = $rawValue === '' ? null : $rawValue;

            $product->metadataValues()->updateOrCreate(
                ['definition_id' => $definition->id],
                $values
            );
        }

        $product->metadataValues()->whereNotIn('definition_id', array_keys($metadata))->delete();

        return $product->fresh()->load('type', 'tags', 'metadataValues.definition');
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    private function validateValue(ProductMetadataDefinition $definition, mixed $value): void
    {
        $valid = match ($definition->type) {
            'number' => $value === null || $value === '' || is_numeric($value),
            'boolean' => $value === null || is_bool($value) || in_array($value, [0, 1, '0', '1'], true),
            'select' => $value === null || $value === '' || in_array((string) $value, $definition->options ?? [], true),
            default => $value === null || is_scalar($value),
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                "metadata.{$definition->id}" => ["El valor no es válido para {$definition->label}."],
            ]);
        }
    }
}
