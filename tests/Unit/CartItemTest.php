<?php

declare(strict_types=1);

use App\Models\CartItem;
use PHPUnit\Framework\TestCase;

final class CartItemTest extends TestCase
{
    public function test_compute_subtotal_clamps_to_zero(): void
    {
        $item = new CartItem();
        $item->unit_price = 10.0;
        $item->quantity = 1;
        $item->discount = 20.0; // larger than unit_price * quantity

        $item->computeSubtotal();

        $this->assertSame(0.0, (float) $item->subtotal);
    }

    public function test_compute_subtotal_normal_case(): void
    {
        $item = new CartItem();
        $item->unit_price = 5.5;
        $item->quantity = 2;
        $item->discount = 1.0;

        $item->computeSubtotal();

        $this->assertSame(10.0, (float) $item->subtotal);
    }
}
