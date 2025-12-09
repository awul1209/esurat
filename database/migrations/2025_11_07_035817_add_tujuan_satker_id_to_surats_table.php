<?php
// database/migrations/xxxx_add_tujuan_satker_id_to_surats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            // Tambahkan kolom ini setelah 'sifat'
            $table->foreignId('tujuan_satker_id')
                  ->nullable() // Mungkin ada surat yg tujuannya Rektor (bukan satker)
                  ->constrained('satkers') // Terhubung ke tabel 'satkers'
                  ->onDelete('set null') // Jika satker dihapus, suratnya tidak ikut terhapus
                  ->after('sifat'); 
        });
    }

    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->dropForeign(['tujuan_satker_id']);
            $table->dropColumn('tujuan_satker_id');
        });
    }
};
