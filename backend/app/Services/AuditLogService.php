<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log(string $action, string $description, ?int $targetUserId = null, array $metadata = []): AuditLog
    {
        // Default to user 1 (seeded SA) if no user is authenticated (e.g. CLI commands or guest actions)
        $actorId = Auth::id() ?? 1;

        return AuditLog::create([
            'user_id' => $actorId,
            'target_user_id' => $targetUserId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
