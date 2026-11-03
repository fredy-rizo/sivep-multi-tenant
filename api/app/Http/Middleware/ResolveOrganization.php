<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la organización (tenant) de la petición.
 *
 * - Usuarios normales: siempre su propia organización.
 * - super_admin: puede indicar X-Organization-Id (o ?organization_id=) para
 *   dar soporte a un tenant concreto.
 *
 * Debe ejecutarse DESPUÉS de auth:sanctum.
 */
class ResolveOrganization
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ($user->hasRole('super_admin')) {
            $requestedId = $request->header('X-Organization-Id')
                ?? $request->query('organization_id');

            if (! $requestedId) {
                return response()->json([
                    'message' => 'Selecciona una organización con X-Organization-Id.',
                    'code' => 'ORGANIZATION_REQUIRED',
                ], 400);
            }

            $organization = Organization::query()
                ->where('is_active', true)
                ->find($requestedId);

            if (! $organization) {
                return response()->json([
                    'message' => 'Organización no encontrada o inactiva.',
                    'code' => 'ORGANIZATION_NOT_FOUND',
                ], 404);
            }

            $this->context->set($organization);

            return $next($request);
        }

        if (! $user->organization_id) {
            return response()->json([
                'message' => 'El usuario no tiene una organización asignada.',
                'code' => 'ORGANIZATION_NOT_ASSIGNED',
            ], 403);
        }

        $organization = $user->organization;

        if (! $organization || ! $organization->is_active) {
            return response()->json([
                'message' => 'La organización está inactiva.',
                'code' => 'ORGANIZATION_INACTIVE',
            ], 403);
        }

        $this->context->set($organization);

        return $next($request);
    }
}
