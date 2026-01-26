<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SuratKeluar; // <--- PAKAI MODEL INI
use App\Models\RiwayatSurat; // Jika ingin mencatat log aktivitas user

// === IMPORT LIBRARY EXCEL ===
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuratKeluarEksternalController extends Controller
{
   public function index(Request $request)
    {
        $userId = Auth::id();

        // 1. Query Builder
        $query = SuratKeluar::where('user_id', $userId)
                            ->where('tipe_kirim', 'eksternal');

        // 2. Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // 3. Ambil Data
        $suratKeluar = $query->latest('tanggal_surat')->get();

        return view('satker.surat_keluar_eksternal.index', compact('suratKeluar'));
    }

    /**
     * Method Export Excel Eksternal
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $userId    = Auth::id();

        // Anonymous Class Export
        $export = new class($startDate, $endDate, $userId) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
            
            protected $startDate;
            protected $endDate;
            protected $userId;

            public function __construct($startDate, $endDate, $userId)
            {
                $this->startDate = $startDate;
                $this->endDate   = $endDate;
                $this->userId    = $userId;
            }

            public function collection()
            {
                $query = SuratKeluar::where('user_id', $this->userId)
                                    ->where('tipe_kirim', 'eksternal');

                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
                }

                return $query->latest('tanggal_surat')->get();
            }

            public function headings(): array
            {
                return [
                    'No',
                    'Tujuan Surat (Pihak Luar)',
                    'Nomor Surat',
                    'Perihal',
                    'Tanggal Surat',
                    'Link File'
                ];
            }

            public function map($surat): array
            {
                static $no = 0;
                $no++;

                $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

                return [
                    $no,
                    $surat->tujuan_luar, // Kolom khusus eksternal
                    $surat->nomor_surat,
                    $surat->perihal,
                    \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                    $linkFile
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        };

        return Excel::download($export, 'Laporan_Surat_Keluar_Eksternal_' . date('d-m-Y_H-i') . '.xlsx');
    }

    public function create()
    {
        return view('satker.surat_keluar_eksternal.create');
    }

  public function store(Request $request)
{
    // 1. Validasi
    $request->validate([
        // Tambahkan aturan unique untuk tabel surat_keluars kolom nomor_surat
        'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'tujuan_luar'   => 'required|string|max:255',
        'perihal'       => 'required|string|max:255',
        'tanggal_surat' => 'required|date',
        'file_surat'    => 'required|file|mimes:pdf,jpg,png|max:10240',
    ], [
        // Custom Error Message (Opsional)
        'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Mohon gunakan nomor lain.',
    ]);

    $user = Auth::user();
    $path = $request->file('file_surat')->store('surat_keluar_eksternal_satker', 'public');

    // 2. Simpan Data
    SuratKeluar::create([
        'user_id'       => $user->id,
        'nomor_surat'   => $request->nomor_surat,
        'perihal'       => $request->perihal,
        'tanggal_surat' => $request->tanggal_surat,
        'file_surat'    => $path,
        'tipe_kirim'    => 'eksternal',
        'tujuan_luar'   => $request->tujuan_luar
    ]);

    return redirect()->route('satker.surat-keluar.eksternal.index')
                     ->with('success', 'Surat keluar eksternal berhasil dibuat.');
}

    public function edit(SuratKeluar $surat)
    {
        // Pastikan milik user sendiri dan tipe eksternal
        if ($surat->user_id != Auth::id() || $surat->tipe_kirim != 'eksternal') abort(403);
        
        return view('satker.surat_keluar_eksternal.edit', compact('surat'));
    }

    public function update(Request $request, SuratKeluar $surat)
    {
        if ($surat->user_id != Auth::id()) abort(403);

        $request->validate([
            // Unique: Abaikan ID surat ini agar tidak dianggap duplikat dengan dirinya sendiri
            'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat,' . $surat->id,
            'tujuan_luar'   => 'required|string|max:255',
            'perihal'       => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'file_surat'    => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ], [
            'nomor_surat.unique' => 'Nomor surat sudah digunakan.'
        ]);

        $data = $request->only(['nomor_surat', 'tujuan_luar', 'perihal', 'tanggal_surat']);

        // Hapus file lama jika ada upload baru
        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('surat-keluar', 'public');
        }

        $surat->update($data);

        return redirect()->route('satker.surat-keluar.eksternal.index')
                         ->with('success', 'Data surat keluar eksternal berhasil diperbarui.');
    }

public function destroy(SuratKeluar $surat)
{
    // Pastikan hanya pemilik yang bisa menghapus
    if ($surat->user_id != Auth::id()) {
        abort(403, 'Anda tidak memiliki akses untuk menghapus surat ini.');
    }

    // JANGAN hapus file_surat di sini agar bisa di-restore nanti oleh BAU.
    // File hanya akan benar-benar dihapus jika dilakukan 'forceDelete' di TrashController.
    
    // Laravel otomatis merubah status menjadi soft delete karena Trait SoftDeletes sudah dipasang di Model
    $surat->delete(); 

    return redirect()->back()->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');
}
}