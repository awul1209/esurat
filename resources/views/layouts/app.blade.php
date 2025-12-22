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
            padding: 10px 14px;
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
            margin-left: 10px;
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

        /* === SUBMENU (PERBAIKAN SESUAI REQUEST) === */
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
        /* Overlay hitam saat sidebar kebuka di HP */
        #sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 999; /* Di bawah sidebar, di atas konten */
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 768px) {
            #sidebar-wrapper {
                transform: translateX(-100%); /* Default sembunyi ke kiri */
                width: var(--sidebar-width); /* Lebar tetap penuh */
            }
            #page-content-wrapper { margin-left: 0; width: 100%; }
            
            /* Saat Toggled (Munculkan Sidebar di HP) */
            #app.toggled #sidebar-wrapper {
                transform: translateX(0); /* Geser masuk */
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            
            /* Tampilkan overlay saat toggled */
            #app.toggled #sidebar-overlay {
                display: block;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    
    {{-- OVERLAY UNTUK HP (KLIK UNTUK TUTUP) --}}
    <div id="sidebar-overlay"></div>

    <div id="app">
        
        {{-- SIDEBAR --}}
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
                        $smActive = Request::routeIs('bau.surat.*');
                        $isEksternalActive = Request::routeIs('bau.surat.eksternal') || (Request::routeIs('bau.surat.create') && request('type') == 'eksternal');
                        $isInternalActive  = Request::routeIs('bau.surat.internal') || (Request::routeIs('bau.surat.create') && request('type') == 'internal');
                    @endphp
                    
                    <a href="#menuSuratMasukBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $smActive ? 'active' : '' }}" aria-expanded="{{ $smActive ? 'true' : 'false' }}">
                        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $smActive ? 'show' : '' }}" id="menuSuratMasukBau">
                        <div class="list-group list-group-flush submenu">
                            {{-- Tambahkan Icon Dot (bi-circle-fill) --}}
                            <a href="{{ route('bau.surat.eksternal') }}" class="list-group-item list-group-item-action {{ $isEksternalActive ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('bau.surat.internal') }}" class="list-group-item list-group-item-action {{ $isInternalActive ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('bau.disposisi.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.disposisi.index') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> <span class="menu-text">Disposisi Rektor</span>
                    </a>

                    <a href="{{ route('bau.riwayat.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.riwayat.index') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> <span class="menu-text">Riwayat Terusan</span>
                    </a>

                    @php $skActive = Request::routeIs('bau.surat-keluar.*'); @endphp
                    <a href="#menuSuratKeluarBau" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ $skActive ? 'active' : '' }}" aria-expanded="{{ $skActive ? 'true' : 'false' }}">
                        <i class="bi bi-send-fill"></i> <span class="menu-text">Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ $skActive ? 'show' : '' }}" id="menuSuratKeluarBau">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.surat-keluar.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat-keluar.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>
                            <a href="{{ route('bau.surat-keluar.internal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.surat-keluar.internal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('bau.inbox') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.inbox*') ? 'active' : '' }}">
                        <i class="bi bi-folder-symlink-fill"></i> <span class="menu-text">Inbox BAU</span>
                    </a>

                    <a href="#menuMasterData" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i> <span class="menu-text">Master Data</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ Request::routeIs('bau.manajemen-user.*') ? 'show' : '' }}" id="menuMasterData">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('bau.manajemen-user.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('bau.manajemen-user.*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Manajemen User
                            </a>
                        </div>
                    </div>

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
                    <a href="{{ route('adminrektor.disposisi.riwayat') }}" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.disposisi.riwayat') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> <span class="menu-text">Riwayat Disposisi</span>
                    </a>
                    <a href="#menuSuratKeluarRektor" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.suratkeluar.*') ? 'active' : '' }}">
                        <i class="bi bi-send-fill"></i> <span class="menu-text">Surat Keluar</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ Request::routeIs('adminrektor.suratkeluar.*') ? 'show' : '' }}" id="menuSuratKeluarRektor">
                        <div class="list-group list-group-flush submenu">
                            {{-- MENU SURAT KELUAR EKSTERNAL --}}
                            <a href="{{ route('adminrektor.surat-keluar-eksternal.index') }}" 
                               class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.surat-keluar-eksternal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Eksternal
                            </a>

                            {{-- MENU SURAT KELUAR INTERNAL --}}
                            <a href="{{ route('adminrektor.surat-keluar-internal.index') }}" 
                               class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.surat-keluar-internal*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Internal
                            </a>
                        </div>
                    </div>
                    {{-- MANAJEMEN USER (PROFILE) UNTUK REKTOR --}}
                    <a href="{{ route('profil.edit') }}" class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i> <span class="menu-text">Manajemen User</span>
                    </a>

                {{-- SATKER --}}
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
                    {{-- MANAJEMEN USER (PROFILE) UNTUK SATKER --}}
                    <a href="{{ route('profil.edit') }}" class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i> <span class="menu-text">Manajemen User</span>
                    </a>

                {{-- PEGAWAI --}}
                @elseif(Auth::user()->role == 'pegawai')
                    <a href="#menuSuratMasukPegawai" data-bs-toggle="collapse" class="list-group-item list-group-item-action {{ Request::routeIs('pegawai.surat-masuk.*') || Request::routeIs('pegawai.surat-umum.*') ? 'active' : '' }}">
                        <i class="bi bi-inbox-fill"></i> <span class="menu-text">Surat Masuk</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </a>
                    <div class="collapse {{ Request::routeIs('pegawai.surat-masuk.*') || Request::routeIs('pegawai.surat-umum.*') ? 'show' : '' }}" id="menuSuratMasukPegawai">
                        <div class="list-group list-group-flush submenu">
                            <a href="{{ route('pegawai.surat-masuk.eksternal') }}" class="list-group-item list-group-item-action {{ Request::routeIs('pegawai.surat-masuk.eksternal') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Surat Untuk Saya
                            </a>
                            <a href="{{ route('pegawai.surat-umum.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('pegawai.surat-umum.index') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill"></i> Surat Umum
                            </a>
                        </div>
                    </div>
                    {{-- MANAJEMEN USER (PROFILE) UNTUK PEGAWAI --}}
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
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <span class="fw-bold small">{{ Auth::user()->name }}</span>
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
        </div>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
           const sidebarToggle = document.getElementById('sidebarToggle');
           const overlay = document.getElementById('sidebar-overlay');
           const appDiv = document.getElementById('app');

           // Fungsi Toggle Sidebar
           function toggleSidebar(e) {
               if(e) e.preventDefault();
               appDiv.classList.toggle('toggled');
           }

           // Klik tombol garis 3
           if (sidebarToggle) {
               sidebarToggle.addEventListener('click', toggleSidebar);
           }

           // Klik Overlay (Tutup Sidebar di HP)
           if (overlay) {
               overlay.addEventListener('click', function() {
                   appDiv.classList.remove('toggled'); // Paksa tutup
               });
           }
        });
    </script>
</body>
</html>