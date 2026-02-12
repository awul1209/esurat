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
    Schema::table('surat_keluars', function (Blueprint $table) {
        // Kolom untuk menyimpan token unik per surat
        $table->string('token', 64)->nullable()->unique()->after('verifikasi_url');
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
