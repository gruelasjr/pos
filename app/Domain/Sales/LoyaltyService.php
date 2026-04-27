<?php

namespace App\Domain\Sales;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyMovement;
use App\Models\Sale;
use Illuminate\Database\DatabaseManager;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

class LoyaltyService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function accrueFromSale(Sale $sale): ?LoyaltyAccount
    {
        if (!$sale->customer_id) {
            return null;
        }

        return $this->db->transaction(function () use ($sale) {
            $points = max(0, (int) floor((float) $sale->total_net));

            $account = LoyaltyAccount::firstOrCreate(
                ['customer_id' => $sale->customer_id],
                ['points_balance' => 0, 'lifetime_points' => 0, 'tier' => 'base']
            );

            $account->points_balance += $points;
            $account->lifetime_points += $points;
            $account->save();

            LoyaltyMovement::create([
                'loyalty_account_id' => $account->id,
                'sale_id' => $sale->id,
                'type' => 'earn',
                'points' => $points,
                'meta' => ['source' => 'sale_checkout'],
            ]);

            return $account;
        });
    }

    public function redeem(string $accountId, int $points, ?string $saleId = null): LoyaltyAccount
    {
        if ($points <= 0) {
            throw new UnprocessableEntityException('puntos_invalidos');
        }

        return $this->db->transaction(function () use ($accountId, $points, $saleId) {
            $account = LoyaltyAccount::query()->lockForUpdate()->findOrFail($accountId);

            if ($account->points_balance < $points) {
                throw new UnprocessableEntityException('saldo_puntos_insuficiente');
            }

            $account->points_balance -= $points;
            $account->save();

            LoyaltyMovement::create([
                'loyalty_account_id' => $account->id,
                'sale_id' => $saleId,
                'type' => 'redeem',
                'points' => -1 * $points,
                'meta' => ['source' => 'manual_redeem'],
            ]);

            return $account;
        });
    }
}
