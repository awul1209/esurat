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
    Schema::table('surat_keluars', function (Blueprint $table) {
        // Kolom untuk menampung input manual (Misal: "Dinas Pendidikan")
        $table->string('tujuan_luar')->nullable()->after('perihal'); 
        
        // Kolom penanda tipe (Internal/Eksternal) jika belum ada
        // $table->string('tipe_kirim')->default('internal')->after('id'); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            //
        });
    }
};
