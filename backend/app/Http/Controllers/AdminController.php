<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Http\Resources\UserResource;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = User::with('roles')->get();

        return UserResource::collection($users);
    }

    public function suspend(Request $request, User $user)
    {
        Gate::authorize('suspend', $user);

        $request->validate([
            'status' => 'required|string|in:active,suspended',
        ]);

        $status = $request->input('status');
        $user->update(['status' => $status]);

        AuditLogService::log(
            action: $status === 'suspended' ? 'user_suspension' : 'user_activation',
            description: $status === 'suspended' ? "Suspension du compte de {$user->email}." : "Activation du compte de {$user->email}.",
            targetUserId: $user->id
        );

        return response()->json([
            'message' => $status === 'suspended' ? 'Utilisateur suspendu avec succès.' : 'Compte utilisateur réactivé.',
            'user' => new UserResource($user)
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        Gate::authorize('manageRoles', $user);

        // Security check: Never allow modifying the role of a Super Administrator
        if ($user->hasRole('Super Administrateur')) {
            abort(403, 'Action interdite : Le rôle du Super Administrateur ne peut pas être modifié.');
        }

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $roles = $request->input('roles');

        // Security check: Only an existing Super Administrator can assign the Super Administrator role
        if (in_array('Super Administrateur', $roles) && !$request->user()->hasRole('Super Administrateur')) {
            abort(403, 'Action interdite : Seul un Super Administrateur peut attribuer le rôle de Super Administrateur.');
        }

        $user->syncRoles($roles);

        $user->update(['current_role' => $roles[0] ?? null]);

        AuditLogService::log(
            action: 'role_change',
            description: "Changement de rôles pour l'utilisateur {$user->email} : " . implode(', ', $roles) . ".",
            targetUserId: $user->id,
            metadata: ['roles' => $roles]
        );

        return response()->json([
            'message' => 'Rôles mis à jour avec succès.',
            'user' => new UserResource($user)
        ]);
    }

    public function auditLogs(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Super Administrateur')) {
            $logs = AuditLog::with(['user', 'targetUser'])->orderBy('created_at', 'desc')->get();
        } elseif ($user->hasPermissionTo('admin_view_app_logs')) {
            $logs = AuditLog::with(['user', 'targetUser'])
                ->whereNotIn('action', ['system_config_update', 'backup_restoration'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            abort(403, 'Accès interdit aux logs d\'audit.');
        }

        return response()->json($logs);
    }

    public function getConfig(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Administrateur')) {
            abort(403, 'Seul le Super Administrateur peut gérer la configuration système.');
        }

        return response()->json([
            'openai_model' => config('services.openai.model'),
            'app_locale' => config('app.locale'),
            'app_timezone' => config('app.timezone'),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Administrateur')) {
            abort(403, 'Seul le Super Administrateur peut modifier la configuration système.');
        }

        $request->validate([
            'openai_model' => 'required|string',
        ]);

        AuditLogService::log(
            action: 'system_config_update',
            description: "Mise à jour de la configuration système : modèle OpenAI défini sur {$request->openai_model}."
        );

        return response()->json([
            'message' => 'Configuration système mise à jour.',
            'openai_model' => $request->openai_model
        ]);
    }
}
