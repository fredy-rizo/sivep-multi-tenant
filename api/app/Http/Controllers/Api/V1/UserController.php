<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(protected TenantContext $tenant) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->where('organization_id', $this->tenant->id())
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query
                ->whereHas('roles', fn ($query) => $query->where('name', $request->input('role'))))
            ->when($request->filled('is_active'), fn ($query) => $query
                ->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function summary(): JsonResponse
    {
        $base = User::query()->where('organization_id', $this->tenant->id());

        return response()->json([
            'data' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('is_active', true)->count(),
                'inactive' => (clone $base)->where('is_active', false)->count(),
                'admins' => (clone $base)->role('admin_granja')->count(),
                'field_staff' => (clone $base)->role(['veterinario', 'operario'])->count(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $user = User::create([
            ...$data,
            'organization_id' => $this->tenant->id(),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($role);
        $user->load('roles');

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        $this->ensureSameOrganization($user);

        return (new UserResource($user->load('roles')))->response();
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureSameOrganization($user);

        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role']);

        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        if ($request->user()->is($user)) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => 'No puedes desactivar tu propia cuenta.',
                ]);
            }

            if ($role && ! $user->hasRole($role)) {
                throw ValidationException::withMessages([
                    'role' => 'No puedes cambiar tu propio rol.',
                ]);
            }
        }

        $user->update($data);

        if ($role) {
            $user->syncRoles([$role]);
        }

        return (new UserResource($user->load('roles')))->response();
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->ensureSameOrganization($user);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Usuario desactivado.']);
    }

    private function ensureSameOrganization(User $user): void
    {
        abort_unless(
            (int) $user->organization_id === (int) $this->tenant->id(),
            404,
            'Usuario no encontrado.',
        );
    }
}
