<?php

use App\Http\Controllers\API\ChallengeActivityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ChallengeController;
use App\Http\Controllers\API\LogoutController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


# Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [RegisterController::class, 'sendOtp']);
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);
});

Route::middleware('auth:sanctum')->group(function () {
    # Challenge routes
    Route::get('/challenges-list', [ChallengeController::class, 'challengesList']);
    Route::post('/challenges-add', [ChallengeController::class, 'challangeAdd']);
    Route::get('/challenges/{type}', [ChallengeController::class, 'challengesListFilter']);
    Route::get('/challenges-delete/{id}', [ChallengeController::class, 'challengeDelete']);   

    # Challenge Activities Routes
    Route::post('challenges/{challenge}/activities/generate', [ChallengeActivityController::class, 'generateActivities']);
    Route::get('challenges/{challenge}/activities', [ChallengeActivityController::class, 'getActivities']);
    Route::put('activities/{activity}/status', [ChallengeActivityController::class, 'updateStatus']);
    Route::get('challenges/{challenge}/progress', [ChallengeActivityController::class, 'getProgress']);
    Route::get('/activities/pending', [ChallengeActivityController::class, 'getListPendingActivities']);
    Route::get('/activities/stats', [ChallengeActivityController::class, 'getActivityStats']);
    Route::get('/activities/pending-count', [ChallengeActivityController::class, 'getActivitiesPendingCount']);

    # Logout Route
    Route::post('/logout', [LogoutController::class, 'logout']);
});
