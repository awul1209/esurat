@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white border-bottom-0">
            <ul class="nav nav-pills card-header-pills" id="trashTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="masuk-tab" data-bs-toggle="tab" data-bs-target="#tab-masuk" type="button" role="tab">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Surat Masuk ({{ $suratMasukTrash->count() }})
                    </button>
                </li>
                <li class="nav-item ms-2">
                    <button class="nav-link fw-bold" id="keluar-tab" data-bs-toggle="tab" data-bs-target="#tab-keluar" type="button" role="tab">
                        <i class="bi bi-box-arrow-up me-1"></i> Surat Keluar ({{ $suratKeluarTrash->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="trashTabContent">
                
                {{-- TAB SURAT MASUK --}}
                <div class="tab-pane fade show active" id="tab-masuk" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-trash w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Perihal</th>
                                    <th>Dari/Pengirim</th>
                                    <th>Tgl Hapus</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suratMasukTrash as $sm)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $sm->nomor_surat }}</td>
                                    <td>{{ $sm->perihal }}</td>
                                    <td>{{ $sm->surat_dari }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($sm->deleted_at)->isoFormat('D MMM Y, HH:mm') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <form action="{{ route('bau.trash.restore.masuk', $sm->id) }}" method="POST" class="d-inline shadow-sm">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success rounded-start" title="Pulihkan">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            </form>
                                           {{-- Tombol Hapus Permanen --}}

                                <button type="button" class="ms-1 btn btn-danger btn-sm d-flex align-items-center justify-content-center shadow-sm" 
                                        onclick="confirmDelete('{{ $sm->id }}', 'masuk')" 
                                        style="height: 32px; width: 32px; border-radius: 6px;" title="Hapus Permanen">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB SURAT KELUAR --}}
                <div class="tab-pane fade" id="tab-keluar" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-trash w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Perihal</th>
                                    <th>Tujuan</th>
                                    <th>Tgl Hapus</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suratKeluarTrash as $sk)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $sk->nomor_surat }}</td>
                                    <td>{{ $sk->perihal }}</td>
                                  <td>
                                    @if($sk->tipe_kirim == 'internal')
                                        {{-- Jika internal, mungkin tujuannya ada di relasi atau kolom lain --}}
                                        <span class="badge bg-info">Internal</span>
                                    @else
                                        {{ $sk->tujuan_luar }}
                                    @endif
                                </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($sk->deleted_at)->isoFormat('D MMM Y, HH:mm') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <form action="{{ route('bau.trash.restore.keluar', $sk->id) }}" method="POST" class="d-inline shadow-sm">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success rounded-start" title="Pulihkan">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            </form>

                                    {{-- Tombol Hapus Permanen --}}
                                        <button type="button" class="ms-1 btn btn-danger btn-sm d-flex align-items-center justify-content-center shadow-sm" 
                                                onclick="confirmDelete('{{ $sk->id }}', 'masuk')" 
                                                style="height: 32px; width: 32px; border-radius: 6px;" title="Hapus Permanen">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Hidden Form untuk Force Delete --}}
<form id="force-delete-form" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection
{{-- Import Langsung Library agar tidak error $ is not defined --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Pastikan script berjalan setelah library di atas dimuat
    $(document).ready(function() {
        // Inisialisasi DataTables
        if ($.fn.DataTable) {
            $('.datatable-trash').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                },
                pageLength: 10,
                order: [[3, 'desc']], // Urutkan tgl hapus terbaru
                responsive: true
            });
        }

        // Perbaikan tampilan tabel saat pindah TAB
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });
    });

    /**
     * Fungsi Konfirmasi Hapus Permanen
     */
    function confirmDelete(id, type) {
        Swal.fire({
            title: 'Hapus Permanen?',
            text: "Data yang dihapus secara permanen tidak dapat dikembalikan lagi!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Ya, Hapus Permanen!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.getElementById('force-delete-form');
                // Mengarahkan ke rute force delete
                form.action = "/bau/trash/force-delete/" + id + "/" + type;
                form.submit();
            }
        });
    }
</script>