<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Article;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Administrateur')) {
            return true;
        }
        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Article $article): bool
    {
        if ($article->status === 'published') {
            return true;
        }
        return $user && ($user->id === $article->author_id || $user->hasPermissionTo('articles_publish'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('articles_write_draft');
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->hasPermissionTo('articles_publish')) {
            return true;
        }
        return $user->hasPermissionTo('articles_write_draft') && $user->id === $article->author_id;
    }

    public function publish(User $user, Article $article): bool
    {
        if ($user->hasPermissionTo('articles_publish')) {
            return true;
        }
        return $user->hasPermissionTo('articles_publish_own') && $user->id === $article->author_id;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('articles_publish');
    }
}
