@extends('layouts.app')

@push('styles')
<style>
    /* Style KPI */
    .border-start-primary { border-left: .25rem solid #4e73df !important; }
    .border-start-success { border-left: .25rem solid #1cc88a !important; }
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
    
    {{-- BAGIAN 1: KARTU KPI --}}
    <div class="row mt-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Surat Masuk</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalSuratDiterima }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-inbox-fill h2 text-gray-300"></i>
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
                                Surat Masuk (Bulan Ini)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $suratBulanIni }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar2-check-fill h2 text-gray-300"></i>
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
                                Total Surat Keluar (Internal)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalSuratKeluar }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: CHARTS --}}
    <div class="row">
        {{-- LINE CHART --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat Masuk (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- PIE CHART --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Sumber Surat Masuk</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-0" style="height: 250px;">
                        <canvas id="komposisiSuratChart"></canvas>
                    </div>
                    <div class="mt-3 text-center small">
                        <span class="me-2">
                            <i class="bi bi-circle-fill" style="color: #4e73df;"></i> Eksternal
                        </span>
                        <span class="me-2">
                            <i class="bi bi-circle-fill" style="color: #1cc88a;"></i> Internal
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Ambil data chart dari controller
    const pieLabels = {!! json_encode($pieLabels) !!};
    const pieData = {!! json_encode($pieData) !!};
    const lineLabels = {!! json_encode($lineLabels) !!};
    const lineData = {!! json_encode($lineData) !!};

    $(document).ready(function () {
        
        // --- PIE CHART (Komposisi) ---
        const ctxPie = document.getElementById('komposisiSuratChart');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: ['#4e73df', '#1cc88a'], // Biru, Hijau
                        hoverBackgroundColor: ['#2e59d9', '#17a673'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
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

        // --- LINE CHART (Tren 7 Hari) ---
        const ctxLine = document.getElementById('trenSuratChart');
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: lineLabels, // Label Tanggal (11 Des, 12 Des...)
                    datasets: [{
                        label: "Surat Masuk",
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
                        data: lineData, // Data jumlah surat per hari
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: { 
                            grid: { display: false },
                            ticks: { maxTicksLimit: 7 }
                        },
                        y: { 
                            ticks: { precision: 0 }, 
                            beginAtZero: true,
                            suggestedMax: 5 // Agar grafik tidak flat jika data sedikit
                        }
                    }
                }
            });
        }

    });
</script>
@endpush