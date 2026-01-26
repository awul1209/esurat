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
        // Kita beri default 'Terkirim' (artinya baru dikirim Satker, belum diapa-apain BAU)
        $table->string('status')->default('Terkirim')->after('file_surat')->nullable(); 
    });
}

public function down()
{
    Schema::table('surat_keluars', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
