<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Coupon;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class CouponController extends BaseApiController
{
    public function index(Request $request)
    {
        $coupons = Coupon::query()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($coupons, 'Cupones listados');
    }

    public function store(Request $request, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $coupon = Coupon::create($data);
        $auditLogger->log('coupon.created', $request->user(), Coupon::class, $coupon->id, $data);

        return $this->success('Cupón creado', $coupon);
    }
}
