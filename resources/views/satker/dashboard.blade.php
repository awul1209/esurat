@extends('layouts.app')

@push('styles')
{{-- FullCalendar CSS --}}
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
    /* Card Styles */
    .border-left-primary { border-left: .25rem solid #4e73df !important; }
    .border-left-success { border-left: .25rem solid #1cc88a !important; }
    .border-left-info    { border-left: .25rem solid #36b9cc !important; }
    .border-left-warning { border-left: .25rem solid #f6c23e !important; }
    
    .text-xs { font-size: .75rem; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-gray-800 { color: #5a5c69 !important; }
    .fw-bold { font-weight: 700 !important; }

    /* Calendar Fix */
    #calendar {
        max-height: 250px;
        font-size: 0.60rem;
    }

    .fc-toolbar-title { font-size: 0.8rem !important; font-weight: bold; }
    .fc-button { font-size: 0.65rem !important; }
    .fc-event { cursor: pointer; }

 /* 1. Container Scroll */
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f8f9fc; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d3e2; border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #858796; }
    .custom-scroll{
        height:34vh;
    }
    .chart-pie{
            height:25vh;
    }

    /* 2. Log Item Box */
    .log-item {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
        position: relative;
        overflow: hidden; /* Agar border kiri tidak bocor */
    }
    .log-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.10) !important;
        border-color: #d1d3e2;
        z-index: 1;
    }

    /* Indikator Warna Kiri */
    .border-left-indicator {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
    }
    .indicator-primary { background-color: #4e73df; }
    .indicator-success { background-color: #1cc88a; }
    .indicator-warning { background-color: #f6c23e; }
    .indicator-danger  { background-color: #e74a3b; }

    /* 3. Timeline Vertical Line */
    .log-timeline {
        position: relative;
        padding-left: 25px; /* Memberi ruang untuk garis */
        margin-left: 10px;
        border-left: 2px solid #eaecf4; /* Garis lurus abu-abu muda */
        padding-bottom: 0;
    }

    /* 4. Item dalam Timeline */
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem; /* Jarak antar item history */
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }

    /* 5. Titik/Dot Timeline */
    .timeline-dot {
        position: absolute;
        left: -31px; /* KALIBRASI POSISI TITIK AGAR PAS DI GARIS */
        top: 3px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #858796; /* Default border */
        z-index: 2;
    }
    /* Warna Dot */
    .dot-primary { border-color: #4e73df; }
    .dot-success { border-color: #1cc88a; }
    .dot-warning { border-color: #f6c23e; }
    .dot-danger  { border-color: #e74a3b; }
    .dot-secondary{ border-color: #858796; }

    /* Typography Khusus Log */
    .log-header-title { font-size: 1rem; color: #2e303e; font-weight: 700; }
    .timeline-status { font-size: 0.9rem; font-weight: 700; color: #5a5c69; margin-bottom: 2px;}
    .timeline-time { font-size: 0.75rem; color: #858796; margin-bottom: 4px; display: block;}
    .timeline-desc { font-size: 0.85rem; color: #5a5c69; line-height: 1.4; }
    .timeline-actor { font-size: 0.75rem; font-weight: 600; margin-top: 4px; display: block; color: #4e73df; }

    .card-log{
        height:200px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    
  {{-- ========================================================== --}}
    {{-- BAGIAN 1: 6 KARTU STATISTIK (RESPONSIF) --}}
    {{-- Desktop: 1 Baris (6 Card) | Laptop: 2 Baris (3 Card/baris) --}}
    {{-- ========================================================== --}}
    <div class="row mt-2">
        
        {{-- CARD 1: Masuk INTERNAL --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Masuk (Int)</div> {{-- Disingkat agar muat --}}
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalMasukInternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-check-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: Masuk EKSTERNAL --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Masuk (Eks)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalMasukEksternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-paper-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 3: Masuk BULAN INI --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Masuk (Bulan)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalMasukSebulan }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-check-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 4: Keluar INTERNAL --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Keluar (Int)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalKeluarInternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 5: Keluar EKSTERNAL --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2" style="border-left: .25rem solid #e74a3b !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                Keluar (Eks)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalKeluarEksternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-exclamation-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 6: Keluar BULAN INI --}}
        <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2" style="border-left: .25rem solid #858796 !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">
                                Keluar (Bulan)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalKeluarSebulan }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-range-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

 {{-- ========================================================== --}}
    {{-- BAGIAN 2: LOG AKTIVITAS (KIRI) & PIE CHART (KANAN) --}}
    {{-- ========================================================== --}}
    <div class="row">

        {{-- KOLOM 1: LOG AKTIVITAS (LEBAR) --}}
        <div class="col-xl-8 col-lg-8 mb-2">
            <div class="card card-log shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i>Log Aktivitas Surat
                    </h6>
                    {{-- Tombol Refresh / Badge --}}
                    <span class="badge bg-primary rounded-pill"><i class="bi bi-lightning-fill"></i> Realtime</span>
                </div>
                
                <div class="card-body bg-light custom-scroll" style="overflow-y: auto; padding: 1.25rem;">
                    
                    @forelse($activityLogs as $log)
                    
                    {{-- WRAPPER ITEM LOG --}}
                    <a href="{{ $log['url'] }}" class="d-block text-decoration-none log-item shadow-sm p-3">
                        
                        {{-- Garis Warna Kiri --}}
                        <div class="border-left-indicator indicator-{{ $log['color'] }}"></div>

                        {{-- A. HEADER SURAT (Judul & Badge) --}}
                        <div class="ps-2 mb-3 border-bottom pb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-{{ $log['color'] }} bg-opacity-10 text-{{ $log['color'] }} mb-1">
                                        {{ $log['kategori'] }} {{ $log['tipe'] }}
                                    </span>
                                    <div class="log-header-title text-truncate" style="max-width: 500px;" title="{{ $log['judul'] }}">
                                        {{ $log['judul'] }}
                                    </div>
                                </div>
                                <div class="text-end">
                                     <small class="text-muted fst-italic" style="font-size: 0.7rem;">ID: #{{ $log['id'] }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- B. TIMELINE HISTORY --}}
                        <div class="ps-2">
                            <div class="log-timeline">
                                
                                @foreach($log['history'] as $index => $hist)
<div class="timeline-item">
    <div class="timeline-dot {{ $index == 0 ? 'dot-'.$log['color'] : 'dot-secondary' }}"></div>
    
    <div class="d-flex justify-content-between align-items-center">
        <div class="timeline-status">{{ $hist['status'] }}</div>
        <span class="timeline-time"><i class="bi bi-calendar3 me-1"></i>{{ $hist['waktu'] }}</span>
    </div>

    <div class="timeline-desc">
        {{ $hist['ket'] }}
    </div>

    <span class="timeline-actor">
        <i class="bi bi-person-fill me-1"></i> {{ $hist['aktor'] }}
    </span>

    {{-- Tampilkan Info BAU hanya jika ada data dan ini adalah item history terbaru (index 0) --}}
    @if($index == 0 && isset($log['tgl_bau']) && $log['tgl_bau'])
        <div class="mt-1">
            <small class="text-success fw-bold">
                <i class="fas fa-check-double me-1"></i> 
                Diteruskan BAU: {{ $log['tgl_bau'] }} WIB
            </small>
        </div>
    @endif
</div>
@endforeach

                            </div>
                        </div>
                    </a>

                    @empty
                    <div class="text-center py-5 mt-5">
                        <i class="bi bi-clipboard-x text-gray-300 display-4"></i>
                        <p class="text-muted mt-3">Belum ada aktivitas surat tercatat.</p>
                    </div>
                    @endforelse

                </div>
            </div>
        </div>

{{-- KOLOM 2: PIE CHART (KANAN) --}}
<div class="col-xl-4 col-lg-4 mb-2">
    <div class="card shadow mb-4 border-0 hover-animate">
        <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between border-bottom-0">
            <h6 class="m-0 fw-bold text-primary">Komposisi Detail Surat</h6>
        </div>
        <div class="card-body">
            <div class="chart-pie pt-0 pb-0">
                <canvas id="komposisiSuratChart"></canvas>
            </div>
            
            {{-- Legenda Sinkron (Urutan: Biru, Hijau, Kuning, Merah) --}}
            <div class="mt-4 d-flex flex-wrap justify-content-center small fw-bold">
                <span class="mx-2 mb-2">
                    <i class="bi bi-circle-fill text-primary"></i> 
                    <span class="text-primary">Masuk Int</span>
                </span>
                
                <span class="mx-2 mb-2">
                    <i class="bi bi-circle-fill text-success"></i> 
                    <span class="text-success">Masuk Eks</span>
                </span>

                <span class="mx-2 mb-2">
                    <i class="bi bi-circle-fill text-warning"></i> 
                    <span class="text-warning">Keluar Int</span>
                </span>

                <span class="mx-2 mb-2">
                    <i class="bi bi-circle-fill text-danger"></i> 
                    <span class="text-danger">Keluar Eks</span>
                </span>
            </div>
        </div>
    </div>
</div>

    </div>

    {{-- ========================================================== --}}
    {{-- BAGIAN 3: AGENDA & KALENDER --}}
    {{-- ========================================================== --}}
    <div class="row">
{{-- AGENDA CHART --}}
<div class="col-xl-8 col-lg-7">
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white border-bottom-0">
            <h6 class="m-0 fw-bold text-primary">Agenda Kegiatan Mendatang</h6>
        </div>
        <div class="card-body">
            <div class="chart-area" style="height: 250px;">
                <canvas id="agendaChart"></canvas>
            </div>
        </div>
    </div>
</div>

        {{-- KALENDER --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-2">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-calendar-event me-1"></i> Kalender Kegiatan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive"> 
                        <div id='calendar' style="min-width: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL AGENDA KALENDER (Dipindahkan ke dalam Section Content agar rapi) --}}
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-calendar2-week me-2"></i>Agenda: <span id="modalDate"></span>
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
{{-- PENUTUP SECTION CONTENT (Hanya 1 kali) --}}

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Ambil Data dari Controller
 

    const agendaLabels = {!! json_encode($agendaLabels ?? []) !!};
    const agendaValues = {!! json_encode($agendaValues ?? []) !!};
    const calendarEvents = {!! json_encode($calendarEvents ?? []) !!};

    const pieLabels = @json($pieLabels);
    const pieData   = @json($pieData);

    document.addEventListener('DOMContentLoaded', function() {
        
    const ctxPie = document.getElementById('komposisiSuratChart');
if (ctxPie) {
    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData, // Urutan dari Controller: Int Masuk, Eks Masuk, Int Keluar, Eks Keluar
                backgroundColor: [
                    '#4e73df', // Biru (Masuk Int) - Index 0
                    '#1cc88a', // Hijau (Masuk Eks) - Index 1
                    '#f6c23e', // Kuning (Keluar Int) - Index 2
                    '#e74a3b'  // Merah (Keluar Eks) - Index 3
                ],
                hoverBackgroundColor: [
                    '#2e59d9', 
                    '#17a673', 
                    '#dda20a', 
                    '#be2617'
                ],
                borderColor: '#ffffff',
                borderWidth: 3,
                // Sekarang yang menonjol adalah Masuk Int (Index 0)
                offset: [20, 0, 0, 0], 
                hoverOffset: 15
            }],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgb(26, 25, 25)",
                    bodyColor: "#ffffff",
                    borderColor: '#111111',
                    borderWidth: 1,
                    displayColors: true,
                    padding: 6
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1200,
                easing: 'easeOutQuart'
            }
        },
    });
}
 const ctxAgenda = document.getElementById('agendaChart');

if (ctxAgenda) {
    new Chart(ctxAgenda, {
        type: 'bar',
        data: {
            labels: @json($agendaLabels),
            datasets: [
                {
                    label: "Internal",
                    backgroundColor: "#4e73df", // Biru
                    hoverBackgroundColor: "#2e59d9",
                    data: @json($agendaInternalData),
                },
                {
                    label: "Eksternal",
                    backgroundColor: "#1cc88a", // Hijau
                    hoverBackgroundColor: "#17a673",
                    data: @json($agendaEksternalData),
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    grid: { display: false },
                    stacked: false // false agar batang berjejer sampingan
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                legend: { display: true, position: 'bottom' }
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
                    info.el.title = "Klik untuk melihat detail";
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
                            var isInternal  = (props.tipe === 'Internal');
                            var badgeClass  = isInternal ? 'bg-primary bg-gradient' : 'bg-warning bg-gradient text-dark';
                            var borderClass = isInternal ? 'border-primary' : 'border-warning';
                            var iconColor   = isInternal ? 'text-primary' : 'text-warning text-dark';

                            listHtml += `
                                <div class="card mb-3 shadow-sm border-0 border-start border-4 ${borderClass}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge ${badgeClass} rounded-pill px-3 py-2" style="font-size: 0.85rem;">
                                                ${props.tipe}
                                            </span>
                                            <div class="text-end" style="line-height: 1.1;">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">No. Surat:</small>
                                                <span class="fw-bold text-dark small">
                                                    <i class="bi bi-envelope-paper me-1"></i> ${props.nomor_surat}
                                                </span>
                                            </div>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-3 mt-1" style="line-height: 1.4; font-size: 1rem;">
                                            ${props.perihal_full}
                                        </h6>
                                        <div class="d-flex align-items-center bg-light border rounded p-2">
                                            <div class="me-3 ps-1">
                                                 <i class="bi bi-building fs-4 ${iconColor}"></i>
                                            </div>
                                            <div style="line-height: 1.2;">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Dikirim Oleh:</small>
                                                <strong class="text-dark" style="font-size: 0.9rem;">${props.pengirim}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        listHtml = '<div class="p-4 text-center text-muted">Data tidak ditemukan.</div>';
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