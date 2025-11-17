<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->filled('query'), function ($q) use ($request) {
                $term = $request->input('query');
                $q->where(function ($search) use ($term) {
                    $search->where('nombre', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($customers, 'Clientes listados');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', 'unique:customers,email'],
            'telefono' => ['nullable', 'string', 'max:32'],
            'acepta_marketing' => ['boolean'],
        ]);

        $customer = Customer::create($data);

        return $this->success('Cliente registrado', $customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', 'unique:customers,email,' . $customer->id . ',id'],
            'telefono' => ['nullable', 'string', 'max:32'],
            'acepta_marketing' => ['boolean'],
        ]);

        $customer->update($data);

        return $this->success('Cliente actualizado', $customer);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'nombre' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:32'],
            'acepta_marketing' => ['boolean'],
        ]);

        $sale = Sale::where('id', $data['token'])
            ->orWhere('folio', $data['token'])
            ->first();

        if (!$sale) {
            return $this->error('Token inválido', [], 404);
        }

        $customer = $sale->customer_id ? Customer::find($sale->customer_id) : new Customer();
        $customer->fill([
            'nombre' => $data['nombre'],
            'email' => $data['email'] ?? $customer->email,
            'telefono' => $data['telefono'] ?? $customer->telefono,
            'acepta_marketing' => $data['acepta_marketing'] ?? false,
        ]);
        $customer->save();

        $sale->customer_id = $customer->id;
        $sale->save();

        return $this->success('Cliente actualizado desde registro remoto', $customer);
    }
}
