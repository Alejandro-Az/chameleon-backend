<?php
// routes/api/v1/events.php
use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin auth)
Route::get('events/{slug}', [EventController::class, 'show']);

// Rutas del master (requieren auth)
Route::middleware(['auth:api', 'user.active', 'user.verified', 'jwt.not_revoked'])->group(function () {
    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{slug}', [EventController::class, 'update']);
    Route::delete('events/{slug}', [EventController::class, 'destroy']);
    Route::get('events/{slug}/modules', [EventController::class, 'getModules']);
    Route::put('events/{slug}/modules', [EventController::class, 'updateModules']);
});
