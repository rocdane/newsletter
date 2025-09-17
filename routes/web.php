<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EmailTrackerController;
use App\Livewire\Dashboard;
use App\Livewire\MailingForm;
use App\Livewire\CampaignProgress;

Route::get('/', [HomeController::class, 'welcome'])->name('welcome');

Route::get('/mailing', [HomeController::class, 'mailing'])->name('mailing');

Route::get('/subscribe', [HomeController::class, 'subscribe'])->name('suscribe');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
});

Route::prefix('email')->name('email.')->group(function () {
    Route::get('/campaign/create', MailingForm::class)->name('campaign.create');
    Route::get('/campaign/{campaign}/progress', CampaignProgress::class)->name('campaign.progress');
    Route::get('/open/{email}', [EmailTrackerController::class, 'open'])->name('open');
    Route::get('/click/{email}', [EmailTrackerController::class, 'click'])->name('click');
    Route::get('/unsubscribe/{email}', [EmailTrackerController::class, 'unsubscribe'])->name('unsubscribe');
});
