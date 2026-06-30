<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestUsageLogger
{
    /**
     * Log safe request metadata for production usage analysis.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldSkip($request)) {
            Log::info('request.completed', [
                'timestamp' => now()->toIso8601String(),
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'user_id' => $request->user()?->id,
                'clinic_id' => $this->resolveClinicId($request),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        if ($path === 'up' || str_starts_with($path, 'build/') || str_starts_with($path, 'vendor/')) {
            return true;
        }

        return (bool) preg_match('/\.(css|js|map|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf)$/i', $path);
    }

    private function resolveClinicId(Request $request): ?int
    {
        $route = $request->route();
        $clinic = $route?->parameter('clinic');

        if ($clinic instanceof Clinic) {
            return $clinic->id;
        }

        if (is_numeric($clinic)) {
            return (int) $clinic;
        }

        if ($request->has('clinic_id') && is_numeric($request->input('clinic_id'))) {
            return (int) $request->input('clinic_id');
        }

        if ($request->has('clinicId') && is_numeric($request->input('clinicId'))) {
            return (int) $request->input('clinicId');
        }

        return null;
    }
}