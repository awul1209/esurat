<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Hanya bisa diakses oleh user yang sudah login.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Tampilkan halaman form edit profil.
     */
    public function edit()
    {
        // Ambil user yang sedang login
        $user = Auth::user();
        return view('profil.edit', compact('user'));
    }

    /**
     * Simpan perubahan dari form edit profil.
     */
  public function update(Request $request)
{
    // Ambil user yang sedang login
    $user = Auth::user();

    // 1. Validasi data
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => [
            'required',
            'string',
            'email',
            'max:255',
            // Pastikan email unik, KECUALI untuk user ini sendiri
            Rule::unique('users')->ignore($user->id),
        ],
        // TAMBAHAN: Validasi Email 2 (Opsional & Unik)
        'email2' => [
            'nullable',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email2')->ignore($user->id),
        ],
        // TAMBAHAN: Validasi No HP (Nullable)
        'no_hp' => 'nullable|string|max:255',
        
        // Password boleh kosong, jika diisi minimal 8 karakter dan confirmed
        'password' => 'nullable|string|min:8|confirmed',
    ]);

    // 2. Update data dasar
    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->email2 = $validated['email2']; // Simpan Email 2
    $user->no_hp = $validated['no_hp'];   // Simpan No HP

    // 3. Cek apakah user mengisi password baru
    if ($request->filled('password')) {
        $user->password = Hash::make($validated['password']);
    }

    // 4. Simpan perubahan
    $user->save();

    // 5. Kembali ke halaman yang sama dengan pesan sukses
    return redirect()->route('profil.edit')->with('success', 'Profil Anda berhasil diperbarui.');
}
}