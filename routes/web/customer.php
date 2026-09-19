<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MultichannelController;
use App\Http\Controllers\ReleasesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublishingController;
use App\Http\Controllers\PublishingSongwriterController;
use App\Http\Controllers\SettlementController;

use App\Http\Middleware\CustomerVerified;

// Authenticated and email verified routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Any user that has profile
    Route::middleware(['can:customer'])->group(function () {
        // If user is not admin or verified customer but is a customer
        Route::get('/pending', [HomeController::class, 'pending'])->name('pending');

        // Profile
        Route::prefix('/profile')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile');
            // Route::put('/',             [ProfileController::class, 'update'])->name('profile.update');

            Route::prefix('identity')->group(function () {
                Route::put('/', [ProfileController::class, 'update_identity'])->name('profile.identity.update');
            });
        });

        // Contract
        Route::get('/contract/{id}', [ProfileController::class, 'retrieve_contract'])->name('profile.contract.retrieve');

        // Verified customer routes
        Route::middleware([CustomerVerified::class])->group(function () {

            // Home
            Route::get('/overview', [HomeController::class, 'index'])->name('home');

            Route::prefix('analytics')->group(function () {
                Route::get('/', [AnalyticController::class, 'index'])->name('analytics.index');
                Route::get('release/{id}', [AnalyticController::class, 'releaseStatistics'])->name('analytics.release.statistics');
                Route::get('tracks', [AnalyticController::class, 'get_tracks'])->name('analytics.get_tracks');
                Route::get('artists', [AnalyticController::class, 'get_artists'])->name('analytics.get_artists');
            });

            // Earnings
            Route::prefix('earnings')->group(function () {
                Route::get('/', [HomeController::class, 'earnings'])->name('home.earnings');
                Route::post('export', [HomeController::class, 'export_earnings'])->name('home.export_earnings');
                Route::prefix('_ajax')->group(function () {
                    Route::get('/data', [HomeController::class, 'get_earnings_data'])->name('home.get_earnings_data');
                });
            });

            Route::prefix('transactions')->group(function () {
                Route::get('/', [SettlementController::class, 'index'])->name('settlements.index');
                Route::get('/{id}', [SettlementController::class, 'show'])->name('settlements.show');
            });

            // Releases
            Route::group([], function () {

                // Releases
                Route::prefix('releases')->group(function () {
                    Route::get('/', [ReleasesController::class, 'index'])->name('releases.index');
                    Route::get('/create/{type}', [ReleasesController::class, 'create'])->name('releases.create');
                    Route::get('{id}', [ReleasesController::class, 'show'])->name('releases.show');
                    Route::get('{id}/edit', [ReleasesController::class, 'edit'])->name('releases.edit');
                    Route::delete('{id}', [ReleasesController::class, 'destroy'])->name('releases.destroy');

                    Route::prefix('_ajax')->group(function () {
                        Route::post('/store', [ReleasesController::class, 'store'])->name('releases.store');
                        Route::put('{id}/update/', [ReleasesController::class, 'update'])->name('releases.update');
                        Route::post('{id}/submit/', [ReleasesController::class, 'submit'])->name('releases.submit');

                        // Get subgenre
                        Route::get('/genre/{genreid}', [ReleasesController::class, 'get_subgenre'])->name('releases.get_subgenre');
                        
                        // Get tracks
                        Route::get('/{id}/tracks', [ReleasesController::class, 'get_tracks'])->name('releases.get_tracks');
                    });
                });

                // Get track
                Route::prefix('track')->group(function () {
                    Route::prefix('_ajax')->group(function () {
                        Route::post('/', [ReleasesController::class, 'track_chunk_store'])->name('releases.track_chunk_store');
                        Route::post('/merge-chunk', [ReleasesController::class, 'track_chunk_merge'])->name('releases.track_chunk_merge');
                    });
                });
            });

            // Publishing
            Route::prefix('publishing')->group(function () {
                Route::get('/', [PublishingController::class, 'index'])->name('publishing.index');
                Route::get('create', [PublishingController::class, 'create'])->name('publishing.create');
                Route::post('store', [PublishingController::class, 'store'])->name('publishing.store');

                Route::get('contract/{id}', [PublishingController::class, 'retrieve_contract'])->name('publishing.contract.retrieve');

                Route::prefix('songwriters')->group(function () {
                    Route::get('/', [PublishingSongwriterController::class, 'index'])->name('publishing.songwriters.index');
                    Route::get('create', [PublishingSongwriterController::class, 'create'])->name('publishing.songwriters.create');
                    Route::post('store', [PublishingSongwriterController::class, 'store'])->name('publishing.songwriters.store');
                    Route::get('{id}/edit', [PublishingSongwriterController::class, 'edit'])->name('publishing.songwriters.edit');
                    Route::put('{id}', [PublishingSongwriterController::class, 'update'])->name('publishing.songwriters.update');
                    Route::delete('{id}', [PublishingSongwriterController::class, 'destroy'])->name('publishing.songwriters.destroy');
                });

                Route::prefix('signee')->group(function () {
                    Route::get('{id}/resend', [PublishingController::class, 'resend'])->name('publishing.signee.resend');
                });

                Route::get('{id}/edit', [PublishingController::class, 'edit'])->name('publishing.edit');
                Route::get('{id}', [PublishingController::class, 'show'])->name('publishing.show');
                Route::put('{id}/cancel', [PublishingController::class, 'cancel'])->name('publishing.cancel');
                Route::put('{id}', [PublishingController::class, 'update'])->name('publishing.update');
                Route::delete('{id}', [PublishingController::class, 'destroy'])->name('publishing.destroy');
            });

            // MCN
            Route::prefix('mcn')->group(function () {
                Route::get('/', [MultichannelController::class, 'index'])->name('mcn.index');
                Route::get('/create', [MultichannelController::class, 'create'])->name('mcn.create');
            });
        });

    });
});
