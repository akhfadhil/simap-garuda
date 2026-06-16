<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DesaController;
use App\Http\Controllers\Admin\KecamatanController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PpkController;
use App\Http\Controllers\PpsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password', [AccountController::class, 'editPassword'])->name('password.edit');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('password.update');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::get('/dashboard/ppk', [DashboardController::class, 'ppk'])->name('dashboard.ppk');
    Route::get('/dashboard/pps', [DashboardController::class, 'pps'])->name('dashboard.pps');
    Route::get('/dashboard/kpps', [DashboardController::class, 'kpps'])->name('dashboard.kpps');

    Route::get('/clear-view-session', function () {
        session()->forget('admin_view_kecamatan_id');
        session()->forget('admin_view_desa_id');
        session()->forget('admin_view_tps_id');

        return response()->noContent();
    })->name('clear.view.session');

    Route::middleware('role:ppk,admin')->group(function () {
        Route::get('/ppk/data-pps', [PpkController::class, 'dataPps'])->name('ppk.data-pps');
        Route::get('/ppk/view-pps/{desa}', [PpkController::class, 'viewPps'])->name('ppk.view-pps');
    });

    Route::middleware('role:pps,ppk,admin')->group(function () {
        Route::get('/pps/data-tps', [PpsController::class, 'dataTps'])->name('pps.data-tps');
        Route::get('/pps/view-tps/{tps}', [PpsController::class, 'viewTps'])->name('pps.view-tps');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('kecamatan', KecamatanController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('desa', DesaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tps', TpsController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('users/bulk', [UserManagementController::class, 'bulk'])->name('users.bulk');
        Route::post('users/bulk', [UserManagementController::class, 'bulkStore'])->name('users.bulk.store');
        Route::get('users/export', [UserManagementController::class, 'export'])->name('users.export');
        Route::resource('users', UserManagementController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/kecamatan/{kecamatan}/view', [DashboardController::class, 'viewAsPpk'])->name('kecamatan.view');
        Route::get('/desa/{desa}/view', [DashboardController::class, 'viewAsPps'])->name('desa.view');
        Route::get('/tps/{tps}/view', [DashboardController::class, 'viewAsKpps'])->name('tps.view');
    });

    Route::prefix('admin/setup')->name('admin.setup.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SetupController::class, 'index'])->name('index');
        Route::post('pemilu-settings', [App\Http\Controllers\Admin\SetupController::class, 'updatePemiluSettings'])->name('pemilu.settings');

        Route::post('partai', [App\Http\Controllers\Admin\SetupController::class, 'storePartai'])->name('partai.store');
        Route::delete('partai/{partai}', [App\Http\Controllers\Admin\SetupController::class, 'destroyPartai'])->name('partai.destroy');
        Route::post('partai/{partai}/caleg', [App\Http\Controllers\Admin\SetupController::class, 'storeCaleg'])->name('caleg.store');
        Route::delete('caleg/{caleg}', [App\Http\Controllers\Admin\SetupController::class, 'destroyCaleg'])->name('caleg.destroy');

        Route::post('dapil', [App\Http\Controllers\Admin\SetupController::class, 'storeDapil'])->name('dapil.store');
        Route::delete('dapil/{dapil}', [App\Http\Controllers\Admin\SetupController::class, 'destroyDapil'])->name('dapil.destroy');
        Route::post('kecamatan-dapil', [App\Http\Controllers\Admin\SetupController::class, 'assignDapil'])->name('kecamatan.dapil');
    });

    Route::prefix('rekap')->name('rekap.')->middleware('role:kpps,pps,ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\KppsController::class, 'index'])->name('index');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\KppsController::class, 'export'])->name('export');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\KppsController::class, 'form'])->name('form');
        Route::post('{jenis}', [App\Http\Controllers\Rekap\KppsController::class, 'store'])->name('store');
        Route::post('{jenis}/finalisasi', [App\Http\Controllers\Rekap\KppsController::class, 'finalisasi'])->name('finalisasi');
    });

    Route::prefix('pps/rekap')->name('pps.rekap.')->middleware('role:pps,ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\PpsController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\PpsController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\PpsController::class, 'export'])->name('export');
    });

    Route::prefix('ppk/rekap')->name('ppk.rekap.')->middleware('role:ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\PpkController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\PpkController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\PpkController::class, 'export'])->name('export');
    });

    Route::prefix('admin/rekap')->name('admin.rekap.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\AdminController::class, 'index'])->name('index');
        Route::get('chart', [App\Http\Controllers\Rekap\AdminController::class, 'chartPage'])->name('chart');
        Route::get('chart/data', [App\Http\Controllers\Rekap\AdminController::class, 'chartData'])->name('chart.data');
        Route::get('export/download', [App\Http\Controllers\Rekap\AdminController::class, 'exportDownload'])->name('export.download');
        Route::get('export/tps-belum-masuk', [App\Http\Controllers\Rekap\AdminController::class, 'exportMissingTps'])->name('export.missing-tps');
        Route::get('export/tps-perlu-dicek', [App\Http\Controllers\Rekap\AdminController::class, 'exportReviewTps'])->name('export.review-tps');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\AdminController::class, 'export'])->name('export');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\AdminController::class, 'show'])->name('show');
    });
});
