<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @stack('styles')
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app" class="d-flex">
        
<div class="sidebar-wrapper" id="sidebar-wrapper">
    <button class="btn-close d-lg-none" id="sidebarClose"></button>
    <div class="sidebar-heading text-center py-3 fs-5 border-bottom">
        <i class="bi bi-envelope-paper-fill me-2"></i>
        <span>e-Surat UNIJA</span>
    </div>
    
    <div class="list-group list-group-flush my-3">
        
        {{-- Link Dashboard Utama (Struktur Asli Anda) --}}
        <a href="{{ route('home') }}" class="list-group-item list-group-item-action {{ Request::routeIs('home') ? 'active' : '' }}">
            <i class="bi bi-house-door-fill me-2"></i> Dashboard
        </a>

        {{-- ==================================================== --}}
        {{-- MENU KHUSUS ADMIN BAU (TIDAK BERUBAH) --}}
        {{-- ==================================================== --}}
        @if(Auth::user()->role == 'bau')
            
         {{-- 1. SURAT MASUK (DROPDOWN) --}}
            @php
                // Cek apakah sedang di halaman surat masuk (Tapi BUKAN di Inbox BAU)
                $smActive = Request::routeIs('bau.surat.*') && !Request::routeIs('bau.surat.untukBau');
            @endphp
            <a href="#menuSuratMasukBau" data-bs-toggle="collapse" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $smActive ? 'active' : '' }}">
                <span><i class="bi bi-inbox-fill me-2"></i> Surat Masuk</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ $smActive ? 'show' : '' }}" id="menuSuratMasukBau">
                <div class="list-group list-group-flush submenu bg-light">
                    {{-- Sub: Eksternal --}}
                    <a href="{{ route('bau.surat.eksternal') }}" 
                       class="list-group-item list-group-item-action ps-5 border-0 {{ Request::routeIs('bau.surat.eksternal') ? 'fw-bold text-primary' : '' }}">
                        <i class="bi bi-box-arrow-in-down me-2"></i> Eksternal
                    </a>
                    {{-- Sub: Internal --}}
                    <a href="{{ route('bau.surat.internal') }}" 
                       class="list-group-item list-group-item-action ps-5 border-0 {{ Request::routeIs('bau.surat.internal') ? 'fw-bold text-primary' : '' }}">
                        <i class="bi bi-arrow-left-right me-2"></i> Internal
                    </a>
                </div>
            </div>

   

            {{-- 3. DISPOSISI (TETAP) --}}
            <a href="{{ route('bau.disposisi.index') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('bau.disposisi.index') ? 'active' : '' }}">
                <i class="bi bi-pencil-square me-2"></i> Disposisi (dari Rektor)
            </a>

            {{-- 4. RIWAYAT TERUSAN (TETAP) --}}
            <a href="{{ route('bau.riwayat.index') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('bau.riwayat.index') ? 'active' : '' }}">
                <i class="bi bi-send-check-fill me-2"></i> Riwayat Terusan
            </a>

            {{-- 5. SURAT KELUAR (DROPDOWN) --}}
            @php
                $skActive = Request::routeIs('bau.surat-keluar.*');
            @endphp
            <a href="#menuSuratKeluarBau" data-bs-toggle="collapse" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $skActive ? 'active' : '' }}">
                <span><i class="bi bi-send-fill me-2"></i> Surat Keluar</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ $skActive ? 'show' : '' }}" id="menuSuratKeluarBau">
                <div class="list-group list-group-flush submenu bg-light">
                    {{-- Sub: Eksternal --}}
                    <a href="{{ route('bau.surat-keluar.eksternal') }}" 
                       class="list-group-item list-group-item-action ps-5 border-0 {{ Request::routeIs('bau.surat-keluar.eksternal') ? 'fw-bold text-primary' : '' }}">
                        <i class="bi bi-globe me-2"></i> Eksternal
                    </a>
                    {{-- Sub: Internal --}}
                    <a href="{{ route('bau.surat-keluar.internal') }}" 
                       class="list-group-item list-group-item-action ps-5 border-0 {{ Request::routeIs('bau.surat-keluar.internal') ? 'fw-bold text-primary' : '' }}">
                        <i class="bi bi-arrows-expand me-2"></i> Internal
                    </a>
                </div>
            </div>

                     {{-- 2. INBOX BAU (LANGSUNG) --}}
<a href="{{ route('bau.inbox') }}" 
   class="list-group-item list-group-item-action {{ Request::routeIs('bau.inbox*') ? 'active' : '' }}">
    <i class="bi bi-folder-symlink-fill me-2"></i> Inbox BAU
</a>

            {{-- 6. MASTER DATA (TETAP) --}}
            <a href="#menuMasterData" data-bs-toggle="collapse" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="bi bi-gear-fill me-2"></i> Master Data</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ Request::routeIs('bau.manajemen-user.*') ? 'show' : '' }}" id="menuMasterData">
                <div class="list-group list-group-flush submenu bg-light">
                    <a href="{{ route('bau.manajemen-user.index') }}" 
                       class="list-group-item list-group-item-action ps-5 border-0 {{ Request::routeIs('bau.manajemen-user.*') ? 'fw-bold text-primary' : '' }}">
                        <i class="bi bi-person-vcard-fill me-2"></i> Manajemen User
                    </a>
                </div>
            </div>
            

        {{-- ==================================================== --}}
        {{-- MENU KHUSUS ADMIN REKTOR (TIDAK BERUBAH) --}}
        {{-- ==================================================== --}}
        @elseif(Auth::user()->role == 'admin_rektor')
            
        <a href="#menuSuratMasukRektor" data-bs-toggle="collapse" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="bi bi-inbox-fill me-2"></i> Surat Masuk</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            {{-- Logic: Tetap terbuka jika sedang di route suratmasuk apapun atau disposisi --}}
            <div class="collapse {{ (Request::routeIs('adminrektor.suratmasuk.*') || Request::routeIs('adminrektor.disposisi.show')) ? 'show' : '' }}" 
                 id="menuSuratMasukRektor">
                <div class="list-group list-group-flush submenu">
                    
                    {{-- A. Eksternal (Dari BAU) --}}
                    {{-- Asumsi: index default adalah eksternal --}}
                    <a href="{{ route('adminrektor.suratmasuk.index') }}" 
                       class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('adminrektor.suratmasuk.index') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down me-2"></i> Eksternal
                    </a>

                    {{-- B. Internal (Dari Satker Lain) --}}
                    {{-- Pastikan Route ini sudah dibuat --}}
                    <a href="{{ route('adminrektor.suratmasuk.internal') }}" 
                       class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('adminrektor.suratmasuk.internal') ? 'active' : '' }}">
                        <i class="bi bi-arrows-collapse me-2"></i> Internal
                    </a>
                </div>
            </div>

            {{-- 2. RIWAYAT DISPOSISI (SINGLE MENU) --}}
            <a href="{{ route('adminrektor.disposisi.riwayat') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('adminrektor.disposisi.riwayat') ? 'active' : '' }}">
                <i class="bi bi-clock-history me-2"></i> Riwayat Disposisi
            </a>

            {{-- 3. SURAT KELUAR (DROPDOWN) --}}
            <a href="#menuSuratKeluarRektor" data-bs-toggle="collapse" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="bi bi-send-fill me-2"></i> Surat Keluar</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ Request::routeIs('adminrektor.suratkeluar.*') ? 'show' : '' }}" id="menuSuratKeluarRektor">
                <div class="list-group list-group-flush submenu">
                    
                    {{-- A. Eksternal (Ke Luar Kampus) --}}
                    <a href="{{ route('adminrektor.suratkeluar.index') }}" 
                       class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('adminrektor.suratkeluar.index') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-up me-2"></i> Eksternal
                    </a>

                    {{-- B. Internal (Ke Satker Lain) --}}
                    {{-- Pastikan Route ini sudah dibuat --}}
                    <a href="{{ route('adminrektor.suratkeluar.internal') }}" 
                       class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('adminrektor.suratkeluar.internal') ? 'active' : '' }}">
                        <i class="bi bi-arrows-expand me-2"></i> Internal
                    </a>
                </div>
            </div>

            {{-- 4. MANAJEMEN AKUN (BARU) --}}
            {{-- Menggunakan route profil.edit yang umum digunakan untuk edit profil sendiri --}}
            <a href="{{ route('profil.edit') }}" 
               class="list-group-item list-group-item-action {{ Request::routeIs('profil.edit') ? 'active' : '' }}">
                <i class="bi bi-person-gear me-2"></i> Manajemen Akun
            </a>

        {{-- ==================================================== --}}
        {{-- (DIPERBARUI) MENU KHUSUS SATKER --}}
        {{-- ==================================================== --}}
        @elseif(Auth::user()->role == 'satker')
    
    {{-- Dropdown Surat Masuk --}}
    <a href="#menuSuratMasukSatker" data-bs-toggle="collapse" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox-fill me-2"></i> Surat Masuk</span>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse {{ (Request::routeIs('satker.surat-masuk.*') || Request::routeIs('satker.surat-masuk.internal')) ? 'show' : '' }}" id="menuSuratMasukSatker">
        <div class="list-group list-group-flush submenu">
            <a href="{{ route('satker.surat-masuk.eksternal') }}" class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('satker.surat-masuk.eksternal') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down me-2"></i> Eksternal
            </a>
            {{-- LINK BARU: INTERNAL --}}
            <a href="{{ route('satker.surat-masuk.internal') }}" class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('satker.surat-masuk.internal') ? 'active' : '' }}">
                <i class="bi bi-arrows-collapse me-2"></i> Internal
            </a>
        </div>
    </div>
    
    {{-- Dropdown Surat Keluar --}}
    <a href="#menuSuratKeluarSatker" data-bs-toggle="collapse" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="bi bi-send-fill me-2"></i> Surat Keluar</span>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse {{ Request::routeIs('satker.surat-keluar.*') ? 'show' : '' }}" id="menuSuratKeluarSatker">
        <div class="list-group list-group-flush submenu">
            {{-- LINK BARU: INTERNAL --}}
<a href="{{ route('satker.surat-keluar.internal') }}" 
   class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('satker.surat-keluar.internal*') ? 'active' : '' }}">
    <i class="bi bi-arrows-expand me-2"></i> Internal
</a>

<a href="{{ route('satker.surat-keluar.eksternal.index') }}" 
   class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('satker.surat-keluar.eksternal*') ? 'active' : '' }}">
    <i class="bi bi-box-arrow-up me-2"></i> Eksternal
</a>
        </div>
    </div>

        {{-- ==================================================== --}}
        {{-- (DIPERBARUI) MENU KHUSUS PEGAWAI --}}
        {{-- ==================================================== --}}
        @elseif(Auth::user()->role == 'pegawai')
            
             {{-- Dropdown Surat Masuk (Untuk SEMUA Pegawai) --}}
            <a href="#menuSuratMasukPegawai" data-bs-toggle="collapse" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="bi bi-inbox-fill me-2"></i> Surat Masuk</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ Request::routeIs('pegawai.surat-masuk.*') || Request::routeIs('pegawai.surat-umum.*') ? 'show' : '' }}" id="menuSuratMasukPegawai">
                <div class="list-group list-group-flush submenu">
                    <a href="{{ route('pegawai.surat-masuk.eksternal') }}" class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('pegawai.surat-masuk.eksternal') ? 'active' : '' }}">
                        <i class="bi bi-person-check-fill me-2"></i> Surat Untuk Saya
                    </a>
                    <a href="{{ route('pegawai.surat-umum.index') }}" class="list-group-item list-group-item-action ps-5 {{ Request::routeIs('pegawai.surat-umum.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill me-2"></i> Surat Umum
                    </a>
                </div>
            </div>

            {{-- 
              ====================================================
              PERBAIKAN: Menu BAPSI hanya muncul jika 
              user adalah bagian dari Satker BAPSI.
              (Ganti 'BAPSI' dengan singkatan Satker Anda yang benar jika perlu)
              ====================================================
            --}}
            @if(Auth::user()->satker && Auth::user()->satker->singkatan == 'BAPSI')
                {{-- Dropdown Surat BAPSI (Sesuai Rencana Anda) --}}
                <a href="#menuBapsiPegawai" data-bs-toggle="collapse" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-card-checklist me-2"></i> BAPSI</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse" id="menuBapsiPegawai">
                     <div class="list-group list-group-flush submenu">
                        <a href="#" class="list-group-item list-group-item-action ps-5 disabled">
                            <i class="bi bi-file-earmark-text me-2"></i> Surat BAPSI (Segera)
                        </a>
                    </div>
                </div>
            @endif

        @endif
    </div>
</div>
        
        <div id="page-content-wrapper" class="flex-grow-1">
            
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
                {{-- (Navbar Anda tidak berubah) --}}
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-person-circle me-1"></i>
                                {{ Auth::user()->name }} 
                                @if(Auth::user()->satker)
                                    ({{ Auth::user()->satker->nama_satker }})
                                @endif
                            </a>
                             <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item {{ Request::routeIs('profil.edit') ? 'active' : '' }}" href="{{ route('profil.edit') }}">
                                    <i class="bi bi-person-fill-gear me-2"></i> Edit Profil
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('logout') }}"
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
            </nav>

            <main class="py-3">
                @yield('content')
            </main>
        </div>

       @stack('scripts')
</body>
</html>