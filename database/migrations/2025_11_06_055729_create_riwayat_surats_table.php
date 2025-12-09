<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_riwayat_surats_table.php
    public function up(): void
    {
        Schema::create('riwayat_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats');
            $table->foreignId('user_id'); // User yang melakukan aksi
            $table->string('status_aksi'); // Mis: "Diterima BAU", "Diteruskan ke Admin Rektor"
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_surats');
    }
};
