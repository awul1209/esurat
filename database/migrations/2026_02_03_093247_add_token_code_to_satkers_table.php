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
    Schema::table('satkers', function (Blueprint $table) {
        // Menambahkan kolom untuk identitas token satker di API Kampus
        $table->string('token_code')->nullable()->unique()->after('nama_satker');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satkers', function (Blueprint $table) {
            //
        });
    }
};
