<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Import Controller Utama
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\ProfileController;

// Import Controller BAU
use App\Http\Controllers\Bau\SuratController as BauSuratController;
use App\Http\Controllers\Bau\SuratKeluarController as BauSuratKeluarController;
// ====================================================
// (PERBAIKAN 1) Import UserController yang benar
// ====================================================
use App\Http\Controllers\Bau\UserController as BauUserController;

// Import Controller AdminRektor
use App\Http\Controllers\AdminRektor\DisposisiController;
use App\Http\Controllers\AdminRektor\SuratMasukController as AdminRektorSuratMasukController;
use App\Http\Controllers\AdminRektor\SuratKeluarController as AdminRektorSuratKeluarController;

// Import Controller Satker & Pegawai
use App\Http\Controllers\Satker\DashboardController as SatkerDashboardController;
use App\Http\Controllers\Satker\SuratController as SatkerSuratController;
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboardController;
use App\Http\Controllers\Pegawai\SuratController as PegawaiSuratController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes(['register' => false]);
Route::get('/home', [HomeController::class, 'index'])->name('home');

// ===================================================================
// GRUP UTAMA: SEMUA RUTE YANG MEMERLUKAN LOGIN
// ===================================================================
Route::middleware('auth')->group(function () {

    // Rute Profil & Cetak
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::get('/cetak/disposisi/{surat}', [CetakController::class, 'cetakDisposisi'])
        ->name('cetak.disposisi');


    // --- GRUP UNTUK ADMIN BAU (TIDAK BERUBAH) ---
    Route::prefix('bau')->name('bau.')->group(function () {
    
        // (Rute-rute surat Anda yang sudah ada)
        Route::get('/surat/index', [BauSuratController::class, 'index'])->name('surat.index');
        Route::get('/surat/create', [BauSuratController::class, 'create'])->name('surat.create');
        Route::post('/surat/store', [BauSuratController::class, 'store'])->name('surat.store');
        Route::get('/disposisi', [BauSuratController::class, 'showDisposisi'])->name('disposisi.index');
        Route::get('/riwayat', [BauSuratController::class, 'showRiwayat'])->name('riwayat.index');
        Route::post('/surat/{surat}/forward-to-rektor', [BauSuratController::class, 'forwardToRektor'])
            ->name('surat.forwardToRektor');
        Route::post('/surat/{surat}/forward-to-satker', [BauSuratController::class, 'forwardToSatker'])
            ->name('surat.forwardToSatker');
       Route::resource('surat-keluar', BauSuratKeluarController::class)
             ->parameters(['surat_keluar' => 'suratKeluar']);
        Route::get('/riwayat/{surat}/detail', [BauSuratController::class, 'showRiwayatDetail'])
            ->name('riwayat.detail');
        Route::get('/surat/{surat}/edit', [BauSuratController::class, 'edit'])
            ->name('surat.edit');
        Route::put('/surat/{surat}/update', [BauSuratController::class, 'update'])
            ->name('surat.update');
        Route::delete('/surat/{surat}/destroy', [BauSuratController::class, 'destroy'])
            ->name('surat.destroy');

        // ====================================================
        // (PERBAIKAN 2) Gunakan 'BauUserController::class' yang sudah di-import
        // ====================================================
        Route::resource('manajemen-user', BauUserController::class)
             ->parameters(['manajemen_user' => 'manajemen_user']); // Menyesuaikan nama parameter

    });

    // --- GRUP UNTUK ADMIN REKTOR ---
    Route::prefix('admin-rektor')->name('adminrektor.')->group(function () {
        Route::get('/surat-masuk', [AdminRektorSuratMasukController::class, 'index'])
            ->name('suratmasuk.index');
        Route::get('/disposisi/{surat}', [DisposisiController::class, 'show'])->name('disposisi.show');
        Route::post('/disposisi/{surat}', [DisposisiController::class, 'store'])->name('disposisi.store');
        Route::get('/riwayat-disposisi', [DisposisiController::class, 'riwayat'])->name('disposisi.riwayat');
        Route::get('/surat-keluar', [AdminRektorSuratKeluarController::class, 'index'])->name('suratkeluar.index');
        Route::get('/surat-keluar/create', [AdminRektorSuratKeluarController::class, 'create'])->name('suratkeluar.create');
    });

    // --- GRUP UNTUK ADMIN SATKER ---
    Route::prefix('satker')->name('satker.')->group(function () {
        Route::get('/surat-masuk-eksternal', [SatkerSuratController::class, 'indexMasukEksternal'])
            ->name('surat-masuk.eksternal');
        Route::post('/surat/{surat}/delegasi-ke-pegawai', [SatkerSuratController::class, 'delegasiKePegawai'])
            ->name('surat.delegasiKePegawai');
            Route::post('/surat/{surat}/arsipkan', [SatkerSuratController::class, 'arsipkan'])
            ->name('surat.arsipkan');
        Route::post('/surat/{surat}/broadcast-internal', [SatkerSuratController::class, 'broadcastInternal'])
            ->name('surat.broadcastInternal');
            
    });

    // --- GRUP UNTUK PEGAWAI ---
Route::prefix('pegawai')->name('pegawai.')->group(function () {
        
        // Rute HALAMAN TABEL untuk "Surat Untuk Saya"
        Route::get('/surat-masuk-eksternal', [PegawaiSuratController::class, 'indexMasukEksternal'])
            ->name('surat-masuk.eksternal');

        // (BARU) Rute HALAMAN TABEL untuk "Surat Umum"
        Route::get('/surat-umum', [PegawaiSuratController::class, 'indexSuratUmum'])
            ->name('surat-umum.index');
    });

});