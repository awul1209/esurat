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
    Schema::table('riwayat_surats', function (Blueprint $table) {
        // Tambahkan kolom surat_keluar_id setelah surat_id
        $table->unsignedBigInteger('surat_keluar_id')->nullable()->after('surat_id');
        
        // Opsional: Tambahkan foreign key agar relasi kuat
        $table->foreign('surat_keluar_id')->references('id')->on('surat_keluars')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('riwayat_surats', function (Blueprint $table) {
        $table->dropForeign(['surat_keluar_id']);
        $table->dropColumn('surat_keluar_id');
    });
}
};
