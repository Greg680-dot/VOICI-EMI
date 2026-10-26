<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($articles);
    }

    public function show(Request $request, Article $article)
    {
        Gate::authorize('view', $article);
        return response()->json($article);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Article::class);

        $request->validate([
            'title' => 'required|string|max:191',
            'content' => 'required|string',
            'summary_falc' => 'nullable|string',
        ]);

        $article = Article::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'content' => $request->content,
            'author_id' => $request->user()->id,
            'status' => 'draft',
            'summary_falc' => $request->summary_falc,
            'reliability_score' => 'non_verifie',
            'is_fact_checked' => false
        ]);

        return response()->json($article, 201);
    }

    public function update(Request $request, Article $article)
    {
        Gate::authorize('update', $article);

        $request->validate([
            'title' => 'sometimes|string|max:191',
            'content' => 'sometimes|string',
            'summary_falc' => 'sometimes|string',
            'reliability_score' => 'sometimes|string|in:non_verifie,faible,moyen,eleve',
            'is_fact_checked' => 'sometimes|boolean',
        ]);

        $article->update($request->only('title', 'content', 'summary_falc', 'reliability_score', 'is_fact_checked'));

        return response()->json($article);
    }

    public function publish(Request $request, Article $article)
    {
        Gate::authorize('publish', $article);

        $article->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        return response()->json([
            'message' => 'Article publié avec succès.',
            'article' => $article
        ]);
    }

    public function comments(Article $article)
    {
        $comments = $article->comments()
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    public function storeComment(Request $request, Article $article)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $comment = $article->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'status' => 'approved' // Automatically approved unless reported later
        ]);

        return response()->json($comment, 201);
    }

    public function destroyComment(Request $request, Comment $comment)
    {
        // Require articles_moderate_comments
        if (!$request->user()->hasPermissionTo('articles_moderate_comments')) {
            abort(403, 'Action interdite - Droits de modération requis.');
        }

        $comment->delete();

        return response()->json([
            'message' => 'Commentaire supprimé par le modérateur.'
        ]);
    }
}
