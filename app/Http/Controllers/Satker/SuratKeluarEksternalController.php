<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SuratKeluar; // <--- PAKAI MODEL INI
use App\Models\RiwayatSurat; // Jika ingin mencatat log aktivitas user

class SuratKeluarEksternalController extends Controller
{
    public function index()
    {
        // Ambil data dari tabel surat_keluars yang tipe_kirim-nya 'eksternal'
        $suratKeluar = SuratKeluar::where('user_id', Auth::id())
                            ->where('tipe_kirim', 'eksternal') 
                            ->latest('tanggal_surat')
                            ->get();

        return view('satker.surat_keluar_eksternal.index', compact('suratKeluar'));
    }

    public function create()
    {
        return view('satker.surat_keluar_eksternal.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_surat'      => 'required|string|max:255',
            'tujuan_luar'      => 'required|string|max:255', // Input Manual
            'perihal'          => 'required|string',
            'tanggal_surat'    => 'required|date',
            'file_surat'       => 'required|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-keluar', 'public');

        // Simpan ke tabel surat_keluars
        $surat = SuratKeluar::create([
            'user_id'       => $user->id,
            'nomor_surat'   => $request->nomor_surat,
            'perihal'       => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'file_surat'    => $path,
            'tipe_kirim'    => 'eksternal',          // Penanda Eksternal
            'tujuan_luar'   => $request->tujuan_luar // Simpan Nama Tujuan Manual
        ]);

        return redirect()->route('satker.surat-keluar.eksternal.index')->with('success', 'Surat keluar eksternal berhasil dibuat.');
    }

    public function edit(SuratKeluar $surat) // Model Binding SuratKeluar
    {
        // Pastikan milik user sendiri dan tipe eksternal
        if ($surat->user_id != Auth::id() || $surat->tipe_kirim != 'eksternal') abort(403);
        
        return view('satker.surat_keluar_eksternal.edit', compact('surat'));
    }

    public function update(Request $request, SuratKeluar $surat)
    {
        if ($surat->user_id != Auth::id()) abort(403);

        $request->validate([
            'nomor_surat'   => 'required|string',
            'tujuan_luar'   => 'required|string',
            'perihal'       => 'required|string',
            'tanggal_surat' => 'required|date',
            'file_surat'    => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $data = $request->except(['file_surat', '_token', '_method']);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
            $data['file_surat'] = $request->file('file_surat')->store('surat-keluar', 'public');
        }

        $surat->update($data);
        return redirect()->route('satker.surat-keluar.eksternal.index')->with('success', 'Data diperbarui.');
    }

    public function destroy(SuratKeluar $surat)
    {
        if ($surat->user_id != Auth::id()) abort(403);
        if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
        $surat->delete();
        return redirect()->back()->with('success', 'Surat dihapus.');
    }
}