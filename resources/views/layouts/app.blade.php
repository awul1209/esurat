<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @stack('styles')
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        /* === VARIABLES === */
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --sidebar-bg: #ffffff;
            --active-bg: #0d6efd; 
            --active-text: #ffffff;
            --text-color: #586a84;
            --hover-bg: #eef2f7;
            --border-color: #ececec;
            --content-padding: 10px 0px; 
        }

        body {
            background-color: #f5f7fb; 
            font-family: 'Nunito', sans-serif;
            overflow-x: hidden;
        }

        /* === SIDEBAR WRAPPER === */
        #sidebar-wrapper {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Transisi smooth */
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px 0 rgba(0,0,0,0.02);
        }

        /* === HEADER SIDEBAR === */
        .sidebar-heading {
            padding: 0 24px;
            display: flex;
            align-items: center;
            height: 70px; 
            border-bottom: 1px solid transparent;
        }

        .logo-box {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            color: white;
            border-radius: 10px;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        }

        .sidebar-title {
            font-weight: 800;
            font-size: 1.1rem;
            color: #2c3e50;
            margin-left: 12px;
            white-space: nowrap;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        /* === MENU LIST === */
        .list-group {
            padding: 10px 12px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        .list-group::-webkit-scrollbar { width: 5px; }
        .list-group::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        .list-group-item {
            border: none;
            background: transparent;
            color: var(--text-color);
            font-weight: 800;
            font-size: 1rem;
            padding: 11px 5px;
            margin-bottom: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .list-group-item i {
            font-size: 1.15rem;
            min-width: 30px; 
            display: flex;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .menu-text {
            margin-left: 5px;
            flex-grow: 1;
            transition: opacity 0.3s ease;
        }

        .chevron-icon {
            font-size: 0.75rem !important;
            opacity: 0.5;
            transition: transform 0.3s ease;
        }

        /* Hover & Active Items (Menu Utama) */
        .list-group-item:hover {
            background-color: var(--hover-bg);
            color: #2c3e50;
            transform: translateX(3px);
        }
        .list-group-item:hover i {
            transform: scale(1.1);
            color: var(--active-bg);
        }

        .list-group-item.active {
            background-color: var(--active-bg);
            color: var(--active-text);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
            transform: none;
        }
        .list-group-item.active i {
            color: white;
            transform: none;
        }
        .list-group-item.active .chevron-icon {
            opacity: 0.8;
            color: white;
        }

        /* === SUBMENU === */
        .submenu .list-group-item {
            padding-left: 14px;
            margin-left: 20px;
            font-size: 0.99rem; /* Font agak kecil dari menu utama */
            margin-bottom: 2px;
            opacity: 0.85;
            color: var(--text-color); /* Warna awal abu */
            background-color: transparent !important; /* Hapus background */
            box-shadow: none !important; /* Hapus shadow */
            border-radius: 0; /* Hapus radius */
            overflow: hidden;
            width: 90%;
        }
        
        /* Tambahkan Icon Dot Kecil untuk Submenu */
        .submenu .list-group-item i {
            font-size: 0.5rem; /* Icon dot kecil */
            min-width: 20px;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
        }

        /* Hover & Active Submenu: Cuma ganti warna teks */
        .submenu .list-group-item:hover,
        .submenu .list-group-item.active {
            color: var(--active-bg) !important; /* Warna Biru */
            opacity: 1;
            transform: translateX(5px); /* Geser dikit biar dinamis */
        }
        
        .submenu .list-group-item.active i,
        .submenu .list-group-item:hover i {
            color: var(--active-bg) !important; /* Icon ikut biru */
            transform: scale(1.2);
        }

        /* Rotate Chevron saat expand */
        .list-group-item[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }

        /* === CONTENT AREA === */
        #page-content-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
        }

        .navbar {
            padding: 0 var(--content-padding);
            height: 70px;
            background-color: #fff;
        }

        main {
            padding: var(--content-padding); 
            width: 100%;
        }

        /* === TOGGLED STATE (DESKTOP) === */
        @media (min-width: 769px) {
            #app.toggled #sidebar-wrapper {
                width: var(--sidebar-collapsed-width);
            }
            #app.toggled #page-content-wrapper {
                margin-left: var(--sidebar-collapsed-width);
                width: calc(100% - var(--sidebar-collapsed-width));
            }
            #app.toggled .sidebar-title,
            #app.toggled .menu-text,
            #app.toggled .chevron-icon {
                opacity: 0; pointer-events: none; display: none;
            }
            #app.toggled .sidebar-heading { padding: 0; justify-content: center; }
            #app.toggled .list-group { padding: 10px 8px; }
            #app.toggled .list-group-item { justify-content: center; padding: 12px 0; margin-bottom: 6px; }
            #app.toggled .list-group-item i { margin-right: 0; font-size: 1.3rem; }
            #app.toggled .collapse.show { display: none; }
        }

        /* === MOBILE RESPONSIVE & OVERLAY === */
        #sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 999; 
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 768px) {
            #sidebar-wrapper {
                transform: translateX(-100%); 
                width: var(--sidebar-width); 
            }
            #page-content-wrapper { margin-left: 0; width: 100%; }
            
            #app.toggled #sidebar-wrapper {
                transform: translateX(0); 
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            
            #app.toggled #sidebar-overlay {
                display: block;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    
    <div id="sidebar-overlay"></div>

    <div id="app">
        
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">
                <div class="logo-box"><i class="bi bi-envelope-paper-fill"></i></div>
                <span class="sidebar-title">e-Surat UNIJA</span>
            </div>
            
            <div class="list-group list-group-flush mt-2">
                {{-- Dashboard --}}
                <a href="{{ route('home') }}" class="list-group-item list-group-item-action {{ Request::routeIs('home') ? 'active' : '' }}">
                    <i class="bi bi-grid-fill"></i> <span class="menu-text">Dashboard</span>
                </a>

                {{-- ADMIN BAU --}}
                @if(Auth::user()->role == 'bau')
                    @php
                        // Logic Active Kelompok Surat BAU
                        $smActiveBau = Request::routeIs('bau.surat.*');
                        $skActiveBau = Request::routeIs('bau.surat-keluar.*');
                        $inboxActiveBau = Request::routeIs('bau.inbox*');

                        // Logic Active Kelompok Layanan Rektor
                        $skRektorActive = Request::routeIs('bau.verifikasi-rektor.*');
                        $disposisiActiveBau = Request::routeIs('bau.disposisi.*') || Request::routeIs('bau.riwayat.*');
                        $arsipRektorActive = Request::routeIs('bau.arsip-rektor.*');
                    @endphp

                    <div class="sidebar-heading mt-0 mb-0 small text-muted px-3 text-uppercase fw-bold" style="font-size: 10px;">Layanan BAU</div>
                    <a href="{{ route('bau.inbox') }}" class="list-group-item list-group-item-action {{ $inboxActiveBau ? 'active' : '' }}">
                        <i class="bi bi-folder-symlink-fill"></i> <span class="menu-text">Inbox BAU</span>
                    </a>
    

                    <a href="#menuSuratKeluarBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $skActiveBau ? 'active' : '' }}" aria-expanded="{{ $skActiveBau ? 'true' : 'false' }}">
                        <i class="bi bi-send-fill"></i> <span class="menu-text">Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $skActiveBau ? 'show' : '' }}" id="menuSuratKeluarBau">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.surat-keluar.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat-keluar.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('bau.surat-keluar.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat-keluar.internal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>



                    <div class="sidebar-heading mt-0 mb-0 small text-muted px-3 text-uppercase fw-bold" style="font-size: 10px;">Layanan Rektor</div>
                                    <a href="#menuSuratMasukBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $smActiveBau ? 'active' : '' }}" aria-expanded="{{ $smActiveBau ? 'true' : 'false' }}">
                        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk Rektor</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $smActiveBau ? 'show' : '' }}" id="menuSuratMasukBau">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.surat.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('bau.surat.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat.internal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>

                      <a href="#menuVerifikasiRektor" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $skRektorActive ? 'active' : '' }}" aria-expanded="{{ $skRektorActive ? 'true' : 'false' }}">
                        <i class="bi bi-shield-check"></i> <span class="menu-text">Surat Keluar Rektor</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $skRektorActive ? 'show' : '' }}" id="menuVerifikasiRektor">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.verifikasi-rektor.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.verifikasi-rektor.index') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
{{-- Update bagian Verifikasi Surat Rektor > Internal --}}
<a href="{{ route('bau.surat-internal-rektor.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat-internal-rektor.*') ? 'active' : '' }}">
    <i class="bi bi-circle-fill"></i> Internal
</a>
                        </div>
                    </div>

                    <a href="#menuDisposisiBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $disposisiActiveBau ? 'active' : '' }}" aria-expanded="{{ $disposisiActiveBau ? 'true' : 'false' }}">
                        <i class="bi bi-pencil-square"></i> <span class="menu-text">Disposisi Rektor</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $disposisiActiveBau ? 'show' : '' }}" id="menuDisposisiBau">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.disposisi.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.disposisi.index') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Antrean Disposisi
                            </a>
                            <a href="{{ route('bau.riwayat.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.riwayat.index') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Riwayat Terusan
                            </a>
                        </div>
                    </div>

                  

                    {{-- MENU BARU: ARSIP SURAT REKTOR --}}
                    <a href="#menuArsipRektorBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $arsipRektorActive ? 'active' : '' }}" aria-expanded="{{ $arsipRektorActive ? 'true' : 'false' }}">
                        <i class="bi bi-archive-fill"></i> <span class="menu-text">Arsip Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $arsipRektorActive ? 'show' : '' }}" id="menuArsipRektorBau">
                        <div class="list-group list-group-flush submenu">
                    <a href="{{ route('bau.arsip-rektor.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.arsip-rektor.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
<a href="{{ route('bau.arsip-rektor.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.arsip-rektor.internal') ? 'active' : '' }}">
    <i class="bi bi-circle-fill"></i> Internal
</a>
                        </div>
                    </div>

{{-- Pengaturan Section --}}
<div class="sidebar-heading mt-0 mb-1 small text-muted px-3 text-uppercase fw-bold" style="font-size: 10px;">Pengaturan</div>

{{-- Menu Master Data --}}
<a href="#menuMasterDataBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}" aria-expanded="{{ Request::routeIs('bau.manajemen-user.*') ? 'true' : 'false' }}">
    <i class="bi bi-gear-fill"></i> <span class="menu-text">Master Data</span>
    <i class="bi bi-chevron-down chevron-icon"></i>
</a>
<div class="collapse {{ Request::routeIs('bau.manajemen-user.*') ? 'show' : '' }}" id="menuMasterDataBau">
    <div class="list-group list-group-flush submenu">
        <a href="{{ route('bau.manajemen-user.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}">
            <i class="bi bi-circle-fill"></i> Data User
        </a>
    </div>
</div>

{{-- MENU BARU: Tempat Sampah (Diletakkan di bawah Master Data) --}}
<a href="{{ route('bau.trash.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.trash.*') ? 'active' : '' }}">
    <i class="bi bi-trash3-fill"></i> <span class="menu-text">Tempat Sampah</span>
    @php
        // Opsional: Menampilkan badge jumlah item di tempat sampah jika ingin lebih informatif
        $trashCount = \App\Models\Surat::onlyTrashed()->count() + \App\Models\SuratKeluar::onlyTrashed()->count();
    @endphp
    @if($trashCount > 0)
        <span class="badge rounded-pill bg-danger float-end" style="font-size: 10px;">{{ $trashCount }}</span>
    @endif
</a>


                {{-- MENU SATKER --}}
                @elseif(Auth::user()->role == 'satker')
                    <a href="#menuSuratMasukSatker" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ (Request::routeIs('satker.surat-masuk.*') || Request::routeIs('satker.surat-masuk.internal')) ? 'active' : '' }}">
                        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ (Request::routeIs('satker.surat-masuk.*') || Request::routeIs('satker.surat-masuk.internal')) ? 'show' : '' }}" id="menuSuratMasukSatker">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('satker.surat-masuk.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('satker.surat-masuk.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('satker.surat-masuk.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('satker.surat-masuk.internal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>

                    <a href="#menuSuratKeluarSatker" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('satker.surat-keluar.*') ? 'active' : '' }}">
                        <i class="bi bi-send-fill"></i> <span class="menu-text">Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ Request::routeIs('satker.surat-keluar.*') ? 'show' : '' }}" id="menuSuratKeluarSatker">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('satker.surat-keluar.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('satker.surat-keluar.internal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('satker.surat-keluar.eksternal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                        </div>
                    </div>

                    @if(Auth::user()->satker_id == 36)
                        <a href="#menuMasterDataSatker" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i> <span class="menu-text">Master Data</span>
                            <i class="bi bi-chevron-down chevron-icon"></i>
                        </a>
                        <div class="collapse {{ Request::routeIs('bau.manajemen-user.*') ? 'show' : '' }}" id="menuMasterDataSatker">
                            <div class="list-group list-group-flush submenu">
                                <a href="{{ route('bau.manajemen-user.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle-fill"></i> Data User
                                </a>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('profil.edit') }}" class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i> <span class="menu-text">Manajemen User</span>
                    </a>

                {{-- ADMIN REKTOR --}}
                @elseif(Auth::user()->role == 'admin_rektor')
                    <a href="#menuSuratMasukRektor" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ (Request::routeIs('adminrektor.suratmasuk.*') || Request::routeIs('adminrektor.disposisi.show')) ? 'active' : '' }}">
                        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ (Request::routeIs('adminrektor.suratmasuk.*') || Request::routeIs('adminrektor.disposisi.show')) ? 'show' : '' }}" id="menuSuratMasukRektor">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('adminrektor.suratmasuk.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.suratmasuk.index') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('adminrektor.suratmasuk.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.suratmasuk.internal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>
                      <a href="#menuSuratKeluarRektor" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.suratkeluar.*') ? 'active' : '' }}">
                        <i class="bi bi-send-fill"></i> <span class="menu-text">Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ Request::routeIs('adminrektor.suratkeluar.*') ? 'show' : '' }}" id="menuSuratKeluarRektor">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('adminrektor.surat-keluar-eksternal.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.surat-keluar-eksternal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('adminrektor.surat-keluar-internal.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.surat-keluar-internal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>
                    <a href="{{ route('adminrektor.arsip.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.arsip.index') ? 'active' : '' }}">
                        <i class="bi bi-archive-fill"></i> <span class="menu-text">Arsip Rektor</span>
                    </a>
                    <a href="{{ route('adminrektor.disposisi.riwayat') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.disposisi.riwayat') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> <span class="menu-text">Riwayat Disposisi</span>
                    </a>
                  
                    <a href="{{ route('profil.edit') }}" class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i> <span class="menu-text">Manajemen User</span>
                    </a>

                {{-- PEGAWAI --}}
              @elseif(Auth::user()->role == 'pegawai')
    {{-- Menu Surat Masuk --}}
    <a href="#menuSuratMasukPegawai" data-bs-toggle="collapse" 
       class="list-group-item list-group-item-action {{ Request::is('pegawai/surat-masuk*') ? 'active' : '' }}">
        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk</span>
        <i class="bi bi-chevron-down chevron-icon"></i>
    </a>
    <div class="collapse {{ Request::is('pegawai/surat-masuk*') ? 'show' : '' }}" id="menuSuratMasukPegawai">
        <div class="list-group list-group-flush submenu">
            {{-- Gabungan Internal & Eksternal --}}
            <a href="{{ route('pegawai.surat.pribadi') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('pegawai.surat.pribadi') ? 'active' : '' }}">
                <i class="bi bi-circle-fill"></i> Pribadi & Delegasi
            </a>
            {{-- Surat Edaran Satker --}}
            <a href="{{ route('pegawai.surat.umum') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('pegawai.surat.umum') ? 'active' : '' }}">
                <i class="bi bi-circle-fill"></i> Surat Umum
            </a>
        </div>
    </div>

    {{-- Manajemen Profil --}}
    <a href="{{ route('profil.edit') }}" class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
        <i class="bi bi-person-gear"></i> <span class="menu-text">Manajemen User</span>
    </a>
@endif
            </div>
        </div>
        
        {{-- PAGE CONTENT --}}
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light shadow-sm border-bottom">
                <div class="container-fluid p-0">
                    <button class="btn border-0" id="sidebarToggle" style="color: #6c757d;">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
        <li class="nav-item dropdown">
            <a id="navbarDropdown" class="nav-link dropdown-toggle text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 0.9rem; flex-shrink: 0;">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                
                <div class="d-flex flex-column align-items-start leading-tight">
                    <span class="fw-bold small lh-1">{{ Auth::user()->name }}</span>
                    <small class="text-muted fw-medium" style="font-size: 10px; margin-top: 2px;">
                        @if(Auth::user()->role == 'bau')
                            Admin BAU
                        @elseif(Auth::user()->role == 'admin_rektor')
                            Admin Rektor
                        @elseif(Auth::user()->role == 'satker')
                            Admin {{ Auth::user()->satker->nama_satker ?? 'Fakultas' }}
                        @elseif(Auth::user()->role == 'pegawai')
                            Pegawai {{ Auth::user()->satker->nama_satker ?? 'Unit' }}
                        @else
                            {{ ucfirst(Auth::user()->role) }}
                        @endif
                    </small>
                </div>
            </a>
            
            <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="navbarDropdown">
                <a class="dropdown-item small" href="{{ route('profil.edit') }}">
                    <i class="bi bi-person-circle me-2"></i> Edit Profil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item small text-danger" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</div>
                </div>
            </nav>

            <main>
                <div class="container-fluid p-0"> 
                    @yield('content')
                </div>
            </main>

            <footer class="footer mt-auto py-3 bg-light border-top">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">
                            Copyright &copy; {{ date('Y') }} 
                            <span class="fw-bold">E-Surat</span> &bull; 
                            Universitas Wiraraja
                        </div>
                        <div>
                            <a href="#" class="text-decoration-none text-muted me-2">Kebijakan Privasi</a>
                            &middot;
                            <span class="text-muted ms-2">Versi 1.0</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
    
    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
           const sidebarToggle = document.getElementById('sidebarToggle');
           const overlay = document.getElementById('sidebar-overlay');
           const appDiv = document.getElementById('app');

           function toggleSidebar(e) {
               if(e) e.preventDefault();
               appDiv.classList.toggle('toggled');
           }

           if (sidebarToggle) {
               sidebarToggle.addEventListener('click', toggleSidebar);
           }

           if (overlay) {
               overlay.addEventListener('click', function() {
                   appDiv.classList.remove('toggled');
               });
           }
        });
    </script>
</body>
</html>