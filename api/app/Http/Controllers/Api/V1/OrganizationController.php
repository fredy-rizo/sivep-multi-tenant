<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Solo el super administrador puede listar organizaciones.');

        $organizations = Organization::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('nit', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $organizations]);
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Solo el super administrador puede ver organizaciones.');

        return response()->json([
            'data' => $organization->loadCount(['users', 'animals', 'lots']),
        ]);
    }
}
