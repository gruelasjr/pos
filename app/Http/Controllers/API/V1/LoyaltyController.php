<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Sales\LoyaltyService;
use App\Models\Customer;
use App\Models\LoyaltyAccount;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class LoyaltyController extends BaseApiController
{
    public function showByCustomer(Customer $customer)
    {
        $account = LoyaltyAccount::query()
            ->where('customer_id', $customer->id)
            ->with('movements')
            ->first();

        return $this->success('Cuenta de lealtad', $account);
    }

    public function redeem(Request $request, LoyaltyService $loyaltyService, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'loyalty_account_id' => ['required', 'exists:loyalty_accounts,id'],
            'points' => ['required', 'integer', 'min:1'],
            'sale_id' => ['nullable', 'exists:sales,id'],
        ]);

        $account = $loyaltyService->redeem($data['loyalty_account_id'], (int) $data['points'], $data['sale_id'] ?? null);

        $auditLogger->log('loyalty.redeemed', $request->user(), LoyaltyAccount::class, $account->id, [
            'points' => $data['points'],
            'sale_id' => $data['sale_id'] ?? null,
        ]);

        return $this->success('Puntos canjeados', $account);
    }
}
