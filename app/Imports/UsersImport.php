<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Pastikan row email tidak kosong
        if (!isset($row['email']) || $row['email'] == null) {
            return null;
        }

        // Cek apakah user sudah ada (berdasarkan email) untuk menghindari duplikat error
        $user = User::where('email', $row['email'])->first();
        if ($user) {
            return null; // Skip jika email sudah ada
        }

        return new User([
            'name'      => $row['nama'],          // Sesuai header excel 'nama'
            'email'     => $row['email'],         // Sesuai header excel 'email'
            'no_hp'     => $row['no_hp'] ?? null, // Sesuai header excel 'no_hp'
            'password'  => Hash::make($row['password']), 
            'role'      => $row['role'],          // Sesuai header excel 'role' (isi: bau, satker, admin_rektor)
            'satker_id' => $row['satker_id'],     // Sesuai header excel 'satker_id' (Isi angka 2, 3, dst)
        ]);
    }
}