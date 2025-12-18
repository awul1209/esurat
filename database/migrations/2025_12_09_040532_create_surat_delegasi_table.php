<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_delegasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pegawai Tujuan
            $table->string('status')->default('belum_dibaca'); // Status per pegawai
            $table->text('catatan')->nullable(); // Instruksi khusus per pegawai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_delegasi');
    }
};