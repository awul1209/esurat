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
        $table->dropColumn('tujuan_wadek');
    });
}
// (Fungsi down() bisa Anda isi kebalikannya jika perlu)

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            //
        });
    }
};
