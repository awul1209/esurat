<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            // Menambahkan kolom untuk menyimpan link cek keabsahan dari API
            $table->string('verifikasi_url')->nullable()->after('file_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            //
        });
    }
};
