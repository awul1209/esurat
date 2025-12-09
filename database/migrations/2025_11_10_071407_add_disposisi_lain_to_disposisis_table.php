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
    Schema::table('disposisis', function (Blueprint $table) {
        // Tambahkan kolom baru setelah 'catatan_rektor'
        $table->text('disposisi_lain')->nullable()->after('catatan_rektor');
    });
}

public function down(): void
{
    Schema::table('disposisis', function (Blueprint $table) {
        $table->dropColumn('disposisi_lain');
    });
}
};
