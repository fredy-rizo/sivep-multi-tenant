<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gating por feature del plan contratado.
 *
 * Uso: ->middleware(['auth:sanctum', 'organization', 'feature:livestock.core'])
 *
 * Responde 402 (Payment Required) porque el motivo es comercial, no de
 * permisos. El super_admin siempre pasa.
 */
class EnsureFeature
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user?->hasRole('super_admin')) {
            return $next($request);
        }

        $organizationId = $this->context->id();

        if (! $organizationId) {
            return response()->json([
                'message' => 'No hay una organización activa para validar el plan.',
                'code' => 'ORGANIZATION_REQUIRED',
            ], 400);
        }

        $hasFeature = DB::table('subscriptions as s')
            ->join('plan_feature as pf', 'pf.plan_id', '=', 's.plan_id')
            ->where('s.organization_id', $organizationId)
            ->whereIn('s.status', ['active', 'trialing'])
            ->whereDate('s.current_period_end', '>=', now()->toDateString())
            ->where('pf.feature_code', $feature)
            ->exists();

        if (! $hasFeature) {
            return response()->json([
                'message' => 'Tu plan no incluye esta función.',
                'code' => 'FEATURE_NOT_INCLUDED',
                'feature' => $feature,
            ], 402);
        }

        return $next($request);
    }
}
