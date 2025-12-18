@extends('layouts.app')

@push('styles')
<style>
    /* Style 13px */
    .table { font-size: 13px; }
    .card-body .list-group-item, .card-body p { font-size: 13px; }
    .alert { font-size: 13px; }
    
    /* Style KPI Bawaan */
    .border-start-primary { border-left: .25rem solid #4e73df !important; }
    .border-start-success { border-left: .25rem solid #1cc88a !important; }
    .border-start-info { border-left: .25rem solid #36b9cc !important; }
    .border-start-warning { border-left: .25rem solid #f6c23e !important; }
    .text-xs { font-size: .5rem; }
    .text-gray-800 { color: #3a3b45; }

    /* Style KPI (Font dll) */
    .card-body .h5 { font-size: 1.2rem !important; font-weight: 700 !important; }
    .card-body .h2.text-muted { font-size: 1.5rem !important; }
    .card-header h6 { font-size: 14px !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- BAGIAN 1: KARTU KPI (Tidak Berubah) --}}
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Surat Masuk</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalSurat }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-fill h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Surat Baru (di BAU)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $baruDiBau }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-inbox-fill h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Menunggu Disposisi Rektor</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $diAdminRektor }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-workspace h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Perlu Diteruskan (dari Rektor)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $didisposisi }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-pencil-square h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- BAGIAN 2: CHARTS (Line & Pie) --}}
    <div class="row">
        <!-- Line Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat Masuk (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 200px;">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Komposisi Tipe Surat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-0" style="height: 200px;">
                        <canvas id="komposisiSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 
      ====================================================
      BAGIAN 3: LAYOUT BARU (TABEL & BAR CHART)
      ====================================================
    --}}
    <div class="row">
        {{-- Kolom Kiri: Tabel Daftar Kerja --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Daftar Kerja Terbaru (Perlu Tindakan BAU)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">No. Agenda</th>
                                    <th scope="col">Perihal</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suratTerbaru as $surat)
                                <tr>
                                    <th scope="row">{{ $surat->no_agenda }}</th>
                                    <td>{{ $surat->perihal }}</td>
                                    <td>
                                        @if ($surat->status == 'baru_di_bau')
                                            <span class="badge text-bg-info">Baru di BAU</span>
                                        @elseif ($surat->status == 'didisposisi')
                                            <span class="badge text-bg-warning">Perlu Diteruskan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($surat->status == 'baru_di_bau')
                                            <a href="{{ route('bau.surat.eksternal') }}" class="btn btn-sm btn-primary" title="Teruskan ke Rektor">
                                                <i class="bi bi-send-arrow-up-fill"></i> Teruskan
                                            </a>
                                        @elseif ($surat->status == 'didisposisi')
                                            <a href="{{ route('bau.disposisi.index') }}" class="btn btn-sm btn-success" title="Teruskan ke Satker">
                                                <i class="bi bi-send-check-fill"></i> Teruskan
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada pekerjaan baru.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center bg-light border-0">
<a href="{{ route('bau.surat.eksternal') }}" class="btn btn-outline-primary btn-sm me-2">
    Lihat Daftar Surat Masuk
</a>
                    <a href="{{ route('bau.disposisi.index') }}" class="btn btn-outline-warning btn-sm">
                        Lihat Daftar Disposisi
                    </a>
                </div>
            </div>
        </div>

        {{-- 
          ====================================================
          Kolom Kanan: Chart Distribusi (Menggantikan Aksi Cepat)
          ====================================================
        --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Distribusi Surat per Satker (Top 10)</h6>
                </div>
                <div class="card-body">
                    {{-- 
                      Kita buat tinggi canvas ini sedikit lebih besar 
                      agar tingginya seimbang dengan tabel di sebelahnya.
                    --}}
                    <div class="chart-bar" style="height: 200px;">
                        <canvas id="distribusiSatkerChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
{{-- jQuery (diperlukan untuk Chart.js) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Library Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Ambil data dari Blade (dilempar oleh Controller)
    const pieLabels = @json($pieLabels);
    const pieData = @json($pieData);
    const lineLabels = @json($lineLabels);
    const lineData = @json($lineData);
    const barLabels = @json($barLabels);
    const barData = @json($barData);

    $(document).ready(function() {
        
        // --- Inisialisasi Pie Chart (Komposisi Surat) ---
        const ctxPie = document.getElementById('komposisiSuratChart');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: ['#4e73df', '#1cc88a'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    return label + ': ' + value + ' surat';
                                }
                            }
                        }
                    }
                },
            });
        }

        // --- Inisialisasi Line Chart (Tren Surat) ---
        const ctxLine = document.getElementById('trenSuratChart');
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: lineLabels,
                    datasets: [{
                        label: "Jumlah Surat",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.05)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(78, 115, 223, 1)",
                        data: lineData,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Jumlah Surat: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { ticks: { precision: 0 }, beginAtZero: true }
                    }
                }
            });
        }

        // --- Inisialisasi Bar Chart (Distribusi Satker) ---
        const ctxBar = document.getElementById('distribusiSatkerChart');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Jumlah Surat Diteruskan',
                        data: barData,
                        backgroundColor: 'rgba(28, 200, 138, 0.8)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false } // Sembunyikan legenda
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 } // Hanya angka bulat
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

    });
</script>
@endpush