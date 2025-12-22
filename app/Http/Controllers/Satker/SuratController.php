<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\User;
use App\Models\RiwayatSurat;
use App\Models\Satker; 
use App\Models\Disposisi; // Pastikan import model Disposisi
use Illuminate\Support\Facades\DB; 

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuratController extends Controller
{
   public function indexMasukEksternal()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;

        $daftarPegawai = User::where('satker_id', $satkerId)
                            ->where('role', 'pegawai')
                            ->orderBy('name', 'asc')
                            ->get();
        
        $suratMasukSatker = Surat::query()
            ->where(function($query) use ($satkerId) {
                // 1. Jalur Disposisi
                $query->whereHas('disposisis', function ($q) use ($satkerId) {
                    $q->where('tujuan_satker_id', $satkerId);
                })
                // 2. Jalur Langsung
                ->orWhere('tujuan_satker_id', $satkerId);
            })
            
            // --- PERBAIKAN DISINI ---
            // Filter agar HANYA mengambil yang BUKAN internal.
            // Kita gunakan whereNull juga untuk jaga-jaga jika ada data lama yang tipenya kosong (biasanya eksternal).
            ->where(function($q) {
                $q->where('tipe_surat', '!=', 'internal')
                  ->orWhereNull('tipe_surat');
            })
            // ------------------------

            // Filter status global agar yang belum dikirim BAU tidak muncul
            ->whereIn('status', ['di_satker', 'selesai', 'arsip_satker', 'didisposisi'])
            ->with(['disposisis.tujuanSatker', 'tujuanUser', 'tujuanSatker', 'delegasiPegawai']) 
            ->latest('diterima_tanggal')
            ->get();

        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()->with('riwayats.user')->get();
        
        return view('satker.surat-masuk-eksternal', compact(
            'suratMasukSatker',
            'suratEdaran',
            'daftarPegawai'
        ));
    }

 public function exportMasukEksternal(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $user      = Auth::user();
        $satkerId  = $user->satker_id;

        $export = new class($startDate, $endDate, $user, $satkerId) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
            
            protected $startDate;
            protected $endDate;
            protected $user;
            protected $satkerId;

            public function __construct($startDate, $endDate, $user, $satkerId)
            {
                $this->startDate = $startDate;
                $this->endDate   = $endDate;
                $this->user      = $user;
                $this->satkerId  = $satkerId;
            }

           public function collection()
            {
                // ==========================================
                // BAGIAN 1: SURAT MASUK (LOGIKA DARI INDEX)
                // ==========================================
                $query1 = \App\Models\Surat::query();

                // 1. Jalur (Sama persis dengan Index)
                $query1->where(function($masterQ) {
                    // Jalur Disposisi
                    $masterQ->whereHas('disposisis', function ($q) {
                        $q->where('tujuan_satker_id', $this->satkerId);
                    })
                    // Jalur Langsung
                    ->orWhere('tujuan_satker_id', $this->satkerId);
                });

                // 2. Filter Status (Sama persis dengan Index)
                // Hapus 'disimpan' jika di index tidak ada, agar konsisten
                $query1->whereIn('status', ['di_satker', 'selesai', 'arsip_satker', 'didisposisi']);

                // 3. Filter Tipe (PENGAMAN PENTING)
                // Agar Surat Internal Manual tidak ikut, TAPI Surat Eksternal (yang mungkin null) TETAP IKUT
                $query1->where(function($q) {
                    $q->where('tipe_surat', '!=', 'internal') // Buang yang jelas-jelas internal
                      ->orWhereNull('tipe_surat');            // TAPI ambil yang kosong (biasanya eksternal lama)
                });

                // 4. Filter Tanggal
                if ($this->startDate && $this->endDate) {
                    $query1->whereBetween('diterima_tanggal', [$this->startDate, $this->endDate]);
                }
                
                $suratMasuk = $query1->get();


                // ==========================================
                // BAGIAN 2: SURAT EDARAN (LOGIKA DARI INDEX)
                // ==========================================
                $query2 = \App\Models\Surat::select('surats.*')
                        ->join('surat_edaran_satker', 'surats.id', '=', 'surat_edaran_satker.surat_id')
                        ->where('surat_edaran_satker.satker_id', $this->satkerId);
                
                if ($this->startDate && $this->endDate) {
                    $query2->whereBetween('surats.diterima_tanggal', [$this->startDate, $this->endDate]);
                }
                $suratEdaran = $query2->get();


                // ==========================================
                // BAGIAN 3: PENGGABUNGAN (SAMA SEPERTI VIEW)
                // ==========================================
                return $suratMasuk->merge($suratEdaran)
                                  ->unique('id')
                                  ->sortByDesc('diterima_tanggal');
            }

            public function headings(): array
            {
                return [
                    'No', 'No Surat', 'Tanggal Surat', 'Diterima Tanggal', 'Perihal', 'Pengirim', 'Sifat', 'Link Surat'
                ];
            }

            public function map($surat): array
            {
                static $no = 0;
                $no++;

                $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

                return [
                    $no,
                    $surat->nomor_surat,
                    \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                    \Carbon\Carbon::parse($surat->diterima_tanggal)->format('d-m-Y'),
                    $surat->perihal,
                    $surat->surat_dari,
                    $surat->sifat ?? 'Biasa',
                    $linkFile
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [ 1 => ['font' => ['bold' => true]] ];
            }
        };

        return Excel::download($export, 'Laporan_Surat_Masuk_Eksternal_' . date('d-m-Y_H-i') . '.xlsx');
    }

    // ... method index dll yang sudah ada ...

    // Method untuk menyimpan surat eksternal inputan Satker sendiri
   public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'nomor_surat'      => 'required|string|max:255',
            'surat_dari'       => 'required|string|max:255',
            'perihal'          => 'required|string',
            'tanggal_surat'    => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            // Validasi Array Delegasi (Opsional)
            'delegasi_user_ids'=> 'nullable|array',
            'catatan_delegasi' => 'nullable|string',
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-masuk-satker', 'public');

        // Gunakan Transaksi DB
        DB::transaction(function() use ($request, $user, $path) {
            
            // TENTUKAN STATUS AWAL
            // Kita set 'arsip_satker' agar di tabel dianggap "Sudah Diproses" (Processed)
            // Logika View akan otomatis mengubah badge menjadi "Delegasi" jika ada pegawainya,
            // atau "Selesai (Diarsipkan)" jika tidak ada pegawainya.
            $statusAwal = 'arsip_satker'; 

            // A. Simpan Surat
            $surat = \App\Models\Surat::create([
                'user_id'          => $user->id,
                'tipe_surat'       => 'eksternal',
                'nomor_surat'      => $request->nomor_surat,
                'surat_dari'       => $request->surat_dari,
                'perihal'          => $request->perihal,
                'tanggal_surat'    => $request->tanggal_surat,
                'diterima_tanggal' => $request->diterima_tanggal,
                'file_surat'       => $path,
                'sifat'            => 'Asli',
                'no_agenda'        => 'ME-' . time(),
                'tujuan_tipe'      => 'satker',
                'tujuan_satker_id' => $user->satker_id,
                'status'           => $statusAwal, // <--- KUNCI PERBAIKANNYA DISINI
            ]);

            // B. Proses Delegasi (Jika ada inputan pegawai)
            if ($request->has('delegasi_user_ids') && count($request->delegasi_user_ids) > 0) {
                
                $catatan = $request->catatan_delegasi;
                $userIds = $request->delegasi_user_ids;

                // Attach ke tabel pivot
                $surat->delegasiPegawai()->attach($userIds, [
                    'catatan'    => $catatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Simpan Riwayat
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input & Delegasi',
                    'catatan'     => 'Surat diinput manual dan langsung didelegasikan ke ' . count($userIds) . ' pegawai.'
                ]);

                // Notif WA (Opsional)
                try {
                    $pegawais = \App\Models\User::whereIn('id', $userIds)->get();
                    foreach ($pegawais as $p) {
                        if ($p->no_hp) {
                            $pesan = "📩 *Tugas Baru (Delegasi)*\n\nPerihal: {$surat->perihal}\nSilakan cek sistem.";
                            \App\Services\WaService::send($p->no_hp, $pesan);
                        }
                    }
                } catch (\Exception $e) {}

            } else {
                // Jika tidak ada delegasi, catat sebagai Arsip Langsung
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input Manual (Arsip)',
                    'catatan'     => 'Surat diinput manual dan langsung diarsipkan (Selesai).'
                ]);
            }
        });

        return redirect()->back()->with('success', 'Surat berhasil disimpan.');
    }

    // Update Surat (Hanya jika inputan sendiri)
    public function update(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);

        // Cek Hak Akses: Harus inputan user satker ini & status masih di_satker
        if ($surat->user_id != Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak berhak mengedit surat disposisi/inputan orang lain.');
        }

        $request->validate([
            'nomor_surat' => 'required|string',
            'surat_dari'  => 'required|string',
            'perihal'     => 'required|string',
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat'  => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $data = $request->except(['file_surat', '_token', '_method']);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
            $data['file_surat'] = $request->file('file_surat')->store('surat-masuk-satker', 'public');
        }

        $surat->update($data);
        return redirect()->back()->with('success', 'Data surat diperbarui.');
    }

    // Hapus Surat (Hanya jika inputan sendiri)
    public function destroy($id)
    {
        $surat = Surat::findOrFail($id);

        if ($surat->user_id != Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak berhak menghapus surat ini.');
        }

        if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
        $surat->delete();

        return redirect()->back()->with('success', 'Surat berhasil dihapus.');
    }

    /**
     * Helper Private untuk menandai selesai secara aman
     * (Mencegah perubahan status global jika surat disposisi)
     */
   private function updateStatusLokal($surat, $satkerId)
    {
        // 1. Cari Disposisi yang spesifik untuk Satker ini
        $disposisi = Disposisi::where('surat_id', $surat->id)
                              ->where('tujuan_satker_id', $satkerId)
                              ->first();

        if ($disposisi) {
            // FORCE UPDATE status penerimaan
            $disposisi->status_penerimaan = 'selesai';
            $disposisi->save(); 
        } else {
            // Jika tidak ketemu di tabel disposisi (berarti surat langsung), 
            // update status global surat
            $surat->status = 'arsip_satker';
            $surat->save();
        }
    }

    public function delegasiKePegawai(Request $request, Surat $surat)
    {
        // 1. Validasi
        $validated = $request->validate([
            'tujuan_user_ids' => 'required|array|min:1', 
            'tujuan_user_ids.*' => 'exists:users,id',
            'catatan_satker' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $delegatedNames = [];

        // Gunakan Transaction agar jika satu gagal, semua batal
        DB::transaction(function() use ($request, $surat, $user, $validated, &$delegatedNames) {
            
            $pivotData = [];
            foreach ($validated['tujuan_user_ids'] as $userId) {
                $pegawai = User::find($userId);

                // Pastikan hanya mendelegasikan ke pegawai di satker sendiri
                if ($pegawai && $pegawai->satker_id == $user->satker_id) {
                    $pivotData[$pegawai->id] = [
                        'status' => 'belum_dibaca',
                        'catatan' => $validated['catatan_satker'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $delegatedNames[] = $pegawai->name;
                }
            }

            // 2. Simpan ke Pivot (surat_delegasi)
            if (!empty($pivotData)) {
                // Gunakan syncWithoutDetaching agar tidak menghapus delegasi sebelumnya jika ada tambahan
                $surat->delegasiPegawai()->syncWithoutDetaching($pivotData);
            }

            // 3. UPDATE STATUS (Penyelesaian Masalah 1)
            // Kita panggil helper update status
            $this->updateStatusLokal($surat, $user->satker_id);

            // 4. Catat Riwayat
            if (count($delegatedNames) > 0) {
                $namesString = implode(', ', $delegatedNames);
                RiwayatSurat::create([
                    'surat_id' => $surat->id,
                    'user_id' => $user->id,
                    'status_aksi' => 'Didelegasikan ke Pegawai', 
                    'catatan' => 'Didelegasikan oleh ' . $user->name . ' ke: ' . $namesString . '. Instruksi: "' . ($validated['catatan_satker'] ?? '-') . '"'
                ]);
            }
        });

        if (count($delegatedNames) > 0) {
            return redirect()->route('satker.surat-masuk.eksternal')
                             ->with('success', 'Surat berhasil didelegasikan dan status diperbarui.');
        } else {
            return redirect()->back()->with('error', 'Gagal mendelegasikan. Pastikan pegawai valid.');
        }
    }

    public function arsipkan(Request $request, Surat $surat)
    {
        $user = Auth::user();
        
        // Panggil helper update status
        $this->updateStatusLokal($surat, $user->satker_id);
        
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diarsipkan/Selesai di Satker',
            'catatan' => 'Surat ditandai selesai oleh ' . $user->name . ' (Tidak didelegasikan).'
        ]);
        
        return redirect()->back()->with('success', 'Surat berhasil diarsipkan (Tandai Selesai).');
    }

   

    public function broadcastInternal(Request $request, Surat $surat)
    {
        $user = Auth::user();
        DB::table('surat_edaran_satker')
            ->where('surat_id', $surat->id)
            ->where('satker_id', $user->satker_id)
            ->update(['status' => 'diteruskan_internal']);

        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diteruskan ke Internal Satker',
            'catatan' => 'Surat Edaran disebarkan ke semua pegawai di ' . $user->satker->nama_satker . ' oleh ' . $user->name
        ]);

        return redirect()->route('satker.surat-masuk.eksternal')->with('success', 'Surat Edaran berhasil disebarkan ke semua pegawai internal Anda.');
    }
}