@extends('layouts.app')

{{-- 
  ====================================================
  TAMBAHAN 1: CSS & Style Kustom
  ====================================================
--}}
@push('styles')
{{-- Kita tidak pakai DataTables di sini, jadi CSS-nya dihapus --}}
{{-- <link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet"> --}}

<style>
    /* 1. Set font tabel HANYA untuk tabel tindakan cepat */
    #tabelTindakanCepat {
        font-size: 13px !important; 
    }

    /* 2. Style untuk Kartu KPI (Mirip BAU) */
    .border-start-primary { border-left: .25rem solid #4e73df !important; }
    .border-start-success { border-left: .25rem solid #1cc88a !important; }
    .border-start-info { border-left: .25rem solid #36b9cc !important; }
    .text-xs { font-size: .8rem; }
    .text-gray-800 { color: #3a3b45; }
    .card-body .h5 { font-size: 1.5rem !important; font-weight: 700 !important; }
    .card-body .h2.text-muted { font-size: 2rem !important; }
    .card-header h6 { font-size: 14px !important; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">

    {{-- 
      ====================================================
      BAGIAN 1: KARTU KPI
      ====================================================
    --}}
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Perlu Disposisi</div>
                            {{-- Variabel $perluDisposisi ini datang dari Controller --}}
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $perluDisposisi }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-workspace h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Sudah Didisposisi</div>
                            {{-- Variabel $sudahDisposisi ini datang dari Controller --}}
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahDisposisi }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-check-fill h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Total Surat Ditangani</div>
                            {{-- Variabel $totalDiterima ini datang dari Controller --}}
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalDiterima }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-archive-fill h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 
      ====================================================
      BAGIAN 2: CHARTS
      ====================================================
    --}}
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat Diterima (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 220px;">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Komposisi Tipe Surat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-0" style="height: 220px;">
                        <canvas id="komposisiSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- 
      ====================================================
      BAGIAN 3: TABEL DAFTAR KERJA (TINDAKAN CEPAT)
      ====================================================
    --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Tindakan Cepat (5 Surat Terakhir Perlu Disposisi)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabelTindakanCepat" class="table table-hover align-middle table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="text-center">No. Agenda</th>
                                    <th scope="col">Perihal</th>
                                    <th scope="col">Asal Surat</th>
                                    <th scope="col">Tanggal Diterima</th>
                                    <th scope="col" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Variabel $suratBaru ini datang dari Controller --}}
                                @forelse ($suratBaru as $surat)
                                <tr>
                                    <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                                    <td>{{ $surat->perihal }}</td>
                                    <td>{{ $surat->surat_dari }}</td>
                                    <td>{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->isoFormat('D MMMM YYYY') }}</td>
                                    <td class="text-center">
                                    <a href="{{ route('adminrektor.disposisi.show', $surat->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil-square me-1"></i> Tindak Lanjuti
                                    </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        Tidak ada surat baru yang memerlukan disposisi.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Card-footer untuk link ke halaman penuh --}}
                @if($perluDisposisi > 5)
                <div class="card-footer text-center bg-light border-0">
                    {{-- TODO: Nanti link ini harus diarahkan ke halaman 'surat masuk admin rektor' --}}
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        Lihat Semua {{ $perluDisposisi }} Surat yang Perlu Disposisi
                        <i class="bi bi-arrow-right-short ms-1"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

{{-- 
  ====================================================
  TAMBAHAN 2: Script Chart.js (DataTables Dihapus)
  ====================================================
--}}
@push('scripts')
{{-- jQuery TETAP DIPERLUKAN untuk Chart.js --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- DataTables dihapus --}}
{{-- <script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script> --}}

{{-- Library Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>


<script>
    // Ambil data dari Blade (dilempar oleh Controller)
    const pieLabels = @json($pieLabels);
    const pieData = @json($pieData);
    const lineLabels = @json($lineLabels);
    const lineData = @json($lineData);

    $(document).ready(function() {
        
        // --- Inisialisasi DataTables DIHAPUS ---

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
                        legend: {
                            position: 'bottom',
                        },
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
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: lineData,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false // Sembunyikan legenda
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Jumlah Surat: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                // Pastikan hanya angka bulat (integer) di sumbu Y
                                precision: 0 
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>
@endpush