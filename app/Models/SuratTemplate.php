<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratTemplate extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database
     */
    protected $table = 'surat_templates';

    /**
     * Atribut yang dapat diisi secara massal
     */
    protected $fillable = [
        'satker_id',
        'nama_template',
        'config_posisi', // Kolom JSON untuk koordinat X dan Y
    ];

    /**
     * Casting atribut ke tipe data tertentu
     */
    protected $casts = [
        'config_posisi' => 'array', // Otomatis konversi JSON ke Array PHP saat dipanggil
    ];

    /**
     * Relasi ke Satker (Pemilik Template)
     */
    public function satker()
    {
        return $this->belongsTo(Satker::class, 'satker_id');
    }
}