<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    public function suratEdaran()
    {
        return $this->belongsToMany(Surat::class, 'surat_edaran_satker')
                    ->withPivot('status')
                    ->withTimestamps()
                    ->orderBy('pivot_created_at', 'desc');
    }
}
