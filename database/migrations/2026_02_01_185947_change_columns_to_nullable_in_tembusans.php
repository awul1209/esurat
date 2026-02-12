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
    Schema::table('surat_tembusans', function (Blueprint $table) {
        // Mengubah kolom menjadi boleh kosong
        $table->foreignId('satker_id')->nullable()->change();
        $table->foreignId('user_id')->nullable()->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tembusans', function (Blueprint $table) {
            //
        });
    }
};
