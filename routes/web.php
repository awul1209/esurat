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
use App\Http\Controllers\Bau\UserController as BauUserController;
use App\Http\Controllers\Bau\VerifikasiSuratRektorController;
use App\Http\Controllers\Bau\ArsipSuratRektorController;
use App\Http\Controllers\Bau\SuratKeluarInternalRektorController;

// Import Controller AdminRektor
use App\Http\Controllers\AdminRektor\DisposisiController;
use App\Http\Controllers\AdminRektor\SuratMasukController as AdminRektorSuratMasukController;
use App\Http\Controllers\AdminRektor\SuratKeluarInternalController;
// [PERBAIKAN] Hapus baris ini karena Admin Rektor pakai SuratKeluarInternalController & EksternalController
// use App\Http\Controllers\AdminRektor\SuratKeluarController as AdminRektorSuratKeluarController; 

// Import Controller Satker & Pegawai
use App\Http\Controllers\Satker\DashboardController as SatkerDashboardController;
use App\Http\Controllers\Satker\SuratController as SatkerSuratController;
use App\Http\Controllers\Satker\SuratInternalController as SatkerSuratInternalController; // Tambahkan ini agar lebih rapi
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboardController;
use App\Http\Controllers\Pegawai\SuratMasukPribadiController ;
use App\Http\Controllers\Pegawai\SuratMasukUmumController;
use App\Http\Controllers\Bau\TrashController;
use Illuminate\Http\Request;
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
// Gunakan {id} agar cocok dengan isi parameter di Controller
Route::get('/cetak/disposisi-satker/{id}', [CetakController::class, 'cetakDisposisiSatker'])
    ->name('cetak.disposisi.satker');
    // Rute Profil & Cetak
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::get('/cetak/disposisi/{surat}', [CetakController::class, 'cetakDisposisi'])
        ->name('cetak.disposisi');



    // --- GRUP UNTUK ADMIN BAU ---
    Route::middleware(['auth', 'role:bau'])->prefix('bau')->name('bau.')->group(function () {

        // 1. SURAT MASUK
        Route::prefix('surat')->name('surat.')->group(function () {
            Route::get('/eksternal', [BauSuratController::class, 'indexEksternal'])->name('eksternal');
            Route::get('/internal', [BauSuratController::class, 'indexInternal'])->name('internal');

            Route::get('/create', [BauSuratController::class, 'create'])->name('create');
            Route::post('/', [BauSuratController::class, 'store'])->name('store');
            Route::get('/{surat}/edit', [BauSuratController::class, 'edit'])->name('edit');
            Route::put('/{surat}', [BauSuratController::class, 'update'])->name('update');
            Route::delete('/{surat}', [BauSuratController::class, 'destroy'])->name('destroy');

            Route::post('/{surat}/forward-to-rektor', [BauSuratController::class, 'forwardToRektor'])->name('forwardToRektor');
            Route::post('/{surat}/forward-to-satker', [BauSuratController::class, 'forwardToSatker'])->name('forwardToSatker');
            Route::post('/{surat}/selesaikan-lainnya', [BauSuratController::class, 'selesaikanLainnya'])->name('selesaikanLainnya');
        });

        // 2. SURAT KELUAR
        Route::prefix('surat-keluar')->name('surat-keluar.')->group(function () {
            
            Route::get('/eksternal', [BauSuratKeluarController::class, 'indexEksternal'])->name('eksternal');
            Route::get('/internal', [BauSuratKeluarController::class, 'indexInternal'])->name('internal');

            Route::get('/create', [BauSuratKeluarController::class, 'create'])->name('create');
            Route::post('/', [BauSuratKeluarController::class, 'store'])->name('store');
            Route::get('/{suratKeluar}/edit', [BauSuratKeluarController::class, 'edit'])->name('edit');
            Route::put('/{suratKeluar}', [BauSuratKeluarController::class, 'update'])->name('update');
            Route::delete('/{suratKeluar}', [BauSuratKeluarController::class, 'destroy'])->name('destroy');
            Route::get('get-pegawai-by-satker', [BauSuratKeluarController::class, 'getPegawaiBySatker'])->name('get-pegawai-by-satker');
        });

        // 3. MENU LAINNYA
        Route::get('/disposisi', [BauSuratController::class, 'showDisposisi'])->name('disposisi.index');
        Route::get('/riwayat', [BauSuratController::class, 'showRiwayat'])->name('riwayat.index');
        Route::get('/riwayat/{surat}/detail', [BauSuratController::class, 'showRiwayatDetail'])->name('riwayat.detail');

        Route::post('manajemen-user/import', [BauUserController::class, 'import'])->name('manajemen-user.import');
        Route::resource('manajemen-user', BauUserController::class)->parameters(['manajemen-user' => 'manajemen_user']);

        // 4. INBOX BAU
        Route::get('/inbox', [BauSuratController::class, 'indexUntukBau'])->name('inbox'); 
        Route::post('/inbox/{id}/arsipkan', [BauSuratController::class, 'arsipkan'])->name('inbox.arsipkan');
        Route::post('/inbox/store', [BauSuratController::class, 'storeInbox'])->name('inbox.store'); 
        Route::put('/inbox/{id}', [BauSuratController::class, 'updateInbox'])->name('inbox.update'); 
        Route::delete('/inbox/{id}', [BauSuratController::class, 'destroyInbox'])->name('inbox.destroy');
        Route::post('/inbox/delegate', [BauSuratController::class, 'delegate'])->name('inbox.delegate');

        Route::post('/surat/check-duplicate', [BauSuratController::class, 'checkDuplicate'])->name('surat.checkDuplicate');
        Route::post('/surat-keluar/check-duplicate', [BauSuratKeluarController::class, 'checkDuplicate'])->name('surat-keluar.checkDuplicate');

        Route::get('/riwayat/export', [BauSuratController::class, 'exportRiwayatExcel'])->name('riwayat.export');
        Route::get('/surat-keluar/riwayat/{id}', [BauSuratKeluarController::class, 'getRiwayatLog'])->name('surat-keluar.riwayat');
        Route::get('/surat-keluar/export', [BauSuratKeluarController::class, 'exportExcel'])->name('surat-keluar.export');
        Route::get('/inbox/export', [BauSuratController::class, 'exportInbox'])->name('inbox.export');
        Route::get('/riwayat/{surat}/detail', [BauSuratController::class, 'showRiwayatDetailBau'])
    ->name('riwayat.detail'); // Hasilnya: bau.riwayat.detail

    // surat dari rektor bau
Route::get('/verifikasi-surat-rektor', [App\Http\Controllers\Bau\VerifikasiSuratRektorController::class, 'index'])->name('verifikasi-rektor.index');
    Route::post('/verifikasi-surat-rektor/teruskan/{id}', [App\Http\Controllers\Bau\VerifikasiSuratRektorController::class, 'teruskan'])->name('verifikasi-rektor.teruskan');

Route::get('/verifikasi-surat-rektor/log/{id}', [App\Http\Controllers\Bau\VerifikasiSuratRektorController::class, 'getLog'])->name('verifikasi-rektor.log');
// Tambahkan baris ini secara spesifik
    Route::get('/verifikasi-surat-rektor/export', [App\Http\Controllers\Bau\VerifikasiSuratRektorController::class, 'export'])
         ->name('verifikasi-rektor.export');   
         Route::post('/verifikasi-surat-rektor/proses/{id}', [VerifikasiSuratRektorController::class, 'proses'])->name('verifikasi-rektor.proses');
Route::post('/verifikasi-surat-rektor/selesai/{id}', [VerifikasiSuratRektorController::class, 'selesai'])->name('verifikasi-rektor.selesai');


// Arsip Surat Rektor
Route::get('/arsip-surat-rektor/eksternal', [App\Http\Controllers\Bau\ArsipSuratRektorController::class, 'eksternal'])->name('arsip-rektor.eksternal');
Route::get('/arsip-surat-rektor/eksternal/export', [App\Http\Controllers\Bau\ArsipSuratRektorController::class, 'exportEksternal'])->name('arsip-rektor.eksternal.export');
Route::get('/arsip-rektor/internal/export', [App\Http\Controllers\Bau\ArsipSuratRektorController::class, 'exportInternal'])->name('arsip-rektor.internal.export');
// Di dalam group BAU atau group Arsip Rektor
Route::get('/arsip-rektor/log/{id}', [ArsipSuratRektorController::class, 'getLog'])->name('arsip-rektor.log');
Route::get('/arsip-rektor/internal', [App\Http\Controllers\Bau\ArsipSuratRektorController::class, 'internal'])->name('arsip-rektor.internal');
Route::get('/arsip-rektor/log-internal/{id}', [ArsipSuratRektorController::class, 'getLogInternal'])->name('arsip-rektor.log-internal');
// internal surat keluar rektor
Route::get('/surat-internal-rektor', [SuratKeluarInternalRektorController::class, 'index'])->name('surat-internal-rektor.index');
    Route::post('/surat-internal-rektor/proses/{id}', [SuratKeluarInternalRektorController::class, 'proses'])->name('surat-internal-rektor.proses');
    Route::post('/surat-internal-rektor/teruskan/{id}', [SuratKeluarInternalRektorController::class, 'teruskan'])->name('surat-internal-rektor.teruskan');


// Group untuk Trash
    Route::prefix('trash')->name('trash.')->group(function() {
        Route::get('/', [TrashController::class, 'index'])->name('index');
        Route::post('/restore-masuk/{id}', [TrashController::class, 'restoreSuratMasuk'])->name('restore.masuk');
        Route::post('/restore-keluar/{id}', [TrashController::class, 'restoreSuratKeluar'])->name('restore.keluar');
        Route::delete('/force-delete/{id}/{type}', [TrashController::class, 'forceDelete'])->name('forceDelete');
    });
    });


    // --- GRUP UNTUK ADMIN REKTOR ---
    Route::prefix('admin-rektor')->name('adminrektor.')->group(function () {
        
        // Surat Masuk
        Route::get('/surat-masuk', [AdminRektorSuratMasukController::class, 'index'])->name('suratmasuk.index');
        Route::get('/surat-masuk-internal', [AdminRektorSuratMasukController::class, 'indexInternal'])->name('suratmasuk.internal');
           
        // Disposisi
        Route::get('/disposisi/{surat}', [DisposisiController::class, 'show'])->name('disposisi.show');
        Route::post('/disposisi/{surat}', [DisposisiController::class, 'store'])->name('disposisi.store');
        Route::get('/riwayat-disposisi', [DisposisiController::class, 'riwayat'])->name('disposisi.riwayat');
        Route::get('/riwayat-disposisi/export', [DisposisiController::class, 'exportRiwayat'])->name('disposisi.riwayat.export');
        Route::get('/riwayat-disposisi/detail/{id}', [DisposisiController::class, 'detail'])->name('disposisi.riwayat.detail');
        
        // Surat Keluar (Perbaikan: Menggunakan Controller Spesifik)
        // Jika Anda punya Controller umum, pastikan importnya benar. 
        // Tapi di bawah Anda mendefinisikan Resource, jadi route manual ini mungkin duplikat/salah import.
        // Saya disable route manual yang menyebabkan error "Class not exist"
        // Route::get('/surat-keluar', [AdminRektorSuratKeluarController::class, 'index'])->name('suratkeluar.index');
        // Route::get('/surat-keluar/create', [AdminRektorSuratKeluarController::class, 'create'])->name('suratkeluar.create');

        // Surat Keluar Internal
        Route::get('/surat-keluar-internal', [App\Http\Controllers\AdminRektor\SuratKeluarInternalController::class, 'indexInternal'])->name('suratkeluar.internal');
        Route::resource('surat-keluar-internal', App\Http\Controllers\AdminRektor\SuratKeluarInternalController::class);
        Route::get('surat-keluar-internal-export', [App\Http\Controllers\AdminRektor\SuratKeluarInternalController::class, 'export'])->name('surat-keluar-internal.export');
        
        // [PERBAIKAN] LOG RIWAYAT SURAT KELUAR INTERNAL ADMIN REKTOR

            Route::get('/surat-keluar-internal/riwayat/{id}', [SuratKeluarInternalController::class, 'getRiwayat'])
    ->name('surat-keluar-internal.riwayat');

        // Surat Keluar Eksternal
        Route::resource('surat-keluar-eksternal', App\Http\Controllers\AdminRektor\SuratKeluarEksternalController::class);
        Route::get('surat-keluar-eksternal-export', [App\Http\Controllers\AdminRektor\SuratKeluarEksternalController::class, 'export'])->name('surat-keluar-eksternal.export');

        // Arsip
        Route::get('arsip', [App\Http\Controllers\AdminRektor\ArsipController::class, 'index'])->name('arsip.index');
        Route::get('arsip/export', [App\Http\Controllers\AdminRektor\ArsipController::class, 'export'])->name('arsip.export');
        Route::get('/arsip/riwayat/{surat}', [App\Http\Controllers\AdminRektor\ArsipController::class, 'showRiwayatDetail'])->name('arsip.riwayat.detail');
        Route::get('/surat-keluar-eksternal/log/{id}', [App\Http\Controllers\AdminRektor\SuratKeluarEksternalController::class, 'getLog'])
         ->name('surat-keluar-eksternal.log');

    });


    // --- GRUP UNTUK ADMIN SATKER ---
    Route::prefix('satker')->name('satker.')->middleware(['auth', 'role:satker'])->group(function () {
Route::post('submit-delegasi-internal', [SatkerSuratInternalController::class, 'delegate'])
        ->name('surat-masuk.internal.delegate');
        // --- SURAT KELUAR INTERNAL ---
        Route::get('/surat-keluar-internal/export', [SatkerSuratInternalController::class, 'exportKeluar'])
            ->name('surat-keluar.internal.export');
        
        // ====================================================
        // [PERBAIKAN] RUTE LOG / RIWAYAT KHUSUS SATKER
        // ====================================================
        Route::get('/surat-keluar-internal/riwayat-status/{id}', [SatkerSuratInternalController::class, 'getRiwayatStatus'])
            ->name('surat-keluar.internal.riwayat-status');
        // ====================================================

        Route::get('/surat-keluar-internal', [SatkerSuratInternalController::class, 'indexKeluar'])->name('surat-keluar.internal');
        Route::get('/surat-keluar-internal/create', [SatkerSuratInternalController::class, 'create'])->name('surat-keluar.internal.create');
        Route::post('/surat-keluar-internal', [SatkerSuratInternalController::class, 'store'])->name('surat-keluar.internal.store');
        Route::get('/surat-keluar-internal/{id}/edit', [SatkerSuratInternalController::class, 'edit'])->name('surat-keluar.internal.edit');
        Route::put('/surat-keluar-internal/{id}', [SatkerSuratInternalController::class, 'update'])->name('surat-keluar.internal.update');
        Route::delete('/surat-keluar-internal/{id}', [SatkerSuratInternalController::class, 'destroy'])->name('surat-keluar.internal.destroy');


        // --- SURAT MASUK EKSTERNAL ---
        Route::get('/surat/riwayat-disposisi/{id}', [SatkerSuratController::class, 'getRiwayatDisposisi'])
            ->name('surat.riwayat_disposisi');
        Route::get('/surat-masuk-eksternal/export', [SatkerSuratController::class, 'exportMasukEksternal'])
            ->name('surat-masuk.eksternal.export');
        Route::get('/surat-masuk-eksternal', [SatkerSuratController::class, 'indexMasukEksternal'])
            ->name('surat-masuk.eksternal');
Route::post('/surat-masuk-eksternal/{surat}/delegasi', [SatkerSuratController::class, 'delegasiKePegawai'])
    ->name('surat-masuk.eksternal.delegasi');
        
        // Aksi Surat Masuk internal/eksternal
        Route::post('/surat/{surat}/delegasi-ke-pegawai', [SatkerSuratController::class, 'delegasiKePegawai'])->name('surat.delegasiKePegawai');
        Route::post('/surat/{surat}/arsipkan', [SatkerSuratController::class, 'arsipkan'])->name('surat.arsipkan');
        Route::post('/surat/{surat}/broadcast-internal', [SatkerSuratController::class, 'broadcastInternal'])->name('surat.broadcastInternal');


        // CRUD Surat Masuk Manual (Satker)
        Route::post('/surat/store', [SatkerSuratController::class, 'store'])->name('surat.store');
        Route::put('/surat/{id}', [SatkerSuratController::class, 'update'])->name('surat.update');
        Route::delete('/surat/{id}', [SatkerSuratController::class, 'destroy'])->name('surat.destroy');


        // --- SURAT MASUK INTERNAL ---

        Route::get('/surat-masuk-internal/export', [SatkerSuratInternalController::class, 'exportMasuk'])->name('surat-masuk.internal.export');
        Route::get('/surat-masuk-internal', [SatkerSuratInternalController::class, 'indexMasuk'])->name('surat-masuk.internal');
        

        Route::post('/surat-masuk-internal/store', [SatkerSuratInternalController::class, 'storeMasukManual'])->name('surat-masuk.internal.store');
        Route::put('/surat-masuk-internal/{id}', [SatkerSuratInternalController::class, 'updateMasukManual'])->name('surat-masuk.internal.update');
        Route::delete('/surat-masuk-internal/{id}', [SatkerSuratInternalController::class, 'destroyMasukManual'])->name('surat-masuk.internal.destroy');
        Route::post('/surat-masuk-internal/{id}/arsipkan', [SatkerSuratInternalController::class, 'arsipkan'])->name('surat-masuk.internal.arsipkan');
        
        Route::get('/surat-masuk-internal/riwayat/{id}', [SatkerSuratInternalController::class, 'getRiwayatMasuk'])
            ->name('surat-masuk.internal.riwayat');
     
        
        // --- SURAT KELUAR EKSTERNAL ---
        Route::group(['prefix' => 'surat-keluar/eksternal', 'as' => 'surat-keluar.eksternal.'], function () {
            Route::get('/export', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'export'])->name('export');
            Route::get('/', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'store'])->name('store');
            Route::get('/{surat}/edit', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'edit'])->name('edit');
            Route::put('/{surat}', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'update'])->name('update');
            Route::delete('/{surat}', [App\Http\Controllers\Satker\SuratKeluarEksternalController::class, 'destroy'])->name('destroy');
        });

       
    });
    Route::get('/get-pegawai-by-satker', function (Request $request) {
    $pegawai = \App\Models\User::where('satker_id', $request->satker_id)
                ->where('role', 'pegawai')
                ->get(['id', 'name']);
    return response()->json($pegawai);
})->name('api.get-pegawai-by-satker');


    // --- GRUP UNTUK PEGAWAI ---
    Route::prefix('pegawai')->name('pegawai.')->group(function () {
// Surat Pribadi (Internal & Eksternal yang didelegasikan ke user)
    Route::get('surat-masuk/pribadi', [SuratMasukPribadiController::class, 'indexPribadiIntEks'])->name('surat.pribadi');
    Route::post('surat-masuk/pribadi/{surat}/selesai', [SuratMasukPribadiController::class, 'selesai'])->name('surat.selesai');
Route::post('surat-pribadi/terima/{id}', [SuratMasukPribadiController::class, 'terimaSuratLangsung'])
        ->name('surat.terima-langsung');
    // Export
    Route::get('surat-masuk/pribadi/export', [SuratMasukPribadiController::class, 'exportExcel'])->name('surat.export');

       // Surat Umum (Surat Edaran/Internal Satker)
    Route::get('surat-masuk/umum', [SuratMasukUmumController::class, 'index'])->name('surat.umum');
    Route::get('surat-masuk/umum/export', [SuratMasukUmumController::class, 'exportExcel'])->name('surat.umum.export');
    });
 
    


});