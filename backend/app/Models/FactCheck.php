<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'content_type', 'content_value', 'ip_address', 'status',
    'ai_score', 'ai_analysis', 'fact_checker_id', 'final_verdict', 'reliability_rating'
])]
class FactCheck extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ai_score' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function factChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fact_checker_id');
    }
}
