<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // Translation CRUD operations
    Route::prefix('translations')->group(function () {
        Route::get('/', [TranslationController::class, 'index']);
        Route::post('/', [TranslationController::class, 'store']);
        Route::get('/search', [TranslationController::class, 'search']);
        Route::get('/{translation}', [TranslationController::class, 'show']);
        Route::put('/{translation}', [TranslationController::class, 'update']);
        Route::patch('/{translation}', [TranslationController::class, 'update']);
        Route::delete('/{translation}', [TranslationController::class, 'destroy']);
    });

    // Tag management
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::get('/all', [TagController::class, 'all']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{tag}', [TagController::class, 'show']);
        Route::put('/{tag}', [TagController::class, 'update']);
        Route::patch('/{tag}', [TagController::class, 'update']);
        Route::delete('/{tag}', [TagController::class, 'destroy']);
    });

    // Export translations
    Route::prefix('export')->group(function () {
        Route::get('/', [ExportController::class, 'allLocales']);
        Route::get('/locales', [ExportController::class, 'locales']);
        Route::get('/tags', [ExportController::class, 'byTags']);
        Route::get('/{locale}', [ExportController::class, 'singleLocale']);
    });
});

// Public export routes (for frontend consumption)
Route::prefix('public/export')->group(function () {
    Route::get('/', [ExportController::class, 'allLocales']);
    Route::get('/locales', [ExportController::class, 'locales']);
    Route::get('/tags', [ExportController::class, 'byTags']);
    Route::get('/{locale}', [ExportController::class, 'singleLocale']);
});
