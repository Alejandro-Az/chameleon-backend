<?php
// routes/api/v1/admin.templates.php
use App\Http\Controllers\Api\V1\TemplateController;
use Illuminate\Support\Facades\Route;

// GET templates es público (el wizard lo necesita sin login)
Route::get('templates', [TemplateController::class, 'index']);

// CRUD de templates solo para admin (role:admin via Spatie middleware)
Route::middleware(['auth:api', 'user.active', 'user.verified', 'jwt.not_revoked', 'role:admin'])->group(function () {
    Route::post('templates', [TemplateController::class, 'store']);
    Route::put('templates/{template:public_id}', [TemplateController::class, 'update']);
});
