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
    Schema::table('surat_keluar_internal_penerima', function (Blueprint $table) {
        // 0: Belum Baca, 1: Sudah Baca (Klik Detail), 2: Diarsipkan
        $table->tinyInteger('is_read')->default(0)->after('satker_id'); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluar_internal_penerima', function (Blueprint $table) {
            //
        });
    }
};
