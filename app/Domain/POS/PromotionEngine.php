<?php

namespace App\Domain\POS;

use App\Models\Cart;
use App\Models\Promotion;

class PromotionEngine
{
    public function apply(Cart $cart): array
    {
        $subtotal = (float) $cart->total_gross;

        $promotions = Promotion::query()
            ->active()
            ->where('min_subtotal', '<=', $subtotal)
            ->orderBy('priority')
            ->get();

        $applied = [];
        $discount = 0.0;

        foreach ($promotions as $promotion) {
            $candidate = $this->computeDiscount($promotion->type, (float) $promotion->value, $subtotal);
            if ($candidate <= 0) {
                continue;
            }

            $applied[] = [
                'promotion_id' => $promotion->id,
                'name' => $promotion->name,
                'discount' => $candidate,
            ];

            $discount += $candidate;

            if (!$promotion->stackable) {
                break;
            }
        }

        return [
            'promotion_discount' => round(min($discount, $subtotal), 2),
            'applied_promotions' => $applied,
        ];
    }

    protected function computeDiscount(string $type, float $value, float $subtotal): float
    {
        if ($type === 'percentage') {
            return round(max(0.0, $subtotal * ($value / 100)), 2);
        }

        return round(max(0.0, $value), 2);
    }
}
