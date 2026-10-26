<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'account_type' => 'required|string|in:citoyen,apprenant,partenaire',
        ]);

        $partnerStatus = $request->input('account_type') === 'partenaire' ? 'pending' : 'none';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
            'partner_status' => $partnerStatus,
        ]);

        if ($request->input('account_type') === 'citoyen') {
            $user->assignRole('Citoyen');
            $user->update(['current_role' => 'Citoyen']);
        } elseif ($request->input('account_type') === 'apprenant') {
            $user->assignRole('Apprenant');
            $user->update(['current_role' => 'Apprenant']);
        } else {
            $user->assignRole('Partenaire');
            $user->update(['current_role' => 'Partenaire']);
        }

        AuditLogService::log('user_registration', "Inscription d'un nouvel utilisateur ({$request->input('account_type')}) : {$user->email}.", $user->id);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'message' => 'Ce compte a été suspendu par un administrateur.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogService::log('user_login', "Connexion de l'utilisateur {$user->email}.", $user->id);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        AuditLogService::log('user_logout', "Déconnexion de l'utilisateur.", $request->user()->id);

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function profile(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:191',
            'profile_metadata' => 'sometimes|array',
        ]);

        $user->update($request->only('name', 'profile_metadata'));

        return new UserResource($user);
    }

    public function enable2Fa(Request $request)
    {
        $user = $request->user();
        
        $secret = '2FA-SECRET-' . strtoupper(bin2hex(random_bytes(16)));
        $recovery = implode('-', str_split(strtoupper(bin2hex(random_bytes(16))), 4));

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt($recovery),
            'two_factor_confirmed_at' => now(),
        ]);

        AuditLogService::log('2fa_enable', "Activation de la double authentification (2FA).", $user->id);

        return response()->json([
            'message' => 'Double authentification activée avec succès.',
            'secret' => $secret,
            'recovery_code' => $recovery,
        ]);
    }

    public function disable2Fa(Request $request)
    {
        $user = $request->user();

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        AuditLogService::log('2fa_disable', "Désactivation de la double authentification (2FA).", $user->id);

        return response()->json([
            'message' => 'Double authentification désactivée avec succès.'
        ]);
    }
}
