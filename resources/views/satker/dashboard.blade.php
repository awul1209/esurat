@extends('layouts.app')

@push('styles')
<style>
    /* Style KPI (Sama seperti Satker) */
    .border-start-primary { border-left: .25rem solid #4e73df !important; }
    .border-start-info { border-left: .25rem solid #36b9cc !important; }
    .border-start-warning { border-left: .25rem solid #f6c23e !important; }
    .text-xs { font-size: .8rem; }
    .text-gray-800 { color: #3a3b45; }
    .card-body .h5 { font-size: 1.5rem !important; font-weight: 700 !important; }
    .card-body .h2.text-muted { font-size: 2rem !important; }
    .card-header h6 { font-size: 14px !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    
    <h1 class="h3 mb-4 text-gray-800">Dashboard Satker</h1>

    {{-- BAGIAN 1: KARTU KPI (Mirip Satker) --}}
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Surat Diterima</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalSuratDiterima }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-archive-fill h2 text-muted"></i>
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
                                Surat Diterima (Bulan Ini)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $suratBulanIni }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-event-fill h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Total Surat Eksternal</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalEksternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-building h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: CHARTS (Sama seperti Satker) --}}
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
    
    {{-- BAGIAN 3: TABEL DAN MODAL DIHAPUS DARI HALAMAN INI --}}

</div>
@endsection

@push('scripts')
{{-- Import jQuery dan Chart.js --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Ambil data chart dari controller
    const pieLabels = @json($pieLabels);
    const pieData = @json($pieData);
    const lineLabels = @json($lineLabels);
    const lineData = @json($lineData);

    $(document).ready(function () {
        
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

    });
</script>
@endpush