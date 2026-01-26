@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* Scoping DataTables agar tetap rapi */
    #tabelUser_wrapper { padding: 0.5rem 0; }
    #tabelUser, .dataTables_wrapper { font-size: 13px !important; }
    
    /* Style untuk Badge Role agar lebih elegan */
    .badge-role {
        padding: 0.4em 0.8em;
        font-weight: 500;
        letter-spacing: 0.3px;
        border-radius: 6px;
    }
    
    /* Hover effect pada baris tabel */
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
        transition: background-color 0.2s ease;
    }

    .card-header { border-bottom: 1px solid #f0f0f0 !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="font-size: 13px; border-left: 4px solid #198754 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i> 
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Card Tabel --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-people-fill me-2"></i> Manajemen User
                    </h6>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Import Excel
                        </button>

                        <a href="{{ route('bau.manajemen-user.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="bi bi-plus-circle-fill me-1"></i> Tambah User Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelUser" class="table table-hover align-middle">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email Utama</th>
                            <th>Email Cadangan</th>
                            <th class="text-center">Role</th>
                            <th>Unit Kerja (Satker)</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $index => $user)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-muted small">{{ $user->email2 ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge badge-role 
                                    @if($user->role == 'bau') text-bg-danger
                                    @elseif($user->role == 'admin_rektor') text-bg-primary
                                    @elseif($user->role == 'satker') text-bg-success
                                    @else text-bg-secondary @endif shadow-sm">
                                    {{ Str::ucfirst(str_replace('_', ' ', $user->role)) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-building me-2 text-primary small"></i>
                                    {{ $user->satker->nama_satker ?? 'Non-Satker' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('bau.manajemen-user.edit', $user->id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('bau.manajemen-user.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user {{ $user->name }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" {{ $user->id == Auth::id() ? 'disabled' : '' }}>
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
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

{{-- MODAL IMPORT EXCEL --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import dari Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('bau.manajemen-user.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih File Excel (.xlsx / .csv)</label>
                        <input class="form-control" type="file" name="file_excel" required>
                    </div>
                    <div class="bg-light p-3 rounded-3 border">
                        <small class="text-muted d-block fw-bold mb-1">Format Kolom Header (Baris 1):</small>
                        <code class="small text-danger">nama, email, email2, password, role, satker_id</code>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-4 rounded-pill">Upload & Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#tabelUser').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json', // Opsional: Bahasa Indonesia
            },
            pagingType: 'simple_numbers',
            order: [[ 0, 'asc' ]],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 6 } // Matikan sorting di kolom Aksi
            ]
        });
    });
</script>
@endpush