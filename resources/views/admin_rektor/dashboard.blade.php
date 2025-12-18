@extends('layouts.app')

@push('styles')
<style>
    /* Style Kartu KPI */
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-info    { border-left: 0.25rem solid #36b9cc !important; }
    .text-xs { font-size: .7rem; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-gray-800 { color: #5a5c69 !important; }
    .fw-bold { font-weight: 700 !important; }
    .h5 { font-size: 1.25rem; }
    
    /* Chart Container */
    .chart-area { position: relative; height: 250px; width: 100%; }
    .chart-pie { position: relative; height: 195px; width: 100%; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    {{-- BAGIAN 1: KARTU KPI --}}
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Menunggu Disposisi</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $perluDisposisi }} Surat</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-hourglass-split text-gray-300" style="font-size: 2rem;"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Selesai Didisposisi</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahDisposisi }} Surat</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-check-circle-fill text-gray-300" style="font-size: 2rem;"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Ditangani</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalDiterima }} Surat</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-folder2-open text-gray-300" style="font-size: 2rem;"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: CHARTS --}}
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Tren Surat Masuk (7 Hari Terakhir)</h6></div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Komposisi Surat (Internal vs Eksternal)</h6></div>
                <div class="card-body">
                    <div class="chart-pie pt-2 pb-2">
                        <canvas id="komposisiSuratChart"></canvas>
                    </div>
                    <div class="mt-3 text-center small text-muted">
                        *Data berdasarkan surat yang sudah diterima Rektor
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: TABEL TINDAKAN CEPAT --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Tindakan Cepat (Surat Baru Masuk)</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0" style="font-size: 14px;">
                            <thead class="table-light">
                                <tr>
                                    <th>No Agenda</th><th>Asal Surat</th><th>Perihal</th><th>Tanggal Diterima</th><th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suratBaru as $surat)
                                <tr>
                                    <td>{{ $surat->no_agenda }}</td>
                                    <td>{{ $surat->surat_dari }}</td>
                                    <td>{{ $surat->perihal }}</td>
                                    <td>{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->isoFormat('D MMMM YYYY') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('adminrektor.disposisi.show', $surat->id) }}" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-pencil-square"></i> Proses</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada surat baru yang perlu didisposisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
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
    // Data dari Controller
    const lineLabels = @json($lineLabels);
    const lineData = @json($lineData);
    const pieLabels = @json($pieLabels);
    const pieData = @json($pieData);

    Chart.defaults.font.family = 'Nunito';
    Chart.defaults.color = '#858796';

    $(document).ready(function() {
        // 1. LINE CHART (TREN)
        const ctxLine = document.getElementById("trenSuratChart");
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: lineLabels,
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
                        data: lineData,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                    scales: {
                        x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 7 } },
                        y: {
                            min: 0, // MULAI DARI 0
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                stepSize: 1, // KELIPATAN 1 (Bulat)
                                precision: 0, // HINDARI DESIMAL
                                callback: function(value) { if (value % 1 === 0) { return value; } }
                            },
                            grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] }
                        },
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // 2. PIE CHART (KOMPOSISI)
        const ctxPie = document.getElementById("komposisiSuratChart");
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true, position: 'bottom' } },
                    cutout: '70%',
                },
            });
        }
    });
</script>
@endpush