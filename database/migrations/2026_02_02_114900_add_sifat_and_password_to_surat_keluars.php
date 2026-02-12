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
        // Menambahkan kolom sifat surat
        $table->string('sifat')->nullable()->after('perihal'); 
        
        // Menambahkan kolom password untuk keperluan integrasi API
        $table->string('password')->nullable()->after('verifikasi_url');
    });
}

public function down(): void
{
    Schema::table('surat_keluars', function (Blueprint $table) {
        $table->dropColumn(['sifat', 'password']);
    });
}
};
