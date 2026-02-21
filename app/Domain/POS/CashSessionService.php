<?php

namespace App\Domain\POS;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use Equidna\Toolkit\Exceptions\ConflictException;

class CashSessionService
{
    public function open(User $user, string $warehouseId, float $openingAmount): CashSession
    {
        $hasOpenSession = CashSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->exists();

        if ($hasOpenSession) {
            throw new ConflictException('caja_ya_abierta');
        }

        return CashSession::create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouseId,
            'status' => 'open',
            'opening_amount' => $openingAmount,
            'expected_amount' => $openingAmount,
            'opened_at' => now(),
        ]);
    }

    public function close(CashSession $session, float $closingAmount): CashSession
    {
        if ($session->status !== 'open') {
            throw new ConflictException('caja_no_abierta');
        }

        $session->load('movements');

        $delta = (float) $session->movements->sum('amount');
        $expected = round((float) $session->opening_amount + $delta, 2);

        $session->status = 'closed';
        $session->closing_amount = $closingAmount;
        $session->expected_amount = $expected;
        $session->difference_amount = round($closingAmount - $expected, 2);
        $session->closed_at = now();
        $session->save();

        return $session;
    }

    public function registerMovement(
        string $cashSessionId,
        string $type,
        float $amount,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null
    ): CashMovement {
        return CashMovement::create([
            'cash_session_id' => $cashSessionId,
            'type' => $type,
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
        ]);
    }
}
