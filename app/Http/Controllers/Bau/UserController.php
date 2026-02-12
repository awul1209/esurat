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
    $daftarSatker = \App\Models\Satker::orderBy('nama_satker')->get();
    // Tambahkan pengambilan data jabatan
    $daftarJabatan = \App\Models\Jabatan::orderBy('nama_jabatan')->get();
    
    return view('bau.manajemen-user.create', compact('daftarSatker', 'daftarJabatan'));
}

    /**
     * Menyimpan user baru ke database.
     */
 public function store(Request $request)
{
    // 1. Validasi Input
    $validated = $request->validate([
        'name'       => 'required|string|max:255',
        'email'      => 'required|string|email|max:255|unique:users,email',
        'email2'     => 'nullable|string|email|max:255|unique:users,email2',
        'no_hp'      => 'nullable|string|max:20',
        'password'   => 'required|string|min:6|confirmed', // Min 6 sesuai permintaan sebelumnya
        'role'       => 'required|in:admin_rektor,bau,admin_satker,pimpinan,pegawai',
        'satker_id'  => 'nullable|exists:satkers,id',
        'jabatan_id' => 'nullable|exists:jabatans,id', // <--- TAMBAHAN JABATAN
    ]);

    // 2. Proses Simpan dengan Transaction (Opsional tapi disarankan)
    try {
        \DB::transaction(function () use ($validated) {
            User::create([
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'email2'     => $validated['email2'],
                'no_hp'      => $validated['no_hp'],
                'password'   => Hash::make($validated['password']),
                'role'       => $validated['role'],
                'satker_id'  => $validated['satker_id'],
                'jabatan_id' => $validated['jabatan_id'], // <--- SIMPAN JABATAN
            ]);
        });

        return redirect()->route('bau.manajemen-user.index')
                         ->with('success', 'User ' . $validated['name'] . ' berhasil dibuat.');

    } catch (\Exception $e) {
        return redirect()->back()
                         ->withInput()
                         ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

    /**
     * Menampilkan form untuk mengedit user.
     */
 public function edit(User $manajemen_user)
{
    // Binding otomatis dari rute (User $manajemen_user)
    $user = $manajemen_user;
    $daftarSatker = \App\Models\Satker::orderBy('nama_satker')->get();
    $daftarJabatan = \App\Models\Jabatan::orderBy('nama_jabatan')->get(); // Tambahkan ini
    
    return view('bau.manajemen-user.edit', compact('user', 'daftarSatker', 'daftarJabatan'));
}

public function update(Request $request, User $manajemen_user)
{
    $user = $manajemen_user;

    // 1. Validasi
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => [
            'required', 'string', 'email', 'max:255',
            \Illuminate\Validation\Rule::unique('users')->ignore($user->id),
        ],
        'email2' => [
            'nullable', 'string', 'email', 'max:255',
            \Illuminate\Validation\Rule::unique('users', 'email2')->ignore($user->id),
        ],
        'no_hp' => 'nullable|string|max:255', 
        'password' => 'nullable|string|min:6|confirmed', // Nullable: boleh kosong jika tidak ganti
        'role' => 'required|in:admin_rektor,bau,admin_satker,pimpinan,pegawai', // Sesuai tabel users
        'satker_id' => 'nullable|exists:satkers,id',
        'jabatan_id' => 'nullable|exists:jabatans,id', // Tambahan Jabatan
    ]);

    // 2. Update data dasar menggunakan fill (lebih ringkas)
    $user->fill([
        'name'       => $validated['name'],
        'email'      => $validated['email'],
        'email2'     => $validated['email2'],
        'no_hp'      => $validated['no_hp'],
        'role'       => $validated['role'],
        'satker_id'  => $validated['satker_id'],
        'jabatan_id' => $validated['jabatan_id'], // Update Jabatan
    ]);

    // 3. Logika Password Nullable
    // filled() akan mengembalikan true jika input ada dan tidak kosong
    if ($request->filled('password')) {
        $user->password = \Hash::make($request->password);
    }

    $user->save();

    return redirect()->route('bau.manajemen-user.index')
                     ->with('success', 'Data user ' . $user->name . ' berhasil diperbarui.');
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
