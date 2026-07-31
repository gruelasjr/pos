<?php

namespace App\Models;

/**
 * Immutable role catalogue owned by Caronte, kept as constants for policies.
 */
final class Role
{
    public const ADMIN = 'pos-admin';
    public const SELLER = 'pos-seller';
    public const AUDITOR = 'pos-auditor';

    public const ALL = [self::ADMIN, self::SELLER, self::AUDITOR];

    private function __construct()
    {
    }
}
