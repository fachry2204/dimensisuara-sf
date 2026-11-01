<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegionController;

use App\Http\Controllers\Guest\PublishingController;

use App\Http\Middleware\HasRole;
use App\Http\Middleware\NoCacheMiddleware;
use App\Http\Middleware\Publishing\SigneeToken as PublishingSigneeToken;

Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/rilis', [\App\Http\Controllers\LandingController::class, 'releases'])->name('landing.releases');
Route::get('/branding/{kind}/{version}', [\App\Http\Controllers\BrandingController::class, 'show'])
    ->where('kind', 'logo|favicon')->where('version', '[a-f0-9]{20}')->name('branding.show');
Route::get('/rilis/{release}/cover', [\App\Http\Controllers\LandingController::class, 'cover'])
    ->whereNumber('release')->name('landing.cover');

// Prevent cache for auth routes
Route::middleware([NoCacheMiddleware::class])->group(function () {
    Auth::routes(['verify' => true]);
});

// Routes for regions queries
Route::prefix('regions')->group(function () {
    Route::get('provinces', [RegionController::class, 'provinces'])->name('regions.provinces');
    Route::get('children',  [RegionController::class, 'children'])->name('regions.children');
    Route::get('postcode',  [RegionController::class, 'postcode'])->name('regions.postcode');
});

// Terms and conditions page
Route::get('terms-and-conditions', [RegisterController::class, 'tnc'])->name('register.tnc');

// Authenticated and email verified routes
Route::middleware(['auth','verified'])->group(function () {

    // Get file content
    Route::get('/file/access/{file}', [FileController::class, 'access'])->name('file.access');

    // Notification
    Route::prefix('notification')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');

        Route::prefix('_ajax')->group(function () {
            Route::get('{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
        });
    });

});

// Routes for public access for signees
Route::prefix('sign')->group(function () {

    // Routes for publishing
    Route::prefix('publishing')->middleware([PublishingSigneeToken::class])->group(function () {
        Route::prefix('{token}')->group(function () {
            Route::get('/',             [PublishingController::class, 'show'])->name('sign.publishing.show');
            Route::put('publisher',     [PublishingController::class, 'update'])->name('sign.publishing.publisher.update');
            Route::get('file/{file}',   [FileController::class, 'signee'])->name('sign.publishing.songwriter.documents.show');
            Route::post('approve',      [PublishingController::class, 'approve'])->name('sign.publishing.approve');
            Route::post('reject',       [PublishingController::class, 'reject'])->name('sign.publishing.reject');
        });
    });

    Route::get('invalid', function () {
        return view('guest.sign.invalid');
    })->name('sign.invalid');
});
