<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_surats_table.php
    public function up(): void
    {
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            
            // Data dari formulir
            $table->string('surat_dari');
            $table->date('tanggal_surat');
            $table->string('nomor_surat');
            $table->string('perihal');
            $table->date('diterima_tanggal');
            $table->string('no_agenda')->unique(); // Nomor agenda dari BAU
            $table->string('sifat'); // Mis: Asli / Tembusan

            // Metadata Sistem
            $table->enum('tipe_surat', ['internal', 'eksternal']);
            $table->string('file_surat'); // Path ke file PDF/JPG yang di-upload
            
            // Untuk melacak posisi surat
            $table->string('status'); // Mis: 'di_bau', 'di_admin_rektor', 'didisposisi', 'selesai'
            
            // Siapa yang menginput pertama kali (BAU atau Satker A)
            $table->foreignId('user_id')->constrained('users'); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
