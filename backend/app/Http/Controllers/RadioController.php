<?php

namespace App\Http\Controllers;

use App\Models\RadioShow;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class RadioController extends Controller
{
    public function index(Request $request)
    {
        $shows = RadioShow::with(['user', 'partner'])
            ->where('is_archived', false)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return response()->json($shows);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        // Enforce role permission
        if (!$user->hasPermissionTo('radio_publish') && !$user->hasPermissionTo('radio_publish_mandated')) {
            abort(403, 'Action interdite - Droits de publication radio insuffisants.');
        }

        $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'required|string',
            'audio_url' => 'required|string|url',
            'transcription' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'partner_id' => 'nullable|integer|exists:users,id',
        ]);

        $show = RadioShow::create([
            'title' => $request->title,
            'description' => $request->description,
            'audio_url' => $request->audio_url,
            'transcription' => $request->transcription,
            'scheduled_at' => $request->scheduled_at ?? now(),
            'is_archived' => false,
            'user_id' => $user->id,
            'partner_id' => $request->partner_id
        ]);

        AuditLogService::log(
            action: 'radio_show_added',
            description: "Nouvelle émission radio programmée : '{$show->title}'."
        );

        return response()->json($show, 201);
    }

    public function destroy(Request $request, RadioShow $radioShow)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('radio_manage_archives')) {
            abort(403, 'Seuls les rôles habilités peuvent archiver ou supprimer des émissions.');
        }

        $radioShow->update(['is_archived' => true]);

        AuditLogService::log(
            action: 'radio_show_archived',
            description: "Émission radio archivée : '{$radioShow->title}'."
        );

        return response()->json([
            'message' => 'Émission archivée avec succès.'
        ]);
    }
}
