<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
public function up()
{
    Schema::create('klasifikasi_surat', function (Blueprint $table) {
        $table->id();
        
        // Hubungkan ke tabel surats
        $table->foreignId('surat_id')->constrained('surats')->onDelete('cascade');
        
        // Hubungkan ke tabel klasifikasis (sesuaikan nama tabel klasifikasi Anda)
        $table->foreignId('klasifikasi_id')->constrained('klasifikasis')->onDelete('cascade');
        
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('klasifikasi_surat');
}
};
