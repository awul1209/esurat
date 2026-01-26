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
    Schema::create('surat_keluar_rektor_tujuan', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel surat_keluars
        $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->onDelete('cascade');
        // Relasi ke tabel satkers
        $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keluar_rektor_tujuan');
    }
};
