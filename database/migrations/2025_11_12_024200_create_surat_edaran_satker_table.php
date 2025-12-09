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
        // Tabel ini melacak surat edaran/umum ke setiap Satker
        Schema::create('surat_edaran_satker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->onDelete('cascade');
            $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
            
            // Status:
            // 'terkirim' = Satker sudah terima, tapi belum di-share
            // 'diteruskan_internal' = Satker sudah me-broadcast ke pegawainya
            $table->enum('status', ['terkirim', 'diteruskan_internal'])->default('terkirim');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_edaran_satker');
    }
};