<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DesaController;
use App\Http\Controllers\Admin\KecamatanController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KorcamController;
use App\Http\Controllers\KordesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password', [AccountController::class, 'editPassword'])->name('password.edit');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('password.update');

    Route::get('/dashboard/admin-partai', [DashboardController::class, 'admin'])->name('dashboard.admin_partai');
    Route::redirect('/dashboard/admin', '/dashboard/admin-partai')->name('dashboard.admin');
    Route::get('/dashboard/korcam', [DashboardController::class, 'korcam'])->name('dashboard.korcam');
    Route::redirect('/dashboard/ppk', '/dashboard/korcam')->name('dashboard.ppk');
    Route::get('/dashboard/kordes', [DashboardController::class, 'kordes'])->name('dashboard.kordes');
    Route::redirect('/dashboard/pps', '/dashboard/kordes')->name('dashboard.pps');
    Route::get('/dashboard/saksi', [DashboardController::class, 'saksi'])->name('dashboard.saksi');
    Route::redirect('/dashboard/kpps', '/dashboard/saksi')->name('dashboard.kpps');

    Route::get('/clear-view-session', function () {
        session()->forget('admin_view_kecamatan_id');
        session()->forget('admin_view_desa_id');
        session()->forget('admin_view_tps_id');

        return response()->noContent();
    })->name('clear.view.session');

    Route::middleware('role:korcam,admin_partai')->group(function () {
        Route::get('/korcam/data-kordes', [KorcamController::class, 'dataKordes'])->name('korcam.data-kordes');
        Route::redirect('/ppk/data-pps', '/korcam/data-kordes')->name('ppk.data-pps');
        Route::get('/korcam/view-kordes/{desa}', [KorcamController::class, 'viewKordes'])->name('korcam.view-kordes');
        Route::get('/ppk/view-pps/{desa}', fn ($desa) => redirect()->route('korcam.view-kordes', $desa))->name('ppk.view-pps');
    });

    Route::middleware('role:kordes,korcam,admin_partai')->group(function () {
        Route::get('/kordes/data-tps', [KordesController::class, 'dataTps'])->name('kordes.data-tps');
        Route::redirect('/pps/data-tps', '/kordes/data-tps')->name('pps.data-tps');
        Route::get('/kordes/view-tps/{tps}', [KordesController::class, 'viewTps'])->name('kordes.view-tps');
        Route::get('/pps/view-tps/{tps}', fn ($tps) => redirect()->route('kordes.view-tps', $tps))->name('pps.view-tps');
    });

    Route::middleware('role:admin_partai')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('kecamatan', KecamatanController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('desa', DesaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tps', TpsController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('users/bulk', [UserManagementController::class, 'bulk'])->name('users.bulk');
        Route::post('users/bulk', [UserManagementController::class, 'bulkStore'])->name('users.bulk.store');
        Route::get('users/export', [UserManagementController::class, 'export'])->name('users.export');
        Route::resource('users', UserManagementController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/kecamatan/{kecamatan}/view', [DashboardController::class, 'viewAsKorcam'])->name('kecamatan.view');
        Route::get('/desa/{desa}/view', [DashboardController::class, 'viewAsKordes'])->name('desa.view');
        Route::get('/tps/{tps}/view', [DashboardController::class, 'viewAsSaksi'])->name('tps.view');
    });

    Route::prefix('admin/setup')->name('admin.setup.')->middleware('role:admin_partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SetupController::class, 'index'])->name('index');
        Route::post('pemilu-settings', [App\Http\Controllers\Admin\SetupController::class, 'updatePemiluSettings'])->name('pemilu.settings');

        Route::post('partai', [App\Http\Controllers\Admin\SetupController::class, 'storePartai'])->name('partai.store');
        Route::delete('partai/{partai}', [App\Http\Controllers\Admin\SetupController::class, 'destroyPartai'])->name('partai.destroy');
        Route::post('caleg', [App\Http\Controllers\Admin\SetupController::class, 'storeConfiguredCaleg'])->name('caleg.configured.store');
        Route::post('partai/{partai}/caleg', [App\Http\Controllers\Admin\SetupController::class, 'storeCaleg'])->name('caleg.store');
        Route::delete('caleg/{caleg}', [App\Http\Controllers\Admin\SetupController::class, 'destroyCaleg'])->name('caleg.destroy');

        Route::post('dapil', [App\Http\Controllers\Admin\SetupController::class, 'storeDapil'])->name('dapil.store');
        Route::delete('dapil/{dapil}', [App\Http\Controllers\Admin\SetupController::class, 'destroyDapil'])->name('dapil.destroy');
        Route::post('kecamatan-dapil', [App\Http\Controllers\Admin\SetupController::class, 'assignDapil'])->name('kecamatan.dapil');
    });

    Route::prefix('rekap')->name('rekap.')->middleware('role:saksi_tps,kordes,korcam,admin_partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\SaksiController::class, 'index'])->name('index');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\SaksiController::class, 'export'])->name('export');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\SaksiController::class, 'form'])->name('form');
        Route::post('{jenis}', [App\Http\Controllers\Rekap\SaksiController::class, 'store'])->name('store');
        Route::post('{jenis}/finalisasi', [App\Http\Controllers\Rekap\SaksiController::class, 'finalisasi'])->name('finalisasi');
    });

    Route::prefix('kordes/rekap')->name('kordes.rekap.')->middleware('role:kordes,korcam,admin_partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\KordesController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\KordesController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\KordesController::class, 'export'])->name('export');
    });
    Route::prefix('pps/rekap')->name('pps.rekap.')->middleware('role:kordes,korcam,admin_partai')->group(function () {
        Route::redirect('/', '/kordes/rekap')->name('index');
        Route::get('{jenis}', fn ($jenis) => redirect()->route('kordes.rekap.show', $jenis))->name('show');
        Route::get('{jenis}/export', fn ($jenis) => redirect()->route('kordes.rekap.export', $jenis))->name('export');
    });

    Route::prefix('korcam/rekap')->name('korcam.rekap.')->middleware('role:korcam,admin_partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\KorcamController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\KorcamController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\KorcamController::class, 'export'])->name('export');
    });
    Route::prefix('ppk/rekap')->name('ppk.rekap.')->middleware('role:korcam,admin_partai')->group(function () {
        Route::redirect('/', '/korcam/rekap')->name('index');
        Route::get('{jenis}', fn ($jenis) => redirect()->route('korcam.rekap.show', $jenis))->name('show');
        Route::get('{jenis}/export', fn ($jenis) => redirect()->route('korcam.rekap.export', $jenis))->name('export');
    });

    Route::prefix('admin/rekap')->name('admin.rekap.')->middleware('role:admin_partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\AdminController::class, 'index'])->name('index');
        Route::get('chart', [App\Http\Controllers\Rekap\AdminController::class, 'chartPage'])->name('chart');
        Route::get('chart/data', [App\Http\Controllers\Rekap\AdminController::class, 'chartData'])->name('chart.data');
        Route::get('export/download', [App\Http\Controllers\Rekap\AdminController::class, 'exportDownload'])->name('export.download');
        Route::get('export/tps-belum-masuk', [App\Http\Controllers\Rekap\AdminController::class, 'exportMissingTps'])->name('export.missing-tps');
        Route::get('export/tps-perlu-dicek', [App\Http\Controllers\Rekap\AdminController::class, 'exportReviewTps'])->name('export.review-tps');
        Route::post('{jenis}/tps/{tps}/review-status', [App\Http\Controllers\Rekap\AdminController::class, 'updateTpsReviewStatus'])->name('review-status');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\AdminController::class, 'export'])->name('export');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\AdminController::class, 'show'])->name('show');
    });
});
