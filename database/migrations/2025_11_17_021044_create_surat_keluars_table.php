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
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique(); // Nomor surat resmi universitas
            $table->date('tanggal_surat');
            $table->string('tujuan_surat'); // Misal: "Dinas Pendidikan Kab. Sumenep"
            $table->text('perihal');
            $table->string('file_surat'); // Path ke file PDF final yang di-upload
            
            // Siapa yang membuat surat ini (Admin BAU)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keluars');
    }
};