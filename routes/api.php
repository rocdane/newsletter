<?php

use App\Http\Controllers\Api\CampaignController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('campaigns')->name('api')->group(function () {

    // Dashboard overview
    Route::get('dashboard', [CampaignController::class, 'dashboard'])->name('campaigns.dashboard');

    // Campaign CRUD operations
    Route::get('/', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::delete('{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    // Campaign specific data
    Route::get('{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');
    Route::get('{campaign}/emails', [CampaignController::class, 'emails'])->name('campaigns.emails');
    Route::get('{campaign}/performance', [CampaignController::class, 'performance'])->name('campaigns.performance');
});
