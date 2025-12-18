<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    // 1. Buat Tabel Pivot (Tetap sama)
    Schema::create('surat_keluar_internal_penerima', function (Blueprint $table) {
        $table->id();
        $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->onDelete('cascade');
        $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
        $table->timestamp('dibaca_pada')->nullable();
        $table->timestamps();
    });

    // 2. UPDATE KOLOM DI TABEL UTAMA (TAMBAHKAN tujuan_surat)
    Schema::table('surat_keluars', function (Blueprint $table) {
        // Ubah kedua kolom ini agar boleh NULL
        $table->string('tujuan_surat')->nullable()->change(); // <--- TAMBAHKAN BARIS INI
        $table->unsignedBigInteger('tujuan_satker_id')->nullable()->change();
    });
}

    public function down()
    {
        Schema::dropIfExists('surat_keluar_internal_penerima');
    }
};