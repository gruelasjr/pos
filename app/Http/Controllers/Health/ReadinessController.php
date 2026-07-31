<?php

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('SELECT 1')),
            'redis' => $this->redisCheck(),
            'caronte' => $this->configuredUrlCheck((string) config('caronte.url')),
            'providers' => $this->providerCheck(),
        ];

        $ready = collect($checks)->every(fn (array $check): bool => $check['ready']);

        return response()->json([
            'ready' => $ready,
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }

    /** @return array{ready: bool, detail?: string} */
    private function redisCheck(): array
    {
        $usesRedis = in_array(config('cache.default'), ['redis'], true)
            || in_array(config('queue.default'), ['redis'], true)
            || in_array(config('session.driver'), ['redis'], true);

        return $usesRedis
            ? $this->check(fn () => Redis::connection()->ping())
            : ['ready' => true, 'detail' => 'not_required'];
    }

    /** @return array{ready: bool, detail?: string} */
    private function configuredUrlCheck(string $url): array
    {
        $valid = filter_var($url, FILTER_VALIDATE_URL) !== false;
        $secure = ! app()->environment('production') || str_starts_with($url, 'https://');

        return [
            'ready' => $valid && $secure,
            'detail' => $valid && $secure ? 'configured' : 'invalid_or_insecure',
        ];
    }

    /** @return array{ready: bool, detail?: string} */
    private function providerCheck(): array
    {
        $drivers = collect(['payments', 'fiscal', 'receipt_printer', 'cash_drawer', 'barcode_scanner', 'erp'])
            ->mapWithKeys(fn (string $provider): array => [
                $provider => (string) config("pos_integrations.{$provider}.driver", ''),
            ]);
        $unsafe = $drivers->filter(fn (string $driver): bool => in_array($driver, ['', 'mock', 'stub'], true));

        if (! app()->environment('production')) {
            return ['ready' => true, 'detail' => 'development_drivers_allowed'];
        }

        return [
            'ready' => $unsafe->isEmpty(),
            'detail' => $unsafe->isEmpty() ? 'configured' : 'unsafe:' . $unsafe->keys()->join(','),
        ];
    }

    /** @return array{ready: bool, detail?: string} */
    private function check(callable $operation): array
    {
        try {
            $operation();

            return ['ready' => true];
        } catch (Throwable $exception) {
            report($exception);

            return ['ready' => false, 'detail' => 'unavailable'];
        }
    }
}
