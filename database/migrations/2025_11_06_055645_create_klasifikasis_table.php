<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_klasifikasis_table.php
public function up(): void
{
    Schema::create('klasifikasis', function (Blueprint $table) {
        $table->id();
        $table->string('kode_klasifikasi')->unique(); // Misal: "001"
        $table->string('nama_klasifikasi'); // Misal: "Umum"
        $table->text('deskripsi')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klasifikasis');
    }
};
