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


    // --- GRUP UNTUK ADMIN BAU ---
Route::middleware(['auth', 'role:bau'])->prefix('bau')->name('bau.')->group(function () {

    // ====================================================
    // 1. SURAT MASUK (DIPISAH INTERNAL & EKSTERNAL)
    // ====================================================
    Route::prefix('surat')->name('surat.')->group(function () {
        Route::get('/eksternal', [BauSuratController::class, 'indexEksternal'])->name('eksternal');
        Route::get('/internal', [BauSuratController::class, 'indexInternal'])->name('internal');

        // CRUD Standar
        Route::get('/create', [BauSuratController::class, 'create'])->name('create');
        Route::post('/', [BauSuratController::class, 'store'])->name('store');
        Route::get('/{surat}/edit', [BauSuratController::class, 'edit'])->name('edit');
        Route::put('/{surat}', [BauSuratController::class, 'update'])->name('update');
        Route::delete('/{surat}', [BauSuratController::class, 'destroy'])->name('destroy');

        // Aksi Khusus
        Route::post('/{surat}/forward-to-rektor', [BauSuratController::class, 'forwardToRektor'])->name('forwardToRektor');
        Route::post('/{surat}/forward-to-satker', [BauSuratController::class, 'forwardToSatker'])->name('forwardToSatker');
        Route::post('/{surat}/selesaikan-lainnya', [BauSuratController::class, 'selesaikanLainnya'])->name('selesaikanLainnya');
    });

    // ====================================================
    // 2. SURAT KELUAR (DIPISAH INTERNAL & EKSTERNAL)
    // ====================================================
    Route::prefix('surat-keluar')->name('surat-keluar.')->group(function () {
        Route::get('/eksternal', [BauSuratKeluarController::class, 'indexEksternal'])->name('eksternal');
        Route::get('/internal', [BauSuratKeluarController::class, 'indexInternal'])->name('internal');

        Route::get('/create', [BauSuratKeluarController::class, 'create'])->name('create');
        Route::post('/', [BauSuratKeluarController::class, 'store'])->name('store');
        Route::get('/{suratKeluar}/edit', [BauSuratKeluarController::class, 'edit'])->name('edit');
        Route::put('/{suratKeluar}', [BauSuratKeluarController::class, 'update'])->name('update');
        Route::delete('/{suratKeluar}', [BauSuratKeluarController::class, 'destroy'])->name('destroy');
    });

    // ====================================================
    // 3. MENU LAINNYA
    // ====================================================
    Route::get('/disposisi', [BauSuratController::class, 'showDisposisi'])->name('disposisi.index');
    Route::get('/riwayat', [BauSuratController::class, 'showRiwayat'])->name('riwayat.index');
    Route::get('/riwayat/{surat}/detail', [BauSuratController::class, 'showRiwayatDetail'])->name('riwayat.detail');

    Route::resource('manajemen-user', BauUserController::class)
         ->parameters(['manajemen-user' => 'manajemen_user']);

    // ====================================================
    // 4. INBOX BAU (SURAT MASUK KHUSUS BAU) & CRUD
    // ====================================================
    // Perhatikan: Saya menghapus awalan 'bau.' di dalam name()
    // Karena Group paling atas sudah mendefinisikan ->name('bau.')
    
    // Halaman Index (Hasil akhir: bau.inbox)
    Route::get('/inbox', [BauSuratController::class, 'indexUntukBau'])->name('inbox'); 
    
    // Simpan (Hasil akhir: bau.inbox.store)
    Route::post('/inbox/store', [BauSuratController::class, 'storeInbox'])->name('inbox.store'); 
    
    // Update (Hasil akhir: bau.inbox.update)
    Route::put('/inbox/{id}', [BauSuratController::class, 'updateInbox'])->name('inbox.update'); 
    
    // Hapus (Hasil akhir: bau.inbox.destroy)
    Route::delete('/inbox/{id}', [BauSuratController::class, 'destroyInbox'])->name('inbox.destroy');
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

        // 1. Route Surat Masuk Internal
    // (Anda perlu membuat method indexInternal di SuratMasukController)
    Route::get('/surat-masuk-internal', [App\Http\Controllers\AdminRektor\SuratMasukController::class, 'indexInternal'])->name('suratmasuk.internal');

    // 2. Route Surat Keluar Internal
    // (Anda perlu membuat method indexInternal di SuratKeluarController)
    Route::get('/surat-keluar-internal', [App\Http\Controllers\AdminRektor\SuratKeluarController::class, 'indexInternal'])->name('suratkeluar.internal');
    });

    // --- GRUP UNTUK ADMIN SATKER ---
   Route::prefix('satker')->name('satker.')->middleware(['auth', 'role:satker'])->group(function () {

    // --- SURAT MASUK EKSTERNAL ---
    Route::get('/surat-masuk-eksternal', [App\Http\Controllers\Satker\SuratController::class, 'indexMasukEksternal'])
        ->name('surat-masuk.eksternal');
    
    // Aksi Surat Masuk
    Route::post('/surat/{surat}/delegasi-ke-pegawai', [App\Http\Controllers\Satker\SuratController::class, 'delegasiKePegawai'])
        ->name('surat.delegasiKePegawai');
    Route::post('/surat/{surat}/arsipkan', [App\Http\Controllers\Satker\SuratController::class, 'arsipkan'])
        ->name('surat.arsipkan');
    Route::post('/surat/{surat}/broadcast-internal', [App\Http\Controllers\Satker\SuratController::class, 'broadcastInternal'])
        ->name('surat.broadcastInternal');

    // CRUD Surat Masuk Manual (Satker)
    Route::post('/surat/store', [App\Http\Controllers\Satker\SuratController::class, 'store'])->name('surat.store');
    Route::put('/surat/{id}', [App\Http\Controllers\Satker\SuratController::class, 'update'])->name('surat.update');
    Route::delete('/surat/{id}', [App\Http\Controllers\Satker\SuratController::class, 'destroy'])->name('surat.destroy');


    // --- SURAT MASUK INTERNAL ---
    Route::get('/surat-masuk-internal', [App\Http\Controllers\Satker\SuratInternalController::class, 'indexMasuk'])
        ->name('surat-masuk.internal');
    

    // --- SURAT KELUAR INTERNAL ---
    Route::get('/surat-keluar-internal', [App\Http\Controllers\Satker\SuratInternalController::class, 'indexKeluar'])
        ->name('surat-keluar.internal');
    Route::get('/surat-keluar-internal/create', [App\Http\Controllers\Satker\SuratInternalController::class, 'create'])
        ->name('surat-keluar.internal.create');
    Route::post('/surat-keluar-internal', [App\Http\Controllers\Satker\SuratInternalController::class, 'store'])
        ->name('surat-keluar.internal.store');
    Route::get('/surat-keluar-internal/{id}/edit', [App\Http\Controllers\Satker\SuratInternalController::class, 'edit'])
        ->name('surat-keluar.internal.edit');
    Route::put('/surat-keluar-internal/{id}', [App\Http\Controllers\Satker\SuratInternalController::class, 'update'])
        ->name('surat-keluar.internal.update');
    Route::delete('/surat-keluar-internal/{id}', [App\Http\Controllers\Satker\SuratInternalController::class, 'destroy'])
        ->name('surat-keluar.internal.destroy');


    // --- SURAT KELUAR EKSTERNAL (YANG DIPERBAIKI) ---
    // Hapus 'satker.' di key 'as', karena sudah mewarisi dari grup induk
    Route::group(['prefix' => 'surat-keluar/eksternal', 'as' => 'surat-keluar.eksternal.'], function () {
        
        Route::get('/', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'store'])->name('store');
        
        // Perbaiki parameter binding agar sesuai controller ({surat})
        Route::get('/{surat}/edit', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'edit'])->name('edit');
        Route::put('/{surat}', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'update'])->name('update');
        Route::delete('/{surat}', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'destroy'])->name('destroy');
    });

});

    // --- GRUP UNTUK PEGAWAI ---
Route::prefix('pegawai')->name('pegawai.')->group(function () {
        
        // Rute HALAMAN TABEL untuk "Surat Untuk Saya"
        Route::get('/surat-masuk-eksternal', [PegawaiSuratController::class, 'indexMasukEksternal'])
            ->name('surat-masuk.eksternal');

        // (BARU) Rute HALAMAN TABEL untuk "Surat Umum"
        Route::get('/surat-umum', [PegawaiSuratController::class, 'indexSuratUmum'])
            ->name('surat-umum.index');

            Route::post('/surat/{surat}/selesai', [PegawaiSuratController::class, 'selesai'])->name('surat.selesai');
    });

});