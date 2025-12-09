<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;   // <-- Import User
use App\Models\Satker;  // <-- Import Satker
use Illuminate\Support\Facades\Hash; // <-- Import Hash

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Panggil Seeder Satker dan Klasifikasi DULU
        $this->call([
            SatkerSeeder::class,
            KlasifikasiSeeder::class,
        ]);

        // 2. Buat User Admin BAU (Super Admin)
        $bauSatker = Satker::where('singkatan', 'BAU')->first();
        User::where('email', 'test@example.com')->delete();
        User::factory()->create([
            'name' => 'Admin BAU',
            'email' => 'bau@example.com', 
            'password' => Hash::make('password'), 
            'role' => 'bau', 
            'satker_id' => $bauSatker ? $bauSatker->id : null, 
        ]);

        // 3. Buat user Admin Rektor
        User::factory()->create([
            'name' => 'Admin Rektor',
            'email' => 'adminrektor@example.com', 
            'password' => Hash::make('password'),
            'role' => 'admin_rektor',
            'satker_id' => null, 
        ]);

        /*
        |--------------------------------------------------------------------------
        | TAMBAHAN: Buat User Satker untuk Testing
        |--------------------------------------------------------------------------
        */
        
        // Coba cari Satker "Fakultas Hukum" (atau satker lain yang Anda tahu ada)
        // Jika tidak ada, kita ambil Satker pertama (ID 1)
        $satkerContoh = Satker::where('singkatan', 'FH')->first() ?? Satker::find(1);

        if ($satkerContoh) {
            User::factory()->create([
                'name' => 'User Satker (Contoh)',
                'email' => 'satker@example.com', // <-- Email login Anda
                'password' => Hash::make('password'),
                'role' => 'satker',
                'satker_id' => $satkerContoh->id, // <-- Terhubung ke Satker
            ]);
        } else {
            // Jika SatkerSeeder Anda kosong, ini tidak akan berjalan
            // Pastikan SatkerSeeder Anda membuat data
            echo "Peringatan: Tidak ada data Satker (FH atau ID 1) ditemukan. User Satker tidak dibuat.\n";
        }

        /**
         * ====================================================
         * TAMBAHAN BARU: Buat User Pegawai (Bapak Johan)
         * ====================================================
         * Kita buat 'Bapak Johan' sebagai pegawai di Satker yang sama
         * dengan 'Kepala Satker (Contoh)' (misal: FH / ID 1)
         */
        if ($satkerContoh) {
            User::factory()->create([
                'name' => 'Bapak Johan (Pegawai)',
                'email' => 'pegawai@example.com', // <-- Email login baru
                'password' => Hash::make('password'),
                'role' => 'pegawai', // <-- Role baru
                'satker_id' => $satkerContoh->id, // <-- Terhubung ke Satker
            ]);
        } else {
             echo "Peringatan: Tidak ada data Satker (FH atau ID 1) ditemukan. User Pegawai tidak dibuat.\n";
        }
    }
}