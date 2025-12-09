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
        Schema::table('surats', function (Blueprint $table) {
            // Kolom ini akan menyimpan ID user (pegawai) yang dituju
            $table->foreignId('tujuan_user_id')
                  ->nullable() // Boleh kosong, karena tidak semua surat ke pegawai
                  ->constrained('users') // Terhubung ke tabel 'users'
                  ->onDelete('set null') // Jika user dihapus, surat tidak ikut terhapus
                  ->after('tujuan_satker_id'); // Letakkan setelah kolom 'tujuan_satker_id'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            // Ini adalah kebalikan dari 'up()' untuk rollback
            $table->dropForeign(['tujuan_user_id']);
            $table->dropColumn('tujuan_user_id');
        });
    }
};
