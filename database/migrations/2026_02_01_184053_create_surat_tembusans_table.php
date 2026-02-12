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
    Schema::create('surat_tembusans', function (Blueprint $table) {
        $table->id();
        // Relasi ke surat keluar
        $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->onDelete('cascade');
        // Relasi ke unit/satker yang diberikan tembusan
        $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tembusans');
    }
};
