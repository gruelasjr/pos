<?php

return [
    /**
     * When true, do not redact sensitive fields in logs (use with caution).
     */
    'log_sensitive' => (bool) env('LOG_SENSITIVE', false),

    /**
     * API bearer tokens should be short lived for POS terminals.
     */
    /**
     * Receipt customer-registration links are public, single-use tokens.
     */
    'customer_registration_token_ttl_days' => (int) env('CUSTOMER_REGISTRATION_TOKEN_TTL_DAYS', 30),
];
