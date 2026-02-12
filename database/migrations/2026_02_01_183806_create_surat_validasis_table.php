<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('surat_validasis', function (Blueprint $table) {
        $table->id();
        // Menghubungkan ke tabel surat_keluars
        $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->onDelete('cascade');
        // Menghubungkan ke tabel users (pimpinan yang dipilih)
        $table->foreignId('pimpinan_id')->constrained('users')->onDelete('cascade');
        // Status validasi
        $table->enum('status', ['pending', 'setuju', 'revisi'])->default('pending');
        // Catatan jika pimpinan menolak atau memberi arahan revisi
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_validasis');
    }
};
