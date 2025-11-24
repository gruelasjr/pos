<?php

declare(strict_types=1);

use App\Support\SecurityHelpers;
use PHPUnit\Framework\TestCase;

final class SecurityHelpersTest extends TestCase
{
    public function test_redact_masks_sensitive_keys(): void
    {
        $payload = [
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'name' => 'Alice',
            'nested' => [
                'payment_details' => ['token' => 'secret-token']
            ]
        ];

        $redacted = SecurityHelpers::redact($payload);

        $this->assertArrayHasKey('card_number', $redacted);
        $this->assertStringContainsString('...', (string) $redacted['card_number']);
        $this->assertSame('REDACTED', $redacted['cvv']);
        $this->assertSame('Alice', $redacted['name']);
        $this->assertIsArray($redacted['nested']);
    }
}
