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
    Schema::table('disposisis', function (Blueprint $table) {
        // default: 'belum_diproses'. Nanti bisa berubah jadi 'selesai' atau 'arsip'
        $table->string('status_penerimaan')->default('belum_diproses')->after('disposisi_lain');
    });
}

public function down()
{
    Schema::table('disposisis', function (Blueprint $table) {
        $table->dropColumn('status_penerimaan');
    });
}
};
