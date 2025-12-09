<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuratKeluar; // Import model yang benar
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratKeluarController extends Controller
{
    /**
     * Menampilkan halaman daftar surat keluar (Tabel).
     */
    public function index()
    {
        // Ambil surat keluar yang dibuat oleh user BAU ini saja
        $suratKeluars = SuratKeluar::where('user_id', Auth::id())
                                ->latest('tanggal_surat')
                                ->get();
                                
        return view('bau.surat_keluar.index', compact('suratKeluars'));
    }

    /**
     * Menampilkan form untuk membuat surat keluar baru.
     */
    public function create()
    {
        return view('bau.surat_keluar.create');
    }

    /**
     * Menyimpan surat keluar baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:255|unique:surat_keluars,nomor_surat',
            'tanggal_surat' => 'required|date',
            'tujuan_surat' => 'required|string|max:255',
            'perihal' => 'required|string',
            'file_surat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        // 2. Upload File
        $filePath = $request->file('file_surat')->store('surat-keluar', 'public');

        // 3. Simpan ke Database
        SuratKeluar::create([
            'nomor_surat' => $validated['nomor_surat'],
            'tanggal_surat' => $validated['tanggal_surat'],
            'tujuan_surat' => $validated['tujuan_surat'],
            'perihal' => $validated['perihal'],
            'file_surat' => $filePath,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('bau.surat-keluar.index')->with('success', 'Surat keluar berhasil diarsipkan.');
    }

    /**
     * Menampilkan form untuk mengedit surat keluar.
     */
    public function edit(SuratKeluar $suratKeluar)
    {
        // Pastikan user hanya bisa mengedit surat yang mereka buat
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403, 'Anda tidak diizinkan mengedit surat ini.');
        }
        return view('bau.surat_keluar.edit', compact('suratKeluar'));
    }

    /**
     * Mengupdate data surat keluar di database.
     */
    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        // Pastikan user hanya bisa mengupdate surat yang mereka buat
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403);
        }

        // 1. Validasi
        $validated = $request->validate([
            'nomor_surat' => [
                'required', 'string', 'max:255',
                Rule::unique('surat_keluars')->ignore($suratKeluar->id),
            ],
            'tanggal_surat' => 'required|date',
            'tujuan_surat' => 'required|string|max:255',
            'perihal' => 'required|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Opsional saat update
        ]);
        
        // 2. Update data
        $suratKeluar->nomor_surat = $validated['nomor_surat'];
        $suratKeluar->tanggal_surat = $validated['tanggal_surat'];
        $suratKeluar->tujuan_surat = $validated['tujuan_surat'];
        $suratKeluar->perihal = $validated['perihal'];

        // 3. Cek jika ada file baru
        if ($request->hasFile('file_surat')) {
            // Hapus file lama
            Storage::disk('public')->delete($suratKeluar->file_surat);
            // Upload file baru
            $filePath = $request->file('file_surat')->store('surat-keluar', 'public');
            $suratKeluar->file_surat = $filePath;
        }
        
        $suratKeluar->save();

        return redirect()->route('bau.surat-keluar.index')->with('success', 'Data surat keluar berhasil diperbarui.');
    }

    /**
     * Menghapus data surat keluar.
     */
    public function destroy(SuratKeluar $suratKeluar)
    {
        // Pastikan user hanya bisa menghapus surat yang mereka buat
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403);
        }

        try {
            Storage::disk('public')->delete($suratKeluar->file_surat);
            $suratKeluar->delete();
            return redirect()->route('bau.surat-keluar.index')->with('success', 'Surat keluar berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('bau.surat-keluar.index')->with('error', 'Gagal menghapus surat: ' . $e->getMessage());
        }
    }
}