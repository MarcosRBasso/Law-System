<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['store']);
        $this->middleware('permission:roles.update')->only(['update', 'assignPermissions']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    /**
     * Display a listing of roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Role::with('permissions');

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Filter by guard
            if ($request->has('guard') && !empty($request->guard)) {
                $query->where('guard_name', $request->guard);
            }

            // Sorting
            $sortField = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');
            
            if (in_array($sortField, ['name', 'guard_name', 'created_at'])) {
                $query->orderBy($sortField, $sortDirection);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $roles = $query->paginate($perPage);

            return response()->json([
                'data' => RoleResource::collection($roles->items()),
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar perfis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ], [
            'name.required' => 'O nome do perfil é obrigatório.',
            'name.unique' => 'Já existe um perfil com este nome.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas não existem.'
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            // Assign permissions if provided
            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            // Log activity
            activity('role_created')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties(['role_name' => $role->name])
                ->log('Perfil criado');

            return response()->json([
                'message' => 'Perfil criado com sucesso',
                'data' => new RoleResource($role->load('permissions'))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified role
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->load(['permissions', 'users']);

            return response()->json([
                'data' => new RoleResource($role)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ], [
            'name.required' => 'O nome do perfil é obrigatório.',
            'name.unique' => 'Já existe um perfil com este nome.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas não existem.'
        ]);

        try {
            // Prevent updating system roles
            if (in_array($role->name, ['super-admin', 'admin'])) {
                return response()->json([
                    'message' => 'Não é possível editar perfis do sistema'
                ], 403);
            }

            $role->update([
                'name' => $request->name,
                'description' => $request->description
            ]);

            // Update permissions if provided
            if ($request->has('permissions')) {
                if (is_array($request->permissions)) {
                    $permissions = Permission::whereIn('id', $request->permissions)->get();
                    $role->syncPermissions($permissions);
                } else {
                    $role->syncPermissions([]);
                }
            }

            // Log activity
            activity('role_updated')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties(['role_name' => $role->name])
                ->log('Perfil atualizado');

            return response()->json([
                'message' => 'Perfil atualizado com sucesso',
                'data' => new RoleResource($role->load('permissions'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            // Prevent deleting system roles
            if (in_array($role->name, ['super-admin', 'admin'])) {
                return response()->json([
                    'message' => 'Não é possível excluir perfis do sistema'
                ], 403);
            }

            // Check if role has users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'message' => 'Não é possível excluir um perfil que possui usuários associados'
                ], 422);
            }

            $roleName = $role->name;
            $role->delete();

            // Log activity
            activity('role_deleted')
                ->causedBy(auth()->user())
                ->withProperties(['role_name' => $roleName])
                ->log('Perfil excluído');

            return response()->json([
                'message' => 'Perfil excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available permissions
     */
    public function permissions(): JsonResponse
    {
        try {
            $permissions = Permission::all()->groupBy(function ($permission) {
                $parts = explode('.', $permission->name);
                return $parts[0] ?? 'general';
            });

            $grouped = [];
            foreach ($permissions as $module => $modulePermissions) {
                $grouped[] = [
                    'module' => $module,
                    'permissions' => PermissionResource::collection($modulePermissions)
                ];
            }

            return response()->json([
                'data' => $grouped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter permissões',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ], [
            'permissions.required' => 'Selecione pelo menos uma permissão.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas não existem.'
        ]);

        try {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissions);

            // Log activity
            activity('role_permissions_updated')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'role_name' => $role->name,
                    'permissions_count' => $permissions->count()
                ])
                ->log('Permissões do perfil atualizadas');

            return response()->json([
                'message' => 'Permissões atribuídas com sucesso',
                'data' => new RoleResource($role->load('permissions'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atribuir permissões',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_roles' => Role::count(),
                'roles_with_users' => Role::has('users')->count(),
                'roles_without_users' => Role::doesntHave('users')->count(),
                'total_permissions' => Permission::count(),
                'most_used_roles' => Role::withCount('users')
                    ->orderBy('users_count', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($role) {
                        return [
                            'name' => $role->name,
                            'users_count' => $role->users_count
                        ];
                    }),
                'permissions_by_module' => Permission::all()
                    ->groupBy(function ($permission) {
                        $parts = explode('.', $permission->name);
                        return $parts[0] ?? 'general';
                    })
                    ->map(function ($permissions, $module) {
                        return [
                            'module' => $module,
                            'count' => $permissions->count()
                        ];
                    })
                    ->values()
            ];

            return response()->json([
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}