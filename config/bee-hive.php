<?php

use Ometra\Caronte\Tenancy\Resolvers\CaronteTenantResolver;

return [
    'tenant_key' => env('BEE_HIVE_TENANT_KEY', 'tenant_id'),
    'static_tenant_id' => env('BEE_HIVE_STATIC_TENANT_ID'),
    'resolver' => env('BEE_HIVE_RESOLVER', CaronteTenantResolver::class),
    'errors' => ['status' => 404],
    'logging' => ['enabled' => true, 'level' => 'warning', 'sample_rate' => 1.0],
];
