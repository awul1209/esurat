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
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    
    {{-- ========================================================== --}}
    {{-- BAGIAN 1: 4 KARTU STATISTIK --}}
    {{-- ========================================================== --}}
    <div class="row mt-2">
        
        {{-- CARD 1: Surat Masuk HARI INI --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Masuk (Hari Ini)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $suratMasukHariIni }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-exclamation-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: Surat Masuk 1 BULAN TERAKHIR --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Masuk (Bulan Ini)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $suratSebulanTerakhir }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-check-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 3: Surat Keluar INTERNAL --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Keluar (Internal)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalKeluarInternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 4: Surat Keluar EKSTERNAL --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Keluar (Eksternal)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalKeluarEksternal }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-exclamation-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- BAGIAN 2: CHARTS UTAMA (LINE & PIE) --}}
    {{-- ========================================================== --}}
    <div class="row">
        {{-- LINE CHART --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat Masuk (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 205px;">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- PIE CHART --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-1 bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-primary mt-1">Sumber Surat Masuk</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-0" style="height: 180px;">
                        <canvas id="komposisiSuratChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> Eksternal</span>
                        <span class="me-3"><i class="bi bi-circle-fill " style="color:#f6c23e;"></i> Internal</span>
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
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-white border-bottom-0 d-flex justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> Agenda Kegiatan Mendatang
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 260px;">
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
    const pieLabels = {!! json_encode($pieLabels) !!};
    const pieData = {!! json_encode($pieData) !!};
    const lineLabels = {!! json_encode($lineLabels) !!};
    const lineData = {!! json_encode($lineData) !!};
    const agendaLabels = {!! json_encode($agendaLabels ?? []) !!};
    const agendaValues = {!! json_encode($agendaValues ?? []) !!};
    const calendarEvents = {!! json_encode($calendarEvents ?? []) !!};

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. PIE CHART
        const ctxPie = document.getElementById('komposisiSuratChart');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: ['#4e73df', '#f6c23e'],
                        hoverBackgroundColor: ['#2e59d9', '#f6c23e'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            });
        }

        // 2. LINE CHART
        const ctxLine = document.getElementById('trenSuratChart');
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
                        data: lineData,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } },
                        y: { ticks: { precision: 0 }, beginAtZero: true }
                    }
                }
            });
        }

        // 3. AGENDA CHART
        const ctxAgenda = document.getElementById('agendaChart');
        if (ctxAgenda) {
            new Chart(ctxAgenda, {
                type: 'bar',
                data: {
                    labels: agendaLabels.length > 0 ? agendaLabels : ['Tidak ada agenda'],
                    datasets: [{
                        label: "Jumlah Kegiatan",
                        backgroundColor: "#4e73df",
                        hoverBackgroundColor: "#2c44afff",
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