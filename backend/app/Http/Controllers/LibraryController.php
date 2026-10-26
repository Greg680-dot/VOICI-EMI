<?php

namespace App\Http\Controllers;

use App\Models\LibraryResource;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        if ($user && $user->hasPermissionTo('library_download')) {
            // Staff / Authenticated can view all validated resources
            $resources = LibraryResource::where('is_validated', true)->get();
        } else {
            // Guests / Standard users view public validated resources only
            $resources = LibraryResource::where('is_validated', true)
                ->where('is_public', true)
                ->get();
        }

        return response()->json($resources);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        Gate::authorize('create', LibraryResource::class);

        $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'required|string',
            'file_path' => 'required|string',
            'file_type' => 'required|string|in:guide,poster,sheet,other',
            'is_public' => 'boolean'
        ]);

        $resource = LibraryResource::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $request->file_path,
            'file_type' => $request->file_type,
            'uploaded_by' => $user->id,
            'is_validated' => false,
            'is_public' => $request->input('is_public', true)
        ]);

        AuditLogService::log(
            action: 'library_resource_upload',
            description: "Nouveau document déposé pour validation : '{$resource->title}' par {$user->email}.",
            targetUserId: $user->id
        );

        return response()->json($resource, 201);
    }

    public function validateResource(Request $request, LibraryResource $resource)
    {
        Gate::authorize('validate', $resource);

        $resource->update([
            'is_validated' => true,
            'validated_by' => $request->user()->id
        ]);

        AuditLogService::log(
            action: 'library_resource_validated',
            description: "Document validé pour la bibliothèque publique : '{$resource->title}'."
        );

        return response()->json([
            'message' => 'Ressource validée et publiée.',
            'resource' => $resource
        ]);
    }
}
