<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArsipController extends Controller
{
    /**
     * Menampilkan Halaman Arsip Rektor
     * Data: Surat Masuk Internal/Eksternal tujuan Rektor/Univ
     * Status: Selesai, Disimpan, Diarsipkan (Tidak Didisposisi)
     * Urutan: Terbaru diatas (berdasarkan tanggal diterima)
     */
    public function index(Request $request)
    {
        // 1. Query Dasar
        $query = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->whereIn('status', ['arsip rektor', 'disimpan']);

        // 2. Filter Tanggal Surat
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // 3. Filter Tipe Surat (Internal / Eksternal)
        if ($request->filled('tipe_surat') && $request->tipe_surat != 'semua') {
            $query->where('tipe_surat', $request->tipe_surat);
        }

        // 4. Pencarian Manual (No Surat / Perihal / Pengirim / No Agenda)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('perihal', 'like', "%{$search}%")
                  ->orWhere('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('no_agenda', 'like', "%{$search}%")
                  ->orWhere('surat_dari', 'like', "%{$search}%");
            });
        }

        // 5. Urutkan: Terbaru diatas (diterima_tanggal descending)
        // Jika ingin berdasarkan waktu input sistem, ganti jadi latest('created_at')
        $arsipSurat = $query->latest('diterima_tanggal')->get();

        return view('admin_rektor.arsip.index', compact('arsipSurat'));
    }

    /**
     * Export Excel Arsip
     */
    public function export(Request $request)
    {
        // Tangkap Input Filter dari Request
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $search    = $request->search;
        $tipeSurat = $request->tipe_surat;

        // Gunakan Anonymous Class untuk Export
        $export = new class($startDate, $endDate, $search, $tipeSurat) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
            
            protected $startDate, $endDate, $search, $tipeSurat;

            public function __construct($startDate, $endDate, $search, $tipeSurat) {
                $this->startDate = $startDate;
                $this->endDate   = $endDate;
                $this->search    = $search;
                $this->tipeSurat = $tipeSurat;
            }

            public function collection() {
                // Copy Query Dasar dari Index agar hasil sama
                $query = Surat::query()
                    ->whereIn('tujuan_tipe', ['rektor', 'universitas'])
                    ->whereIn('status', ['arsip rektor', 'disimpan']);

                // Filter Tanggal
                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
                }

                // Filter Tipe Surat
                if ($this->tipeSurat && $this->tipeSurat != 'semua') {
                    $query->where('tipe_surat', $this->tipeSurat);
                }
                
                // Filter Search
                if ($this->search) {
                    $s = $this->search;
                    $query->where(function($q) use ($s) {
                        $q->where('perihal', 'like', "%{$s}%")
                          ->orWhere('nomor_surat', 'like', "%{$s}%")
                          ->orWhere('no_agenda', 'like', "%{$s}%")
                          ->orWhere('surat_dari', 'like', "%{$s}%");
                    });
                }

                // Urutkan Terbaru diatas
                return $query->latest('diterima_tanggal')->get();
            }

            public function headings(): array {
                return [
                    'No', 
                    'No Agenda', 
                    'No Surat', 
                    'Tipe',       // Internal/Eksternal
                    'Tanggal Surat', 
                    'Pengirim', 
                    'Perihal', 
                    'Status', 
                    'Link File'
                ];
            }

            public function map($surat): array {
                static $no = 0; $no++;
                
                $link = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';
                $tipe = ucfirst($surat->tipe_surat); // Internal / Eksternal

                return [
                    $no,
                    $surat->no_agenda ?? '-',
                    $surat->nomor_surat,
                    $tipe,
                    Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                    $surat->surat_dari,
                    $surat->perihal,
                    'Arsip Rektor (Selesai)',
                    $link
                ];
            }

            public function styles(Worksheet $sheet) {
                return [ 1 => ['font' => ['bold' => true]] ];
            }
        };

        return Excel::download($export, 'Arsip_Rektor_' . date('d-m-Y_H-i') . '.xlsx');
    }

    public function showRiwayatDetail(Surat $surat)
    {
        // Load relasi riwayats (urutkan terbaru) dan user pelakunya
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }
}