<?php

namespace Tests;

use Equidna\BeeHive\Tenancy\Resolvers\StaticTenantResolver;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeCaronteAuthentication;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('bee-hive.resolver', StaticTenantResolver::class);
        config()->set('bee-hive.static_tenant_id', 'tenant-test');
        $context = new TenantContext();
        $context->set('tenant-test');
        app()->instance(TenantContext::class, $context);
        app('router')->aliasMiddleware('caronte.session', FakeCaronteAuthentication::class);
        app('router')->aliasMiddleware('caronte.application', FakeCaronteAuthentication::class);
        app(\Illuminate\Contracts\Http\Kernel::class)
            ->prependToMiddlewarePriority(FakeCaronteAuthentication::class);
    }

    protected function useTenant(string $tenantId = 'tenant-test'): void
    {
        config()->set('bee-hive.static_tenant_id', $tenantId);
        $context = new TenantContext();
        $context->set($tenantId);
        app()->instance(TenantContext::class, $context);
    }

    protected function tearDown(): void
    {
        FakeCaronteAuthentication::reset();
        parent::tearDown();
    }
}
