<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReadinessTest extends TestCase
{
    public function testReadinessReportsConfiguredLocalDependenciesWithoutExposingSecrets(): void
    {
        config()->set('caronte.url', 'https://caronte.test');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('session.driver', 'array');

        $response = $this->getJson('/ready');

        $response->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonPath('checks.database.ready', true)
            ->assertJsonPath('checks.redis.detail', 'not_required')
            ->assertJsonPath('checks.caronte.detail', 'configured')
            ->assertJsonMissing(['token', 'secret', 'exception']);
    }

    public function testReadinessFailsClosedWhenCaronteIsNotConfigured(): void
    {
        config()->set('caronte.url', '');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('session.driver', 'array');

        $this->getJson('/ready')
            ->assertServiceUnavailable()
            ->assertJsonPath('ready', false)
            ->assertJsonPath('checks.caronte.ready', false)
            ->assertJsonPath('checks.caronte.detail', 'invalid_or_insecure');
    }
}
