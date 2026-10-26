<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_shows', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('audio_url');
            $table->longText('transcription')->nullable(); // Accessibility transcription
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('set null'); // FK to a Partner user
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_shows');
    }
};
