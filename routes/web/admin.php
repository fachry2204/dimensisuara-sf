<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\LogActivityController;

/**
 * Aggregator
 */
use App\Http\Controllers\Admin\Aggregator\MainController        as AggregatorController;
use App\Http\Controllers\Admin\Aggregator\ContractController    as AggregatorContractController;
use App\Http\Controllers\Admin\Aggregator\ReportingController   as AggregatorReportingController;
use App\Http\Controllers\Admin\Aggregator\SettlementController  as AggregatorSettlementController;

/**
 * Publishing
 */
use App\Http\Controllers\Admin\Publishing\MainController        as PublishingController;
use App\Http\Controllers\Admin\Publishing\ContractController    as PublishingContractController;
use App\Http\Controllers\Admin\Publishing\ReportingController   as PublishingReportingController;
use App\Http\Controllers\Admin\Publishing\SettlementController  as PublishingSettlementController;

/**
 * MCN
 */
use App\Http\Controllers\Admin\MCN\MainController           as McnController;
use App\Http\Controllers\Admin\MCN\ContractController       as McnContractController;
use App\Http\Controllers\Admin\MCN\ReportingController      as McnReportingController;
use App\Http\Controllers\Admin\MCN\SettlementController     as McnSettlementController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\UserStatusController;
use App\Http\Controllers\Admin\UserRolesController;

/**
 * Will be changed later
 */
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ContractController;

// Authenticated and email verified routes
Route::middleware(['auth','verified'])->group(function () {

    // User with admin privileges
    Route::prefix('admin')->middleware(['can:admin'])->group(function () {

        // Home
        Route::get('/', [HomeController::class, 'index'])->name('admin.home');

        /**
         * Aggregator
         */
        Route::prefix('releases')->middleware(['can:view-releases'])->group(function () {

            /**
             * Contracts
             */
            Route::prefix('contract')->group(function () {
                Route::get('/',                     [AggregatorContractController::class, 'index'])->name('admin.releases.contract.index');
                Route::post('store',                [AggregatorContractController::class, 'store'])->middleware(['can:generate-contracts'])->name('admin.releases.contract.store');

                Route::prefix('_ajax')->group(function () {
                    Route::get('search',            [AggregatorContractController::class, 'get_data'])->name('admin.releases.contract.get_data');
                    Route::get('search-per-user',   [AggregatorContractController::class, 'get_data_per_user'])->name('admin.releases.contract.get_data_per_user');
                });

                Route::prefix('{id}')->group(function () {
                    Route::get('view',              [AggregatorContractController::class, 'view_raw'])->name('admin.releases.contract.view_raw');                                            // view raw contract PDF
                    Route::get('view-pdf',          [AggregatorContractController::class, 'view_uploaded'])->name('admin.releases.contract.view_uploaded');                                  // view uploaded contract PDF
                    Route::post('upload',           [AggregatorContractController::class, 'pdf_upload'])->middleware(['can:upload-contracts'])->name('admin.releases.contract.pdf_upload');  // upload an existing contract
                    Route::delete('/',              [AggregatorContractController::class, 'destroy'])->middleware(['can:delete-contracts'])->name('admin.releases.contract.destroy');        // delete a contract
                });
            });

            /**
             * Reporting
             */
            Route::prefix('reporting')->middleware(['can:view-reporting'])->group(function () {
                Route::get('/',                 [AggregatorReportingController::class, 'index'])->name('admin.releases.reporting.index');
                Route::post('export',           [AggregatorReportingController::class, 'export'])->name('admin.releases.reporting.export');
                Route::get('{id}',              [AggregatorReportingController::class, 'show'])->name('admin.releases.reporting.show');

                Route::prefix('_ajax')->group(function () {
                    Route::get('data',          [AggregatorReportingController::class, 'get_data'])->name('admin.releases.reporting.get_data');
                    Route::get('release',       [AggregatorReportingController::class, 'get_release'])->name('admin.releases.reporting.get_release');
                    Route::get('track',         [AggregatorReportingController::class, 'get_track'])->name('admin.releases.reporting.get_track');
                    Route::get('profile',       [AggregatorReportingController::class, 'get_profile'])->name('admin.releases.reporting.get_profile');
                });

                Route::middleware(['can:upload-reporting'])->group(function () {
                    Route::post('upload',       [AggregatorReportingController::class, 'upload'])->name('admin.releases.reporting.upload');
                    Route::post('recheck',      [AggregatorReportingController::class, 'recheck'])->name('admin.releases.reporting.recheck');
                    Route::put('{id}/update',   [AggregatorReportingController::class, 'update'])->name('admin.releases.reporting.update');
                    Route::put('{id}/rate',     [AggregatorReportingController::class, 'rate'])->name('admin.releases.reporting.rate');
                });
                
                Route::delete('{id}',           [AggregatorReportingController::class, 'destroy'])->middleware(['can:delete-reporting'])->name('admin.releases.reporting.destroy');
            });

            /**
             * Settlements
             */
            Route::prefix('transaction')->group(function () {
                Route::get('/',                 [AggregatorSettlementController::class, 'index'])->name('admin.releases.settlement.index');
                Route::post('export',           [AggregatorSettlementController::class, 'export'])->name('admin.releases.settlement.export');
                Route::delete('bulk',           [AggregatorSettlementController::class, 'destroyBulk'])->middleware(['can:delete-transactions'])->name('admin.releases.settlement.destroy_bulk');

                Route::middleware(['can:generate-transactions'])->group(function () {
                    Route::post('preview',      [AggregatorSettlementController::class, 'preview'])->name('admin.releases.settlement.preview');
                    Route::post('store',        [AggregatorSettlementController::class, 'store'])->name('admin.releases.settlement.store');
                });

                Route::prefix('_ajax')->group(function () {
                    Route::get('data',          [AggregatorSettlementController::class, 'get_data'])->name('admin.releases.settlement.get_data');
                });

                Route::prefix('{id}')->group(function () {
                    Route::get('/',             [AggregatorSettlementController::class, 'show'])->name('admin.releases.settlement.show');
                    Route::put('/complete',     [AggregatorSettlementController::class, 'complete'])->middleware(['can:manage-generated-transactions'])->name('admin.releases.settlement.complete');
                    Route::post('/upload',      [AggregatorSettlementController::class, 'upload'])->middleware(['can:manage-generated-transactions'])->name('admin.releases.settlement.upload');
                    Route::delete('/',          [AggregatorSettlementController::class, 'destroy'])->middleware(['can:delete-transactions'])->name('admin.releases.settlement.destroy');
                });
            });
            
            /**
             * Main features
             */
            Route::post('export/releases', [AggregatorController::class, 'exportReleases'])->name('admin.releases.export.releases');
            Route::post('export/tracks', [AggregatorController::class, 'exportTracks'])->name('admin.releases.export.tracks');

            Route::get('statistics/{id}', [HomeController::class, 'releaseStatistics'])->name('admin.releases.statistics');

            Route::get('/', [AggregatorController::class, 'index'])->name('admin.releases.index');
            
            Route::middleware(['can:create-releases'])->group(function () {
                Route::get('/create',           [AggregatorController::class, 'create'])->name('admin.releases.create');
                Route::post('/store',           [AggregatorController::class, 'store'])->name('admin.releases.store');
            });

            Route::prefix('_ajax')->group(function () {
                Route::get('/genre/{genreid}',  [AggregatorController::class, 'get_subgenre'])->name('admin.releases.get_subgenre');
                Route::get('/search/{status}',  [AggregatorController::class, 'get_data'])->name('admin.releases.get_data');
            });

            Route::prefix('track')->group(function () {
                Route::prefix('_ajax')->group(function () {
                    Route::post('/',            [AggregatorController::class, 'track_chunk_store'])->name('admin.releases.track_chunk_store');
                    Route::post('merge-chunk',  [AggregatorController::class, 'track_chunk_merge'])->name('admin.releases.track_chunk_merge');
                });

                Route::get('{id}',              [AggregatorController::class, 'track'])->name('admin.releases.track');
            });

            Route::prefix('{id}')->group(function () {
                Route::get('/',                 [AggregatorController::class, 'show'])->name('admin.releases.show');

                Route::middleware(['can:edit-releases'])->group(function () {
                    Route::get('/edit',         [AggregatorController::class, 'edit'])->name('admin.releases.edit');
                    Route::put('/',             [AggregatorController::class, 'update'])->name('admin.releases.update');
                });
                
                // Status Action
                Route::middleware(['can:manage-releases-status'])->group(function () {
                    Route::put('/accept',       [AggregatorController::class, 'accept'])->name('admin.releases.accept');
                    Route::put('/review',       [AggregatorController::class, 'review'])->name('admin.releases.review');
                    Route::put('/uploading',    [AggregatorController::class, 'uploading'])->name('admin.releases.uploading');
                    Route::put('/verify',       [AggregatorController::class, 'verify'])->name('admin.releases.verify');
                    Route::put('/reject',       [AggregatorController::class, 'reject'])->name('admin.releases.reject');
                });
            });
        });

        // Publishing
        Route::prefix('publishing')->middleware(['can:view-releases'])->group(function () {
            
            /**
             * Contracts
             */
            Route::prefix('contract')->group(function () {
                Route::get('/',                 [PublishingContractController::class, 'index'])->name('admin.publishing.contract.index');
                Route::post('store',            [PublishingContractController::class, 'store'])->middleware(['can:generate-contracts'])->name('admin.publishing.contract.store');

                Route::prefix('_ajax')->group(function () {
                    Route::get('search',        [PublishingContractController::class, 'get_data'])->name('admin.publishing.contract.get_data');
                });

                Route::prefix('{id}')->group(function () {
                    Route::get('view',          [PublishingContractController::class, 'view'])->name('admin.publishing.contract.view');                                                     // view raw contract PDF
                    Route::get('view-pdf',      [PublishingContractController::class, 'view_uploaded'])->name('admin.publishing.contract.view_uploaded');                                  // view uploaded contract PDF
                    Route::put('upload',        [PublishingContractController::class, 'upload'])->middleware(['can:upload-contracts'])->name('admin.publishing.contract.upload');  // upload an existing contract
                    Route::delete('pdf',        [PublishingContractController::class, 'delete_pdf'])->name('admin.publishing.contract.delete_pdf');
                    Route::delete('/',          [PublishingContractController::class, 'destroy'])->middleware(['can:delete-contracts'])->name('admin.publishing.contract.destroy');        // delete a contract
                });
            });

            /**
             * Reporting
             */
            Route::prefix('reporting')->group(function () {
                Route::get('/',                 [PublishingReportingController::class, 'index'])->name('admin.publishing.reporting.index');
            });

            /**
             * Settlements
             */
            Route::prefix('transaction')->group(function () {
                Route::get('/',                 [PublishingSettlementController::class, 'index'])->name('admin.publishing.settlement.index');
            });

            /**
             * Main features
             */
            Route::get('/',                     [PublishingController::class, 'index'])->name('admin.publishing.index');
            
            Route::middleware(['can:create-releases'])->group(function () {
                Route::get('/create',           [PublishingController::class, 'create'])->name('admin.publishing.create');
                Route::post('/store',           [PublishingController::class, 'store'])->name('admin.publishing.store');
            });

            Route::prefix('_ajax')->group(function () {
                Route::get('/search/{status}',  [PublishingController::class, 'get_data'])->name('admin.publishing.get_data');
            });

            Route::prefix('signee')->group(function () {
                Route::get('{id}/resend',       [PublishingController::class, 'resend'])->name('admin.publishing.signee.resend');
            });

            Route::prefix('{id}')->group(function () {
                Route::get('/',                     [PublishingController::class, 'show'])->name('admin.publishing.show');
                Route::put('upload/{type}',         [PublishingController::class, 'upload'])->middleware(['can:upload-contracts'])->name('admin.publishing.upload');  // upload an existing contract
                Route::delete('{type}',             [PublishingController::class, 'delete_pdf'])->name('admin.publishing.delete_pdf');

                Route::get('view/statement',        [PublishingController::class, 'view_statement'])->name('admin.publishing.view_statement');
                Route::get('view/statement_pdf',    [PublishingController::class, 'view_statement_pdf'])->name('admin.publishing.view_statement_pdf');
                Route::get('view/nuon',             [PublishingController::class, 'view_nuon'])->name('admin.publishing.view_nuon');
                Route::get('view/nuon_pdf',         [PublishingController::class, 'view_nuon_pdf'])->name('admin.publishing.view_nuon_pdf');

                Route::middleware(['can:edit-releases'])->group(function () {
                    Route::get('/edit',         [PublishingController::class, 'edit'])->name('admin.publishing.edit');
                    Route::put('/',             [PublishingController::class, 'update'])->name('admin.publishing.update');
                });
    
                // Status Action
                Route::middleware(['can:manage-releases-status'])->group(function () {
                    Route::put('/approve',      [PublishingController::class, 'approve'])->name('admin.publishing.approve');
                    Route::put('/reject',       [PublishingController::class, 'reject'])->name('admin.publishing.reject');
                });
            });
        });

        // MCN
        Route::prefix('mcn')->middleware(['can:view-releases'])->group(function () {
            Route::get('/', [McnController::class, 'index'])->name('admin.mcn.index');
        });

        // User Management
        Route::prefix('users')->middleware(['can:view-users'])->group(function () {
            Route::get('/',         [UserController::class, 'index'])->name('admin.users.index');
            Route::post('export',   [UserController::class, 'exportUsers'])->name('admin.users.export');
            Route::get('create',    [UserController::class, 'create'])->name('admin.users.create');
            Route::get('{id}',      [UserController::class, 'show'])->name('admin.users.show');

            // Impersonate
            Route::prefix('impersonate')->middleware(['can:impersonate-users'])->group(function () {
                Route::post('stop', [ImpersonateController::class, 'stop_impersonate'])->name('admin.impersonate.stop');
                Route::post('{id}', [ImpersonateController::class, 'impersonate'])->name('admin.impersonate');
            });

            Route::middleware(['can:edit-users'])->group(function () {
                // Identity
                Route::prefix('identity')->group(function () {
                    Route::put('/',     [UserController::class, 'update_identity'])->name('admin.users.identity.update');
                    Route::delete('/',  [UserController::class, 'delete_identity'])->name('admin.users.identity.delete');
                });

                Route::put('{id}',      [UserController::class, 'update'])->name('admin.users.update');
                Route::get('{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
                Route::put('{id}/rate', [UserController::class, 'update_rate'])->name('admin.users.update_rate');
                
            });

            Route::delete('{id}',   [UserController::class, 'destroy'])->middleware(['can:delete-users'])->name('admin.users.destroy');

            // User status
            Route::prefix('{id}/status')->middleware(['can:manage-user-status'])->group(function () {
                Route::put('verify',       [UserStatusController::class, 'verify'])->name('admin.users.verify');
                Route::put('verify_email', [UserStatusController::class, 'verify_email'])->name('admin.users.verify_email');
                Route::put('notify',       [UserStatusController::class, 'notify'])->name('admin.users.notify');
                Route::put('block',        [UserStatusController::class, 'block'])->name('admin.users.block');
                Route::put('unblock',      [UserStatusController::class, 'unblock'])->name('admin.users.unblock');
            });

            // User features
            Route::prefix('{id}/feature')->middleware(['can:manage-user-features'])->group(function () {
                Route::put('aggregator/enable',     [UserStatusController::class, 'enable_aggregator'])->name('admin.users.aggregator.enable');
                Route::put('aggregator/disable',    [UserStatusController::class, 'disable_aggregator'])->name('admin.users.aggregator.disable');
                Route::put('publishing/enable',     [UserStatusController::class, 'enable_publishing'])->name('admin.users.publishing.enable');
                Route::put('publishing/disable',    [UserStatusController::class, 'disable_publishing'])->name('admin.users.publishing.disable');
                Route::put('mcn/enable',            [UserStatusController::class, 'enable_mcn'])->name('admin.users.mcn.enable');
                Route::put('mcn/disable',           [UserStatusController::class, 'disable_mcn'])->name('admin.users.mcn.disable');
            });
        });

        Route::prefix('broadcast')->middleware(['can:broadcast'])->group(function () {
            Route::get('/',             [BroadcastController::class, 'index'])->name('admin.broadcast.index');
            Route::get('create',        [BroadcastController::class, 'create'])->name('admin.broadcast.create');
            Route::post('/',            [BroadcastController::class, 'store'])->name('admin.broadcast.store');
            Route::get('{id}',          [BroadcastController::class, 'show'])->name('admin.broadcast.show');
            Route::get('{id}/edit',     [BroadcastController::class, 'edit'])->name('admin.broadcast.edit');
            Route::put('{id}',          [BroadcastController::class, 'update'])->name('admin.broadcast.update');
            Route::post('{id}/send',    [BroadcastController::class, 'send'])->name('admin.broadcast.send');
            Route::delete('{id}',       [BroadcastController::class, 'destroy'])->name('admin.broadcast.destroy');

            Route::get('_ajax/data',    [BroadcastController::class, 'get_data'])->name('admin.broadcast.get_data');
        });

        Route::prefix('announcement')->middleware(['can:broadcast'])->group(function () {
            Route::get('/',             [AnnouncementController::class, 'index'])->name('admin.announcement.index');
            Route::get('create',        [AnnouncementController::class, 'create'])->name('admin.announcement.create');
            Route::post('/',            [AnnouncementController::class, 'store'])->name('admin.announcement.store');
            Route::get('{id}/edit',     [AnnouncementController::class, 'edit'])->name('admin.announcement.edit');
            Route::put('{id}',          [AnnouncementController::class, 'update'])->name('admin.announcement.update');
            Route::delete('{id}',       [AnnouncementController::class, 'destroy'])->name('admin.announcement.destroy');

            Route::get('_ajax/data',    [AnnouncementController::class, 'get_data'])->name('admin.announcement.get_data');
        });

        // Logging
        Route::prefix('logactivity')->middleware(['can:view-logging'])->group(function () {
            Route::get('/',         [LogActivityController::class, 'index'])->name('admin.logactivity.index');
            Route::get('search',   [LogActivityController::class, 'get_data'])->name('admin.logactivity.get_data');
            Route::get('{id}',   [LogActivityController::class, 'show'])->name('admin.logactivity.show');
        });

        // Automation
        Route::prefix('automation')->middleware(['can:manage-automation'])->group(function () {
            Route::get('/',                 [AutomationController::class, 'index'])->name('admin.automation.index');
            Route::post('{id}/retry',       [AutomationController::class, 'retry'])->name('admin.automation.retry');
            Route::post('{id}/retry_batch', [AutomationController::class, 'retry_batch'])->name('admin.automation.retry_batch');
        });

        // Admin Roles and Permissions
        Route::prefix('user-roles')->middleware(['can:view-roles'])->group(function () {
           Route::get('/',  [UserRolesController::class, 'index'])->name('admin.user-roles.index');
           Route::middleware(['can:manage-roles'])->group(function () {
               Route::get('create',     [UserRolesController::class, 'create'])->name('admin.user-roles.create');
               Route::post('/',         [UserRolesController::class, 'store'])->name('admin.user-roles.store');
               Route::get('{id}/edit',  [UserRolesController::class, 'edit'])->name('admin.user-roles.edit');
               Route::put('{id}',       [UserRolesController::class, 'update'])->name('admin.user-roles.update');
               Route::delete('{id}',    [UserRolesController::class, 'destroy'])->name('admin.user-roles.destroy');
           });

            Route::prefix('admin')->group(function () {
                Route::post('store',            [UserAdminController::class, 'store'])->name('admin.user-admin.store');
                Route::delete('{id}/revoke',    [UserAdminController::class, 'revoke'])->name('admin.user-admin.revoke');
            });
        });

    });
});
