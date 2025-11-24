<?php

/**
 * Controller: Customer endpoints (API v1).
 *
 * Manages customer records used in sales and loyalty features.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for customer management endpoints.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for customer endpoints (list, create, update, register).
 *
 * Provides JSON responses and customer registration helpers.
 */
/**
 * Customer controller.
 *
 * Manages customers used in sales and registration flows.
 *
 * @package   App\Http\Controllers\API\V1
 */
class CustomerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->filled('query'), function ($q) use ($request) {
                $term = $request->input('query');
                $q->where(function ($search) use ($term) {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($customers, 'Clientes listados');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'accepts_marketing' => ['boolean'],
        ]);

        $customer = Customer::create($data);

        return $this->success('Cliente registrado', $customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', 'unique:customers,email,' . $customer->id . ',id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'accepts_marketing' => ['boolean'],
        ]);

        $customer->update($data);

        return $this->success('Cliente actualizado', $customer);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
            'accepts_marketing' => ['boolean'],
        ]);

        $sale = Sale::where('id', $data['token'])
            ->orWhere('folio', $data['token'])
            ->first();

        if (!$sale) {
            return $this->error('Token inválido', [], 404);
        }

        $customer = $sale->customer_id ? Customer::find($sale->customer_id) : new Customer();
        $customer->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? $customer->email,
            'phone' => $data['phone'] ?? $customer->phone,
            'accepts_marketing' => $data['accepts_marketing'] ?? false,
        ]);
        $customer->save();

        $sale->customer_id = $customer->id;
        $sale->save();

        return $this->success('Cliente actualizado desde registro remoto', $customer);
    }
}
