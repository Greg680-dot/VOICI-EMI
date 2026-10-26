<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\LmsController;
use App\Http\Controllers\FactCheckController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\RadioController;
use App\Http\Controllers\LibraryController;

/*
|--------------------------------------------------------------------------
| API Routes - VoiCI-EMI
|--------------------------------------------------------------------------
*/

// --- 1. Authentification & Profil (Public) ---
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// --- 2. Contenus Publics ---
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
Route::get('/articles/{article}/comments', [ArticleController::class, 'comments']);

Route::get('/lms/courses', [LmsController::class, 'index']);
Route::get('/lms/courses/{course}', [LmsController::class, 'show']);

Route::get('/community/topics', [CommunityController::class, 'topics']);
Route::get('/community/topics/{topic}', [CommunityController::class, 'showTopic']);
Route::get('/community/blogs', [CommunityController::class, 'blogs']);
Route::get('/community/blogs/{blogPost}', [CommunityController::class, 'showBlog']);

Route::get('/radio/shows', [RadioController::class, 'index']);
Route::get('/library/resources', [LibraryController::class, 'index']);

// --- 3. Fact Checking Soumission Invité / Utilisateur (avec middleware quota pour invités) ---
Route::post('/fact-checking/submit', [FactCheckController::class, 'submit'])->middleware('guest.quota');
Route::post('/fact-checking/{factCheck}/analyze-ai', [FactCheckController::class, 'analyzeAi'])->middleware('guest.quota');

// --- 4. Routes Protégées (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profil
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/2fa/enable', [AuthController::class, 'enable2Fa']);
    Route::post('/auth/2fa/disable', [AuthController::class, 'disable2Fa']);

    // Admin & Rôles
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::post('/admin/users/{user}/suspend', [AdminController::class, 'suspend']);
    Route::post('/admin/users/{user}/roles', [AdminController::class, 'updateRoles']);
    Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs']);
    Route::get('/admin/system/config', [AdminController::class, 'getConfig']);
    Route::post('/admin/system/config', [AdminController::class, 'updateConfig']);

    // Actualités Décryptées
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
    Route::post('/articles/{article}/publish', [ArticleController::class, 'publish']);
    Route::post('/articles/{article}/comments', [ArticleController::class, 'storeComment']);
    Route::delete('/comments/{comment}', [ArticleController::class, 'destroyComment']);

    // LMS
    Route::get('/lms/my-courses', [LmsController::class, 'myCourses']);
    Route::get('/lms/trainer/learners', [LmsController::class, 'trainerLearners']);
    Route::post('/lms/courses/{course}/grant-certificate', [LmsController::class, 'grantCertificate']);
    Route::post('/lms/courses', [LmsController::class, 'storeCourse']);
    Route::post('/lms/courses/{course}/enroll', [LmsController::class, 'enroll']);
    Route::post('/lms/courses/{course}/chapters', [LmsController::class, 'storeChapter']);
    Route::post('/lms/chapters/{chapter}/lessons', [LmsController::class, 'storeLesson']);
    Route::get('/lms/lessons/{lesson}', [LmsController::class, 'showLesson']);
    Route::post('/lms/lessons/{lesson}/progress', [LmsController::class, 'updateProgress']);
    Route::post('/lms/lessons/{lesson}/quizzes', [LmsController::class, 'storeQuiz']);
    Route::post('/lms/quizzes/{quiz}/submit', [LmsController::class, 'submitQuiz']);
    Route::get('/lms/courses/{course}/certificate', [LmsController::class, 'downloadCertificate']);

    // Fact Checking
    Route::post('/fact-checking/{factCheck}/validate', [FactCheckController::class, 'validateVerdict']);
    Route::get('/fact-checking/history', [FactCheckController::class, 'history']);

    // Communauté
    Route::post('/community/topics', [CommunityController::class, 'storeTopic']);
    Route::post('/community/topics/{topic}/posts', [CommunityController::class, 'storePost']);
    Route::post('/community/topics/{topic}/lock-pin', [CommunityController::class, 'lockOrPinTopic']);
    Route::post('/community/blogs', [CommunityController::class, 'storeBlog']);
    Route::post('/community/report', [CommunityController::class, 'reportContent']);
    Route::get('/community/reports', [CommunityController::class, 'reports']);
    Route::post('/community/reports/{report}/resolve', [CommunityController::class, 'resolveReport']);
    Route::get('/community/messages', [CommunityController::class, 'messages']);
    Route::post('/community/messages', [CommunityController::class, 'storeMessage']);

    // Web Radio
    Route::post('/radio/shows', [RadioController::class, 'store']);
    Route::delete('/radio/shows/{radioShow}', [RadioController::class, 'destroy']);

    // Bibliothèque
    Route::post('/library/resources', [LibraryController::class, 'store']);
    Route::post('/library/resources/{resource}/validate', [LibraryController::class, 'validateResource']);

});
