<?php

namespace App\Http\Controllers\Bau;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Satker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Menampilkan halaman daftar semua user.
     */
    public function index()
    {
        $users = User::with('satker')->latest()->get();
        return view('bau.manajemen-user.index', compact('users'));
    }

    /**
     * Menampilkan form untuk membuat user baru.
     */
    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker')->get();
        return view('bau.manajemen-user.create', compact('daftarSatker'));
    }

    /**
     * Menyimpan user baru ke database.
     */
  public function store(Request $request)
{
    // 1. Validasi
    $validated = $request->validate([
        'name'      => 'required|string|max:255',
        'email'     => 'required|string|email|max:255|unique:users,email',
        'email2'    => 'nullable|string|email|max:255|unique:users,email2', // <--- TAMBAHAN EMAIL 2
        'no_hp'     => 'nullable|string|max:20',
        'password'  => 'required|string|min:8|confirmed',
        'role'      => 'required|in:bau,admin_rektor,satker,pegawai',
        'satker_id' => 'nullable|exists:satkers,id',
    ]);

    // 2. Buat User
    User::create([
        'name'      => $validated['name'],
        'email'     => $validated['email'],
        'email2'    => $validated['email2'], // <--- SIMPAN EMAIL 2
        'no_hp'     => $validated['no_hp'],
        'password'  => Hash::make($validated['password']),
        'role'      => $validated['role'],
        'satker_id' => $validated['satker_id'],
    ]);

    return redirect()->route('bau.manajemen-user.index')
                     ->with('success', 'User baru berhasil dibuat.');
}

    /**
     * Menampilkan form untuk mengedit user.
     */
    public function edit(User $manajemen_user) // Nama variabel 'manajemen_user' harus cocok dengan rute
    {
        $user = $manajemen_user; // Ganti nama agar lebih mudah dibaca
        $daftarSatker = Satker::orderBy('nama_satker')->get();
        return view('bau.manajemen-user.edit', compact('user', 'daftarSatker'));
    }

    /**
     * Mengupdate data user di database.
     */
 public function update(Request $request, User $manajemen_user)
{
    $user = $manajemen_user;

    // 1. Validasi
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => [
            'required', 'string', 'email', 'max:255',
            Rule::unique('users')->ignore($user->id),
        ],
        // TAMBAHAN: Validasi Email 2 (Opsional tapi harus unik kecuali milik user ini sendiri)
        'email2' => [
            'nullable', 'string', 'email', 'max:255',
            Rule::unique('users', 'email2')->ignore($user->id),
        ],
        // No HP tetap ada dengan max 255 sesuai keinginan Anda
        'no_hp' => 'nullable|string|max:255', 
        'password' => 'nullable|string|min:8|confirmed',
        'role' => 'required|in:bau,admin_rektor,satker,pegawai',
        'satker_id' => 'nullable|exists:satkers,id',
    ]);

    // 2. Update data dasar
    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->email2 = $validated['email2']; // <--- SIMPAN PERUBAHAN EMAIL 2
    $user->no_hp = $validated['no_hp']; 
    $user->role = $validated['role'];
    $user->satker_id = $validated['satker_id'];

    // 3. Cek jika password diisi (Hanya update jika admin mengetik password baru)
    if ($request->filled('password')) {
        $user->password = Hash::make($validated['password']);
    }

    $user->save();

    return redirect()->route('bau.manajemen-user.index')->with('success', 'Data user berhasil diperbarui.');
}

    /**
     * Menghapus user dari database.
     */
    public function destroy(User $manajemen_user)
    {
        $user = $manajemen_user; // Ganti nama

        // Keamanan: Jangan biarkan user menghapus dirinya sendiri
        if ($user->id == Auth::id()) {
            return redirect()->route('bau.manajemen-user.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('bau.manajemen-user.index')->with('success', 'User "' . $userName . '" berhasil dihapus.');
    }

    /**
     * Import user dari file Excel.
     */
// --- TAMBAHKAN METHOD IMPORT INI DI SINI ---
    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file_excel'));
            
            return redirect()->route('bau.manajemen-user.index')
                             ->with('success', 'Data User berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->route('bau.manajemen-user.index')
                             ->with('error', 'Gagal import data: ' . $e->getMessage());
        }
    }
}
