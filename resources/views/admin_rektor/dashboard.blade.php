@extends('layouts.app')

@push('styles')
{{-- FullCalendar CSS --}}
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
    /* Style Card KPI */
    .border-left-primary { border-left: .25rem solid #4e73df !important; }
    .border-left-warning { border-left: .25rem solid #f6c23e !important; }
    .border-left-success { border-left: .25rem solid #1cc88a !important; }
    .border-left-info    { border-left: .25rem solid #36b9cc !important; }
    
    .text-xs { font-size: .75rem; }
    .fw-bold { font-weight: 700 !important; }
    .text-gray-800 { color: #5a5c69 !important; }

    /* Calendar Fix */
    #calendar { max-height: 250px; font-size: 0.65rem; }
    .fc-toolbar-title { font-size: 0.7rem !important; font-weight: bold; }
    .fc-button { font-size: 0.65rem !important; }
    .fc-event { cursor: pointer; }


    .card-kpi {
        transition: all 0.3s cubic-bezier(.25,.8,.25,1);
       
    }
    .card-kpi:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.06) !important;
    }
    .border-left-primary { border-left: .35rem solid #4e73df !important; }
    .border-left-warning { border-left: .35rem solid #f6c23e !important; }
    .border-left-success { border-left: .35rem solid #1cc88a !important; }
    .border-left-info    { border-left: .35rem solid #36b9cc !important; }
    .border-left-danger  { border-left: .35rem solid #e74a3b !important; }
    .border-left-dark    { border-left: .35rem solid #5a5c69 !important; }
    
    .text-xs { font-size: .7rem; letter-spacing: 0.5px; }
    .icon-circle {
        height: 45px;
        width: 45px;
        background-color: #f8f9fc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

 {{-- BAGIAN 1: 6 KARTU KPI (6 Kolom Desktop, 3 Kolom Laptop) --}}
<div class="row gx-3 mt-2">
    
    {{-- Card 1: Masuk Internal --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Masuk Internal</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $masukInternalHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-door-open-fill h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Masuk Eksternal --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Masuk Eksternal</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $masukEksternalHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-building h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Perlu Ditangani --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Perlu Ditangani</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $perluDitanganiHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-envelope-exclamation-fill h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 4: Keluar Internal --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-dark shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-dark text-uppercase mb-1">Keluar Internal</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $keluarInternalHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-send-fill h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 5: Keluar Eksternal --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Keluar Eksternal</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $keluarEksternalHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-send-plus-fill h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 6: Sudah Ditangani --}}
    <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
        <div class="card card-kpi border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Sudah Ditangani</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahDitanganiHariIni }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle-fill h3 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

    {{-- BAGIAN 2: CHARTS UTAMA --}}
<div class="row">
        {{-- Line Chart --}}
<div class="col-xl-8 col-lg-8">
    <div class="card shadow mb-4 border-0 hover-animate">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Tren Aktivitas Surat (7 Hari Terakhir)</h6>
        </div>
        <div class="card-body">
            <div class="chart-area" style="height: 300px;">
                <canvas id="trenSuratChart"></canvas>
            </div>
        </div>
    </div>
</div>
        
{{-- Pie Chart --}}
<div class="col-xl-4 col-lg-4">
    <div class="card shadow mb-4 border-0 hover-animate"> {{-- Tambahkan class hover-animate agar konsisten --}}
        <div class="card-header py-3 bg-white border-bottom-0">
            <h6 class="m-0 fw-bold text-primary">Komposisi Total Surat</h6>
        </div>
        <div class="card-body">
            <div class="chart-pie pt-0 pb-0" style="height: 300px;"> {{-- Tinggi ditambah sedikit agar irisan tidak terpotong --}}
                <canvas id="komposisiSuratChart"></canvas>
            </div>
        </div>
    </div>
</div>
    </div>

    {{-- BAGIAN 3: AGENDA & KALENDER (Arsip Rektor) --}}
    <div class="row">
       {{-- Tabel Aksi Cepat --}}
<div class="col-xl-8 col-lg-7">
    <div class="card shadow mb-4 border-0 hover-animate">
        <div class="card-header py-3 bg-white border-bottom-0 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Aksi Cepat (Perlu Penanganan)
            </h6>
            <span class="badge bg-light text-primary border">{{ count($aksiCepat) }} Surat Menunggu</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Pengirim</th>
                            <th>Perihal</th>
                            <th>Tipe</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($aksiCepat as $item)
                        <tr>
                            <td class="fw-bold text-dark">{{ \Illuminate\Support\Str::limit($item->surat_dari, 20) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->perihal, 35) }}</td>
                            <td>
                                @if($item->tipe_surat == 'internal')
                                    <span class="badge bg-primary-soft text-primary">Internal</span>
                                @else
                                    <span class="badge bg-info-soft text-info">Eksternal</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Logika Link Dinamis Berdasarkan Tipe Surat --}}
                                @php
                                    $targetRoute = ($item->tipe_surat == 'internal') 
                                        ? route('adminrektor.suratmasuk.internal') 
                                        : route('adminrektor.suratmasuk.index');
                                @endphp
                                
                                <a href="{{ $targetRoute }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                    Tangani <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle h1 d-block mb-2 text-success"></i>
                                Semua surat sudah ditangani!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

        {{-- Kalender --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-calendar-check me-1"></i> Kalender Arsip Rektor
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                         <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL KALENDER --}}
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-calendar2-week me-2"></i>Detail Agenda: <span id="modalDate"></span>
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light p-3">
                    <div id="calendarList"></div>
                </div>
                <div class="modal-footer bg-white border-top-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
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
// Data Tren 7 Hari (Untuk Stacked Area Chart)
    const lineLabels = @json($lineLabels);
    const dataMasukInternal = @json($dataMasukInternal);
    const dataMasukEksternal = @json($dataMasukEksternal);
    const dataKeluarInternal = @json($dataKeluarInternal);
    const dataKeluarEksternal = @json($dataKeluarEksternal);

    // Data Komposisi (Untuk Pie Chart Explode)
    const pieLabels = @json($pieLabels);
    const pieData   = @json($pieData);

    // Data Lainnya

    const calendarEvents = @json($calendarEvents);

    document.addEventListener('DOMContentLoaded', function() {
   // 1. LINE CHART (Tren 7 Hari) - Perbaikan Nilai Akurat
const ctxLine = document.getElementById("trenSuratChart");
if (ctxLine) {
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [
                {
                    label: "Masuk Internal",
                    fill: true, 
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.1)", // Transparansi dikurangi agar tidak menutupi
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    data: dataMasukInternal,
                },
                {
                    label: "Masuk Eksternal",
                    fill: true,
                    lineTension: 0.3,
                    backgroundColor: "rgba(54, 185, 204, 0.1)",
                    borderColor: "rgba(54, 185, 204, 1)",
                    pointBackgroundColor: "rgba(54, 185, 204, 1)",
                    data: dataMasukEksternal,
                },
                {
                    label: "Keluar Internal",
                    fill: true,
                    lineTension: 0.3,
                    backgroundColor: "rgba(90, 92, 105, 0.1)",
                    borderColor: "rgba(90, 92, 105, 1)",
                    pointBackgroundColor: "rgba(90, 92, 105, 1)",
                    data: dataKeluarInternal,
                },
                {
                    label: "Keluar Eksternal",
                    fill: true,
                    lineTension: 0.3,
                    backgroundColor: "rgba(231, 74, 59, 0.1)",
                    borderColor: "rgba(231, 74, 59, 1)",
                    pointBackgroundColor: "rgba(231, 74, 59, 1)",
                    data: dataKeluarEksternal,
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: { 
                legend: { display: true, position: 'top', labels: { usePointStyle: true } },
                tooltip: {
                    mode: 'index',
                    intersect: false, // Memudahkan melihat detail saat kursor di atas grafik
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { 
                    stacked: false, // UBAH KE FALSE: Agar angka sumbu Y sesuai data asli
                    beginAtZero: true, 
                    ticks: { 
                        precision: 0,
                        stepSize: 1 // Memastikan angka yang muncul adalah angka bulat (1, 2, 3...)
                    } 
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

      // 2. PIE CHART (Komposisi Total)
const ctxPie = document.getElementById("komposisiSuratChart");
if (ctxPie) {
    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: [
                    '#4e73df', // Biru (Masuk Internal)
                    '#36b9cc', // Cyan (Masuk Eksternal)
                    '#5a5c69', // Abu (Keluar Internal)
                    '#e74a3b'  // Merah (Keluar Eksternal)
                ],
                hoverBackgroundColor: ['#2e59d9', '#2c9faf', '#373840', '#be2617'],
                borderColor: "#ffffff",
                borderWidth: 3,
                // Efek Irisan Menonjol (Exploded) seperti gambar pie.png
                // Index 0 (Masuk Internal) akan menonjol keluar sebesar 20px
                offset: [20, 0, 0, 0], 
                hoverOffset: 20
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    enabled: true,
                    cornerRadius: 4,
                    bodySpacing: 4,
                }
            },
            // Animasi yang smooth dan modern
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500,
                easing: 'easeOutQuart'
            }
        }
    });
}

        // 3. AGENDA CHART (Bar Chart)
        const ctxAgenda = document.getElementById('agendaChart');
        if (ctxAgenda) {
            new Chart(ctxAgenda, {
                type: 'bar',
                data: {
                    labels: agendaLabels.length > 0 ? agendaLabels : ['Tidak ada agenda'],
                    datasets: [{
                        label: "Jumlah Kegiatan",
                        backgroundColor: "#4e73df",
                        hoverBackgroundColor: "#1730d3ff",
                        borderColor: "#4e73df",
                        data: agendaValues.length > 0 ? agendaValues : [0],
                        barPercentage: 0.5,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { ticks: { precision: 0 }, beginAtZero: true }
                    }
                }
            });
        }

        // 4. FULLCALENDAR
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                contentHeight: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: '' 
                },
                buttonText: { today: 'Hari Ini' },
                events: calendarEvents,
                eventDidMount: function(info) {
                    info.el.title = "Klik detail";
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    
                    var clickedDateStr = info.event.startStr;
                    var dateObj = new Date(clickedDateStr);
                    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    var formattedDate = dateObj.toLocaleDateString('id-ID', options);

                    var allEvents = calendar.getEvents();
                    var eventsOnDay = allEvents.filter(function(evt) {
                        return evt.startStr === clickedDateStr;
                    });

                    var listHtml = '';
                    if (eventsOnDay.length > 0) {
                        eventsOnDay.forEach(function(evt) {
                            var props = evt.extendedProps;
                            var isInternal = (props.tipe === 'Internal');
                            var badgeClass = isInternal ? 'bg-primary bg-gradient' : 'bg-warning bg-gradient text-dark';
                            var borderClass = isInternal ? 'border-primary' : 'border-warning';
                            var iconColor   = isInternal ? 'text-primary' : 'text-warning text-dark';

                            listHtml += `
                                <div class="card mb-3 shadow-sm border-0 border-start border-4 ${borderClass}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge ${badgeClass} rounded-pill px-3 py-2" style="font-size: 0.8rem;">${props.tipe}</span>
                                            <div class="text-end">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">No. Surat:</small>
                                                <span class="fw-bold text-dark small">${props.nomor_surat}</span>
                                            </div>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">${props.perihal_full}</h6>
                                        <div class="d-flex align-items-center bg-light border rounded p-2">
                                            <i class="bi bi-building fs-5 ${iconColor} me-2"></i>
                                            <div>
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Pengirim:</small>
                                                <strong class="text-dark small">${props.pengirim}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        listHtml = '<div class="p-3 text-center text-muted">Tidak ada agenda.</div>';
                    }

                    $('#modalDate').text(formattedDate);
                    $('#calendarList').html(listHtml);
                    $('#calendarModal').modal('show');
                }
            });
            calendar.render();
        }
    });
</script>
@endpush