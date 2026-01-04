<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromAppKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-App-Key');

        if (! $appKey) {
            // No tenant header provided; continue without a tenant.
            $request->attributes->set('tenant', null);

            return $next($request);
        }

        $tenant = Tenant::where('app_key', $appKey)->first();

        if (! $tenant || $tenant->status !== 'active') {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid or inactive tenant.',
            ], 404);
        }

        // Attach the resolved tenant to the request for downstream use.
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
