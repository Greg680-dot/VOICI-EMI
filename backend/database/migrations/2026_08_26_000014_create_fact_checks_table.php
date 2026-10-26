<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable for Guest submissions
            $table->string('content_type'); // text, link, image, video, document
            $table->text('content_value'); // The raw text, URL, or file path
            $table->string('ip_address', 45)->nullable(); // For Guest quota limiting
            $table->string('status')->default('submitted'); // submitted, analyzing, analyzed, validated, published
            $table->decimal('ai_score', 5, 2)->nullable(); // 0 to 100 credibility index
            $table->text('ai_analysis')->nullable(); // IA detailed review
            $table->foreignId('fact_checker_id')->nullable()->constrained('users')->onDelete('set null'); // Validator
            $table->text('final_verdict')->nullable(); // Human validation notes
            $table->string('reliability_rating')->default('non_verifie'); // vrai, faux, trompeur, non_verifie
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_checks');
    }
};
