<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->string('title');
            $table->string('content_type')->default('text'); // text, audio, video
            $table->longText('content');
            $table->longText('content_simplified')->nullable(); // FALC
            $table->string('video_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->longText('transcription')->nullable(); // Transcription for media accessibility
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
