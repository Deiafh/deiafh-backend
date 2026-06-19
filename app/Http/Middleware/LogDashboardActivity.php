<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use App\Support\ActivityChanges;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogDashboardActivity
{
    /**
     * Log every successful mutating dashboard action (create/update/delete).
     * Reads/auth/broadcasting endpoints are ignored.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $this->maybeLog($request, $response);
        } catch (\Throwable $e) {
            // Logging must never break the actual request.
        }

        return $response;
    }

    private function maybeLog(Request $request, $response): void
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
        if ($status < 200 || $status >= 300) {
            return; // only successful actions
        }

        $path = $request->path();
        if (str_contains($path, 'dashboard/auth')
            || str_contains($path, 'dashboard/broadcasting')
            || str_contains($path, 'dashboard/presence')) {
            return; // handled elsewhere / not a business action
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return;
        }

        $key = $this->permissionKey($request);

        $properties = [];

        // Field-level old→new diff captured from model events during this request.
        $changes = app(ActivityChanges::class)->all();
        if ($changes) {
            $properties['changes'] = $changes;
        }

        // The raw submitted payload (sans sensitive fields) as a fallback/detail.
        $input = $request->except(['password', 'password_confirmation', '_method', 'remember']);
        if ($input) {
            $properties['request'] = $input;
        }

        ActivityLogger::record($user, $key, null, $request, $properties);
    }

    /**
     * Resolve the action from the route's permission middleware
     * (e.g. "permission:users.add" → "users.add").
     */
    private function permissionKey(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                continue;
            }

            $keys = explode('|', substr($middleware, strlen('permission:')));

            if (count($keys) === 1) {
                return $keys[0];
            }

            // Orders status route carries approve|cancel — pick by the new status.
            $newStatus = $request->input('status');
            if ($newStatus === 'rejected') {
                return collect($keys)->first(fn($k) => str_contains($k, 'cancel')) ?? $keys[0];
            }
            return collect($keys)->first(fn($k) => str_contains($k, 'approve')) ?? $keys[0];
        }

        return null;
    }
}
