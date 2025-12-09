import './bootstrap';

document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. Fungsi Toggle Terpusat ---
    // BARU: Kita buat fungsi terpisah agar bisa dipakai oleh 2 tombol
    const toggleSidebar = function(e) {
        e.preventDefault();
        // Toggle class 'sidebar-toggled' di elemen #app
        document.getElementById('app').classList.toggle('sidebar-toggled');
    };

    // --- 2. Tombol Buka Sidebar (di Navbar) ---
    // Ini adalah tombol Anda yang sudah ada
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        // PERBAIKAN: Sekarang memanggil fungsi terpusat
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // --- 3. Tombol Tutup Sidebar (Tombol 'X' di dalam sidebar) ---
    // BARU: Ini adalah tombol baru yang Anda tambahkan di app.blade.php
    const sidebarClose = document.getElementById('sidebarClose');
    if (sidebarClose) {
        // PERBAIKAN: Memanggil fungsi terpusat yang sama
        sidebarClose.addEventListener('click', toggleSidebar);
    }

    // --- 4. Logika Awal Saat Load Halaman ---
    // PERBAIKAN: Blok 'else' dihapus.
    // SCSS Anda (app.scss) sudah benar menangani sidebar 
    // agar tersembunyi di mobile secara default.
    // Kode 'else' sebelumnya justru membukanya saat load.
    if (window.innerWidth >= 769) {
        // Jangan toggle jika di desktop (biarkan default)
    }
    // Blok 'else' sengaja dihapus untuk memperbaiki bug di mobile
});