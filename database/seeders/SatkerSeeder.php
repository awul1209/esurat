<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Satker;

class SatkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Satker::query()->delete();

        // Perhatikan, semua baris sekarang punya 'nama_satker' dan 'singkatan'
        // yang tidak punya singkatan diisi null
        $satkers = [
            // Rektorat & Pimpinan
            ['nama_satker' => 'Rektor', 'singkatan' => null],
            ['nama_satker' => 'Wakil Rektor I', 'singkatan' => null],
            ['nama_satker' => 'Wakil Rektor II', 'singkatan' => null],
            ['nama_satker' => 'Wakil Rektor III', 'singkatan' => null],
            ['nama_satker' => 'Sekretaris Rektor', 'singkatan' => null],
            ['nama_satker' => 'Kepala Sekretariatan', 'singkatan' => null],
            
            // Fakultas & Pascasarjana
            ['nama_satker' => 'Dekan FH', 'singkatan' => 'FH'],
            ['nama_satker' => 'Wadek I FH', 'singkatan' => 'FH'],
            ['nama_satker' => 'Wadek II FH', 'singkatan' => 'FH'],
            ['nama_satker' => 'Dekan FEB', 'singkatan' => 'FEB'],
            ['nama_satker' => 'Wadek I FEB', 'singkatan' => 'FEB'],
            ['nama_satker' => 'Wadek II FEB', 'singkatan' => 'FEB'],
            ['nama_satker' => 'Dekan FISIP', 'singkatan' => 'FISIP'],
            ['nama_satker' => 'Wadek I FISIP', 'singkatan' => 'FISIP'],
            ['nama_satker' => 'Wadek II FISIP', 'singkatan' => 'FISIP'],
            ['nama_satker' => 'Dekan FT', 'singkatan' => 'FT'],
            ['nama_satker' => 'Wadek I FT', 'singkatan' => 'FT'],
            ['nama_satker' => 'Wadek II FT', 'singkatan' => 'FT'],
            ['nama_satker' => 'Dekan FIK', 'singkatan' => 'FIK'],
            ['nama_satker' => 'Wadek I FIK', 'singkatan' => 'FIK'],
            ['nama_satker' => 'Wadek II FIK', 'singkatan' => 'FIK'],
            ['nama_satker' => 'Dekan FKIP', 'singkatan' => 'FKIP'],
            ['nama_satker' => 'Wadek I FKIP', 'singkatan' => 'FKIP'],
            ['nama_satker' => 'Wadek II FKIP', 'singkatan' => 'FKIP'],
            ['nama_satker' => 'Direktur PASCASARJANA', 'singkatan' => 'PASCASARJANA'],
            ['nama_satker' => 'Wadek I PASCASARJANA', 'singkatan' => 'PASCASARJANA'],
            ['nama_satker' => 'Wadek II PASCASARJANA', 'singkatan' => 'PASCASARJANA'],
            
            // Lembaga & Badan
            ['nama_satker' => 'Ketua Pusat Jaminan Mutu', 'singkatan' => null],
            ['nama_satker' => 'Ketua Satuan Pengendali Internal', 'singkatan' => null],
            ['nama_satker' => 'Kepala Lembaga Penelitian dan Pengamdian Kepada Masyarakat', 'singkatan' => 'LPPM'],
            ['nama_satker' => 'Kepala Lembaga Bantuan Hukum', 'singkatan' => 'LBH'],
            ['nama_satker' => 'Kepala Badan Pengelola usaha', 'singkatan' => null],

            // Biro
            ['nama_satker' => 'Kepala Biro Administrasi Akademik dan Kemahasiswaan', 'singkatan' => null],
            ['nama_satker' => 'Kepala Biro Administrasi Umum', 'singkatan' => 'BAU'],
            ['nama_satker' => 'Kepala Biro Administrasi Keuangan', 'singkatan' => null],
            
            // UPT (Unit Pelaksana Teknis)
            ['nama_satker' => 'Kepala Biro Administrasi Perencanaan, Sistem Informasi dan Pangkalan Data', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Perpustakaan', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Laboratorium/Studio', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Pusat Bahasa', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Pusat Layanan Karier dan Konseling', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Pusat Layanan Kesehatan', 'singkatan' => null],
            ['nama_satker' => 'Kepala UPT Penerimaan Mahasiswa Baru', 'singkatan' => null],
        ];

        DB::table('satkers')->insert($satkers);
    }
}