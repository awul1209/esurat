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
        // Tambahkan kolom penerima_id setelah user_id
        // Kita buat nullable karena tidak semua aksi riwayat (seperti arsip) punya penerima spesifik
        $table->unsignedBigInteger('penerima_id')->nullable()->after('user_id');

        // Hubungkan ke tabel users
        $table->foreign('penerima_id')->references('id')->on('users')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('riwayat_surats', function (Blueprint $table) {
        $table->dropForeign(['penerima_id']);
        $table->dropColumn('penerima_id');
    });
}
};
