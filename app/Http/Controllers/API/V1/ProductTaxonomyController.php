<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ProductMetadataDefinition;
use App\Models\ProductMetadataCodedValue;
use App\Models\ProductTag;
use App\Models\ReservedSkuRangeSegment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductTaxonomyController extends BaseApiController
{
    public function tags(Request $request)
    {
        $query = ProductTag::query()->withCount('products')->orderBy('name');
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return $this->paginated($query->paginate($request->integer('per_page', 100)), 'Etiquetas listadas');
    }

    public function storeTag(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:96'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $tag = ProductTag::create($data);

        return $this->success('Etiqueta creada', $tag);
    }

    public function updateTag(Request $request, ProductTag $productTag)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'slug' => ['sometimes', 'string', 'max:96'],
            'active' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }
        $productTag->update($data);

        return $this->success('Etiqueta actualizada', $productTag->fresh());
    }

    public function deleteTag(ProductTag $productTag)
    {
        $productTag->delete();

        return $this->success('Etiqueta eliminada');
    }

    public function metadata(Request $request)
    {
        $query = ProductMetadataDefinition::query()->withCount('values')->with(['codedValues' => fn ($query) => $query->orderBy('value')])->orderBy('label');
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return $this->paginated($query->paginate($request->integer('per_page', 100)), 'Metadatos listados');
    }

    public function storeMetadata(Request $request)
    {
        $definition = ProductMetadataDefinition::create($this->metadataPayload($request));

        return $this->success('Metadato creado', $definition);
    }

    public function updateMetadata(Request $request, ProductMetadataDefinition $definition)
    {
        $definition->update($this->metadataPayload($request, true));

        return $this->success('Metadato actualizado', $definition->fresh());
    }

    public function deleteMetadata(ProductMetadataDefinition $definition)
    {
        $definition->delete();

        return $this->success('Metadato eliminado');
    }

    public function storeCodedValue(Request $request, ProductMetadataDefinition $definition)
    {
        abort_unless(in_array($definition->type, ['text', 'select'], true), 422, 'Solo texto y selección admiten códigos SKU.');
        $payload = $this->codedValuePayload($request);
        if ($definition->type === 'select' && !in_array($payload['value'], $definition->options ?? [], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['value' => ['El valor no pertenece a las opciones de la metadata.']]);
        }
        $coded = $definition->codedValues()->create($payload);
        return $this->success('Valor codificado creado', $coded);
    }

    public function updateCodedValue(Request $request, ProductMetadataCodedValue $codedValue)
    {
        $payload = $this->codedValuePayload($request, true);
        if (isset($payload['code']) && $payload['code'] !== $codedValue->code
            && ReservedSkuRangeSegment::query()->where('coded_value_id', $codedValue->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => ['El código pertenece a un rango y ya no puede cambiarse.']]);
        }
        $codedValue->update($payload);
        return $this->success('Valor codificado actualizado', $codedValue->fresh());
    }

    private function codedValuePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        return $request->validate([
            'value' => [$required, 'string', 'max:120'],
            'code' => [$required, 'string', 'max:16', 'regex:/^[A-Z0-9]+$/'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function metadataPayload(Request $request, bool $partial = false): array
    {
        $sometimes = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'key' => [$sometimes, 'string', 'max:64'],
            'label' => [$sometimes, 'string', 'max:100'],
            'type' => [$sometimes, Rule::in(['text', 'number', 'boolean', 'select'])],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['key'])) {
            $data['key'] = Str::snake($data['key']);
        }
        if (($data['type'] ?? null) !== 'select' && array_key_exists('options', $data)) {
            $data['options'] = null;
        }

        return $data;
    }
}
