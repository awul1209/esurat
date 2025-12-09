<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Klasifikasi; // <-- Import model

class KlasifikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PERBAIKAN: 
        // Kita harus menyediakan 'kode_klasifikasi' dan 'nama_klasifikasi'
        
        $daftarKlasifikasi = [
            ['kode' => '001', 'nama' => 'Segera'],
            ['kode' => '002', 'nama' => 'Disposisi'],
            ['kode' => '003', 'nama' => 'Tindak Lanjut'],
            ['kode' => '004', 'nama' => 'Selesaikan'],
            ['kode' => '005', 'nama' => 'Pedomani'],
            ['kode' => '006', 'nama' => 'Sarankan'],
            ['kode' => '007', 'nama' => 'Untuk Diketahui'],
            ['kode' => '008', 'nama' => 'Untuk Diproses'],
            ['kode' => '009', 'nama' => 'Sampaikan Ybs.'],
            ['kode' => '010', 'nama' => 'Siapkan'],
            ['kode' => '011', 'nama' => 'Pertimbangkan'],
            ['kode' => '012', 'nama' => 'Agar Menghadap Saya'],
            ['kode' => '013', 'nama' => 'Periksa Disposisi Saya di Dalam'],
            ['kode' => '014', 'nama' => 'Agar Hadir'],
            ['kode' => '015', 'nama' => 'Kompulir'],
            ['kode' => '016', 'nama' => 'Agendakan'],
            ['kode' => '017', 'nama' => 'Laporkan Hasilnya'],
            ['kode' => '018', 'nama' => 'Untuk diwakili'],
            // Anda bisa tambahkan kode arsip surat resmi di sini nanti
            ['kode' => '420', 'nama' => 'Pendidikan'],
            ['kode' => '800', 'nama' => 'Kepegawaian'],
        ];

        // Loop dan masukkan ke database
        foreach ($daftarKlasifikasi as $klasifikasi) {
            Klasifikasi::create([
                'kode_klasifikasi' => $klasifikasi['kode'],
                'nama_klasifikasi' => $klasifikasi['nama']
            ]);
        }
    }
}