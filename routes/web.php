<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DesaController;
use App\Http\Controllers\Admin\KecamatanController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PpkController;
use App\Http\Controllers\PpsController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/partai/login', [AuthController::class, 'showPartaiLogin'])->name('partai.login');
Route::post('/partai/login', [AuthController::class, 'loginPartai'])->name('partai.login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Protected ─────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/password', [AccountController::class, 'editPassword'])->name('password.edit');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('password.update');

    // Dashboard
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::get('/dashboard/komisioner', [DashboardController::class, 'komisioner'])->name('dashboard.komisioner');
    Route::get('/dashboard/partai', fn () => redirect()->route('admin.rekap.chart'))->name('dashboard.partai');
    Route::get('/dashboard/ppk', [DashboardController::class, 'ppk'])->name('dashboard.ppk');
    Route::get('/dashboard/pps', [DashboardController::class, 'pps'])->name('dashboard.pps');
    Route::get('/dashboard/kpps', [DashboardController::class, 'kpps'])->name('dashboard.kpps');

    // Clear session view — accessible by all roles
    Route::get('/clear-view-session', function () {
        session()->forget('admin_view_kecamatan_id');
        session()->forget('admin_view_desa_id');
        session()->forget('admin_view_tps_id');

        return response()->noContent();
    })->name('clear.view.session');

    // Dokumen — KPPS, PPS (view as kpps), & Admin
    Route::middleware('role:kpps,pps,ppk,admin')->group(function () {
        Route::get('/dokumen/upload', [DokumenController::class, 'uploadForm'])->name('dokumen.upload');
        Route::post('/dokumen/upload', [DokumenController::class, 'store'])->name('dokumen.store');
    });

    // Dokumen — PPS, PPK (view as pps), & Admin
    Route::middleware('role:pps,ppk,admin')->group(function () {
        Route::get('/dokumen/verifikasi', [DokumenController::class, 'indexPps'])->name('dokumen.pps');
        Route::post('/dokumen/{dokumen}/verifikasi', [DokumenController::class, 'verifikasi'])->name('dokumen.verifikasi');
    });

    // Dokumen — PPK & Admin
    Route::middleware('role:ppk,admin')->group(function () {
        Route::get('/dokumen/kecamatan', [DokumenController::class, 'indexPpk'])->name('dokumen.ppk');
    });

    // Dokumen — Preview & Download (semua role, guard di controller)
    Route::get('/dokumen/{dokumen}/preview', [DokumenController::class, 'preview'])->name('dokumen.preview');
    Route::get('/dokumen/{dokumen}/download', [DokumenController::class, 'download'])->name('dokumen.download');

    // PPK
    Route::middleware('role:ppk,admin')->group(function () {
        Route::get('/ppk/data-pps', [PpkController::class, 'dataPps'])->name('ppk.data-pps');
        Route::get('/ppk/view-pps/{desa}', [PpkController::class, 'viewPps'])->name('ppk.view-pps');
        Route::get('/ppk/upload', [DokumenController::class, 'uploadFormPpk'])->name('ppk.upload');
        Route::post('/ppk/upload', [DokumenController::class, 'storePpk'])->name('ppk.upload.store');
    });

    // PPS
    Route::middleware('role:pps,ppk,admin')->group(function () {
        Route::get('/pps/data-tps', [PpsController::class, 'dataTps'])->name('pps.data-tps');
        Route::get('/pps/view-tps/{tps}', [PpsController::class, 'viewTps'])->name('pps.view-tps');
    });

    // Admin & Komisioner read-only
    Route::middleware('role:admin,komisioner')->group(function () {
        Route::get('/dokumen/semua', [DokumenController::class, 'indexAdmin'])->name('dokumen.admin');
    });

    // Admin write actions
    Route::middleware('role:admin')->group(function () {
        Route::post('/dokumen/{dokumen}/verifikasi-admin', [DokumenController::class, 'verifikasiAdmin'])->name('dokumen.verifikasi.admin');
        Route::post('/dokumen/{dokumen}/restore', [DokumenController::class, 'restore'])->name('dokumen.restore')->middleware('role:admin');
        Route::post('admin/tools/backup', [App\Http\Controllers\Admin\ToolsController::class, 'backup'])->name('admin.tools.backup');
        Route::post('admin/tools/seed-partai', [App\Http\Controllers\Admin\ToolsController::class, 'seedPartai'])->name('admin.tools.seed-partai');
    });

    // Admin CRUD
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

    // ── SETUP MASTER DATA (Admin only) ──────────────────────
    Route::prefix('admin/setup')->name('admin.setup.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SetupController::class, 'index'])->name('index');
        Route::post('pemilu-settings', [App\Http\Controllers\Admin\SetupController::class, 'updatePemiluSettings'])->name('pemilu.settings');

        // PPWP
        Route::post('ppwp', [App\Http\Controllers\Admin\SetupController::class, 'storePpwp'])->name('ppwp.store');
        Route::delete('ppwp/{calon}', [App\Http\Controllers\Admin\SetupController::class, 'destroyPpwp'])->name('ppwp.destroy');

        // GUBUERNUR
        Route::post('gubernur', [App\Http\Controllers\Admin\SetupController::class, 'storeGubernur'])->name('gubernur.store');
        Route::delete('gubernur/{calon}', [App\Http\Controllers\Admin\SetupController::class, 'destroyGubernur'])->name('gubernur.destroy');

        // BUPATI
        Route::post('bupati', [App\Http\Controllers\Admin\SetupController::class, 'storeBupati'])->name('bupati.store');
        Route::delete('bupati/{calon}', [App\Http\Controllers\Admin\SetupController::class, 'destroyBupati'])->name('bupati.destroy');

        // DPD
        Route::post('dpd', [App\Http\Controllers\Admin\SetupController::class, 'storeDpd'])->name('dpd.store');
        Route::delete('dpd/{calon}', [App\Http\Controllers\Admin\SetupController::class, 'destroyDpd'])->name('dpd.destroy');

        // Partai + Caleg
        Route::post('partai', [App\Http\Controllers\Admin\SetupController::class, 'storePartai'])->name('partai.store');
        Route::delete('partai/{partai}', [App\Http\Controllers\Admin\SetupController::class, 'destroyPartai'])->name('partai.destroy');
        Route::post('partai/{partai}/caleg', [App\Http\Controllers\Admin\SetupController::class, 'storeCaleg'])->name('caleg.store');
        Route::delete('caleg/{caleg}', [App\Http\Controllers\Admin\SetupController::class, 'destroyCaleg'])->name('caleg.destroy');

        Route::post('dapil', [App\Http\Controllers\Admin\SetupController::class, 'storeDapil'])->name('dapil.store');
        Route::delete('dapil/{dapil}', [App\Http\Controllers\Admin\SetupController::class, 'destroyDapil'])->name('dapil.destroy');
        Route::post('kecamatan-dapil', [App\Http\Controllers\Admin\SetupController::class, 'assignDapil'])->name('kecamatan.dapil');

    });

    // ── REKAP INPUT (KPPS) ───────────────────────────────────
    Route::prefix('rekap')->name('rekap.')->middleware('role:kpps,pps,ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\KppsController::class, 'index'])->name('index');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\KppsController::class, 'export'])->name('export'); // ← sebelum {jenis}
        Route::get('{jenis}', [App\Http\Controllers\Rekap\KppsController::class, 'form'])->name('form');
        Route::post('{jenis}', [App\Http\Controllers\Rekap\KppsController::class, 'store'])->name('store');
        Route::post('{jenis}/finalisasi', [App\Http\Controllers\Rekap\KppsController::class, 'finalisasi'])->name('finalisasi');
    });

    // ── REKAP VIEW (PPS) ─────────────────────────────────────
    Route::prefix('pps/rekap')->name('pps.rekap.')->middleware('role:pps,ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\PpsController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\PpsController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\PpsController::class, 'export'])->name('export');
    });

    // ── REKAP VIEW (PPK) ─────────────────────────────────────
    Route::prefix('ppk/rekap')->name('ppk.rekap.')->middleware('role:ppk,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\PpkController::class, 'index'])->name('index');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\PpkController::class, 'show'])->name('show');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\PpkController::class, 'export'])->name('export');
    });

    // ── REKAP VIEW (Admin) ───────────────────────────────────
    Route::prefix('admin/rekap')->name('admin.rekap.')->middleware('role:admin,komisioner,partai')->group(function () {
        Route::get('/', [App\Http\Controllers\Rekap\AdminController::class, 'index'])->name('index');
        Route::get('chart', [App\Http\Controllers\Rekap\AdminController::class, 'chartPage'])->name('chart');
        Route::get('chart/data', [App\Http\Controllers\Rekap\AdminController::class, 'chartData'])->name('chart.data');
        Route::get('export/download', [App\Http\Controllers\Rekap\AdminController::class, 'exportDownload'])->name('export.download'); // ← tambah, SEBELUM {jenis}
        Route::post('{jenis}/cell-flag', [App\Http\Controllers\Rekap\AdminController::class, 'toggleCellFlag'])->middleware('role:admin')->name('cell-flag');
        Route::post('{jenis}/inline-update', [App\Http\Controllers\Rekap\AdminController::class, 'inlineUpdate'])->middleware('role:admin')->name('inline-update');
        Route::get('{jenis}/edit-tps/{tps}', [App\Http\Controllers\Rekap\AdminController::class, 'editTps'])->middleware('role:admin')->name('edit-tps');
        Route::get('{jenis}/export', [App\Http\Controllers\Rekap\AdminController::class, 'export'])->name('export');
        Route::get('{jenis}', [App\Http\Controllers\Rekap\AdminController::class, 'show'])->name('show');
    });
    Route::post('admin/rekap/{jenis}/unlock', [App\Http\Controllers\Rekap\AdminController::class, 'unlock'])
        ->middleware('role:admin')
        ->name('admin.rekap.unlock');
});
