<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/authorization', [AuthController::class, 'login']);
Route::post('/registration', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::prefix('files')->group(function() {
        Route::post('/', [FilesController::class, 'upload']);
        Route::patch('/{file_id}', [FilesController::class, 'update']);
    });
});
