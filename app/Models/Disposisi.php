<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    // Izinkan kolom-kolom ini untuk diisi
    protected $fillable = [
        'surat_id', 
        'user_id', 
        'klasifikasi_id', 
        'tujuan_satker_id', 
        'catatan_rektor',
        'disposisi_lain'
    ];

    // Definisikan relasi
    public function surat() {
        return $this->belongsTo(Surat::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function klasifikasi() {
        return $this->belongsTo(Klasifikasi::class);
    }
    public function tujuanSatker() {
        return $this->belongsTo(Satker::class, 'tujuan_satker_id');
    }
}