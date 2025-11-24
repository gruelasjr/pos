<?php

/**
 * Security helpers for redaction and sensitive data handling.
 *
 * PHP 8.1+
 *
 * @package App\Support
 */

namespace App\Support;

class SecurityHelpers
{
    /**
     * Redact sensitive values from an array payload.
     *
     * Keys that match the default list will be masked. If the
     * `security.log_sensitive` config is true this returns the
     * original payload unchanged.
     *
     * @param  array $payload
     * @return array
     */
    public static function redact(array $payload): array
    {
        if ((bool) config('security.log_sensitive', false)) {
            return $payload;
        }

        $defaults = [
            'password',
            'pass',
            'card_number',
            'card',
            'cvv',
            'cvc',
            'ssn',
            'payment_details',
            'token',
            'access_token',
            'refresh_token',
        ];

        $redacted = [];

        foreach ($payload as $k => $v) {
            $lower = strtolower((string) $k);

            $shouldRedact = false;

            foreach ($defaults as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $shouldRedact = true;
                    break;
                }
            }

            if ($shouldRedact) {
                if (is_string($v) && strlen($v) > 8) {
                    $redacted[$k] = substr($v, 0, 4) . '...' . substr($v, -4);
                } else {
                    $redacted[$k] = 'REDACTED';
                }
            } elseif (is_array($v)) {
                $redacted[$k] = self::redact($v);
            } else {
                $redacted[$k] = $v;
            }
        }

        return $redacted;
    }
}
