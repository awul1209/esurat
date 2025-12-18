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
        // Kolom untuk membedakan apakah surat ini 'internal' (antar satker) atau 'eksternal' (pihak luar)
        $table->string('tipe_kirim')->default('eksternal')->after('nomor_surat'); // Values: internal, eksternal
        
        // Kolom relasi ke Satker Tujuan (Khusus Internal)
        $table->foreignId('tujuan_satker_id')->nullable()->after('tujuan_surat')->constrained('satkers')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('surat_keluars', function (Blueprint $table) {
        $table->dropForeign(['tujuan_satker_id']);
        $table->dropColumn(['tujuan_satker_id', 'tipe_kirim']);
    });
}
};
