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
        // Menambah kolom via setelah kolom tujuan_luar
        $table->string('via')->nullable()->after('tujuan_luar'); 
        
        // Memastikan status bisa menampung 'pending', 'proses', 'selesai'
        // Jika kolom status sudah ada, kita biarkan atau modifikasi type-nya
        if (!Schema::hasColumn('surat_keluars', 'status')) {
            $table->string('status')->default('pending')->after('via');
        }
    });
}

public function down()
{
    Schema::table('surat_keluars', function (Blueprint $table) {
        $table->dropColumn(['via', 'status']);
    });
}
};
