<?php

namespace App\Http\Controllers;

use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Models\BlogPost;
use App\Models\Report;
use App\Models\Comment;
use App\Models\PrivateMessage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function topics(Request $request)
    {
        $topics = ForumTopic::with('user')->orderBy('is_pinned', 'desc')->orderBy('updated_at', 'desc')->get();
        return response()->json($topics);
    }

    public function showTopic(Request $request, ForumTopic $topic)
    {
        $topic->load(['user', 'posts.user']);
        return response()->json($topic);
    }

    public function storeTopic(Request $request)
    {
        Gate::authorize('create', ForumTopic::class);

        $request->validate([
            'title' => 'required|string|max:191',
            'content' => 'required|string',
        ]);

        $topic = ForumTopic::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'user_id' => $request->user()->id,
            'is_locked' => false,
            'is_pinned' => false
        ]);

        $topic->posts()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);

        return response()->json($topic->load('user'), 201);
    }

    public function storePost(Request $request, ForumTopic $topic)
    {
        Gate::authorize('create', ForumTopic::class);

        if ($topic->is_locked) {
            abort(403, 'Ce sujet est verrouillé.');
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $post = $topic->posts()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);

        return response()->json($post->load('user'), 201);
    }

    public function lockOrPinTopic(Request $request, ForumTopic $topic)
    {
        Gate::authorize('lockOrPin', $topic);

        $request->validate([
            'is_locked' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
        ]);

        $topic->update($request->only('is_locked', 'is_pinned'));

        AuditLogService::log(
            action: 'forum_moderation',
            description: "Modération du sujet #{$topic->id} ({$topic->title}) : verrouillage=" . ($topic->is_locked ? 'oui' : 'non') . ", épinglé=" . ($topic->is_pinned ? 'oui' : 'non') . "."
        );

        return response()->json($topic);
    }

    public function blogs(Request $request)
    {
        $blogs = BlogPost::with('user')->where('status', 'published')->orderBy('created_at', 'desc')->get();
        return response()->json($blogs);
    }

    public function showBlog(Request $request, BlogPost $blogPost)
    {
        Gate::authorize('view', $blogPost);
        return response()->json($blogPost->load('user'));
    }

    public function storeBlog(Request $request)
    {
        Gate::authorize('create', BlogPost::class);

        $request->validate([
            'title' => 'required|string|max:191',
            'content' => 'required|string',
        ]);

        $post = BlogPost::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'content' => $request->content,
            'user_id' => $request->user()->id,
            'status' => 'published',
        ]);

        return response()->json($post, 201);
    }

    public function reportContent(Request $request)
    {
        Gate::authorize('create', Report::class);

        $request->validate([
            'reportable_type' => 'required|string|in:comment,forum_post,blog_post',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        $typeMap = [
            'comment' => Comment::class,
            'forum_post' => ForumPost::class,
            'blog_post' => BlogPost::class,
        ];

        $modelClass = $typeMap[$request->reportable_type];
        $target = $modelClass::findOrFail($request->reportable_id);

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $request->reportable_id,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        $reportsCount = Report::where('reportable_type', $modelClass)
            ->where('reportable_id', $request->reportable_id)
            ->where('status', 'pending')
            ->count();

        if ($reportsCount >= 3) {
            $target->update(['status' => 'reported']);
        }

        return response()->json([
            'message' => 'Contenu signalé avec succès.',
            'report' => $report
        ], 201);
    }

    public function reports(Request $request)
    {
        Gate::authorize('viewAny', Report::class);

        $reports = Report::with(['reporter', 'reportable'])->where('status', 'pending')->get();

        return response()->json($reports);
    }

    public function resolveReport(Request $request, Report $report)
    {
        Gate::authorize('resolve', $report);

        $request->validate([
            'action' => 'required|string|in:dismiss,delete',
            'resolution_notes' => 'nullable|string'
        ]);

        $action = $request->input('action');

        if ($action === 'delete') {
            $report->reportable?->delete();
            $report->update([
                'status' => 'resolved',
                'moderator_id' => $request->user()->id,
                'resolution_notes' => $request->resolution_notes ?? 'Contenu supprimé suite au signalement.'
            ]);
        } else {
            $report->reportable?->update(['status' => 'approved']);
            $report->update([
                'status' => 'dismissed',
                'moderator_id' => $request->user()->id,
                'resolution_notes' => $request->resolution_notes ?? 'Signalement rejeté par le modérateur.'
            ]);
        }

        AuditLogService::log(
            action: 'report_resolution',
            description: "Signalement #{$report->id} résolu avec l'action : {$action}.",
            targetUserId: $report->reporter_id
        );

        return response()->json([
            'message' => 'Signalement résolu avec succès.'
        ]);
    }

    public function messages(Request $request)
    {
        $userId = $request->user()->id;

        $messages = PrivateMessage::with(['sender', 'receiver'])
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function storeMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'required|string',
        ]);

        $message = PrivateMessage::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content
        ]);

        return response()->json($message->load(['sender', 'receiver']), 201);
    }
}
