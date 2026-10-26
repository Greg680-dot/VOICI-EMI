<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('draft'); // draft, pending_publish, published
            $table->timestamp('published_at')->nullable();
            $table->string('reliability_score')->default('non_verifie'); // non_verifie, faible, moyen, eleve
            $table->boolean('is_fact_checked')->default(false);
            $table->text('summary_falc')->nullable(); // Simple French (FALC)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
