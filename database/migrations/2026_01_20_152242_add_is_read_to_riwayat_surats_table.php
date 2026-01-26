<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up() {
    Schema::table('riwayat_surats', function (Blueprint $table) {
        $table->integer('is_read')->default(0)->comment('0: Menunggu, 2: Selesai');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_surats', function (Blueprint $table) {
            //
        });
    }
};
