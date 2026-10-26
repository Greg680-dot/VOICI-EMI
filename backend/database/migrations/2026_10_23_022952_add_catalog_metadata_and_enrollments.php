<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('category')->default('Éducation aux Médias')->after('status');
            $table->string('level')->default('Tous niveaux')->after('category');
            $table->string('duration')->default('2h 30min')->after('level');
            $table->string('icon')->default('📚')->after('duration');
            $table->string('badge_color')->default('emerald')->after('icon');
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('status')->default('in_progress');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['category', 'level', 'duration', 'icon', 'badge_color']);
        });
    }
};
