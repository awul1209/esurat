@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    #tabelUser, .dataTables_wrapper { font-size: 13px !important; }
    /* ... (CSS datatables Anda yang lain) ... */
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Manajemen User</h6>
                <a href="{{ route('bau.manajemen-user.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-circle-fill me-2"></i> Tambah User Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelUser" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Email</th>
                            <th scope="col">No. Hp</th>
                            <th scope="col">Role</th>
                            <th scope="col">Satuan Kerja (Satker)</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->no_hp }}</td>
                            <td>
                                <span class="badge 
                                    @if($user->role == 'bau') text-bg-danger
                                    @elseif($user->role == 'admin_rektor') text-bg-primary
                                    @elseif($user->role == 'satker') text-bg-success
                                    @else text-bg-secondary @endif">
                                    {{ Str::ucfirst(str_replace('_', ' ', $user->role)) }}
                                </span>
                            </td>
                            <td>{{ $user->satker->nama_satker ?? 'N/A' }}</td>
<td class="text-center align-middle">
    <div class="d-flex justify-content-center align-items-center gap-2">
        <!-- Tombol Edit -->
        <a href="{{ route('bau.manajemen-user.edit', $user->id) }}" 
           class="btn btn-sm btn-warning" 
           title="Edit">
            <i class="bi bi-pencil-fill"></i>
        </a>

        <!-- Tombol Hapus -->
        <form action="{{ route('bau.manajemen-user.destroy', $user->id) }}" 
              method="POST" 
              onsubmit="return confirm('Anda yakin ingin menghapus user {{ $user->name }}?');">
            @csrf
            @method('DELETE')
            <button type="submit" 
                    class="btn btn-sm btn-danger" 
                    title="Hapus" 
                    {{ $user->id == Auth::id() ? 'disabled' : '' }}>
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
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        new DataTable('#tabelUser', {
            pagingType: 'simple_numbers',
            order: [[ 0, 'asc' ]], // Urutkan berdasarkan Nama
            language: { /* ... (opsi bahasa Anda) ... */ }
        });
    });
</script>
@endpush