<?php

/**
 * Inertia middleware providing shared props and versioning.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Middleware
 */

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia middleware that exposes shared properties and versioning.
 */
/**
 * Middleware: handle Inertia requests.
 *
 * Adjusts Inertia-specific request/response behavior for the application.
 *
 * @package   App\Http\Middleware
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
            ],
        ]);
    }
}
