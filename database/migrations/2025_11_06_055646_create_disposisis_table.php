<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_disposisis_table.php
public function up(): void
{
    Schema::create('disposisis', function (Blueprint $table) {
        $table->id();
        $table->foreignId('surat_id')->constrained('surats')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // ID Admin Rektor
        
        // Hasil disposisi
        $table->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasis');
        $table->foreignId('tujuan_satker_id')->nullable()->constrained('satkers');
        
        $table->text('catatan_rektor')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus dalam urutan terbalik
        Schema::dropIfExists('disposisi_klasifikasi');
        Schema::dropIfExists('disposisi_satker');
        Schema::dropIfExists('disposisis');
    }
};
