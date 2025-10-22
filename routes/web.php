<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Api\EmailTrackingController;
use App\Livewire\Dashboard;
use App\Livewire\MailingForm;
use App\Livewire\CampaignForm;
use App\Livewire\CampaignProgress;

Route::get('/', [HomeController::class, 'welcome'])->name('welcome');

Route::get('/subscribe', [HomeController::class, 'subscribe'])->name('suscribe');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/campaign/create', CampaignForm::class)->name('campaign.create');
    Route::get('/campaign/{campaign}/progress', CampaignProgress::class)->name('campaign.progress');
});

Route::prefix('email')->name('email.')->group(function () {
    Route::get('/open/{email}', [EmailTrackingController::class, 'open'])->name('open');
    Route::get('/click/{email}', [EmailTrackingController::class, 'click'])->name('click');
    Route::get('/unsubscribe/{email}', [EmailTrackingController::class, 'unsubscribe'])->name('unsubscribe');
});
