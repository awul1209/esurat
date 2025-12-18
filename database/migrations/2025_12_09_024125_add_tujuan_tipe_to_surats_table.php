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
            // Menambahkan kolom tujuan_tipe setelah kolom user_id (atau sesuaikan posisinya)
            // Enum: rektor, universitas, satker, pegawai, edaran_semua_satker
            $table->string('tujuan_tipe')->nullable()->after('user_id')
                  ->comment('rektor, universitas, satker, pegawai, edaran_semua_satker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->dropColumn('tujuan_tipe');
        });
    }
};