@extends('layouts.app')

@push('styles')
<style>
    /* Style 13px */
    .table { font-size: 13px; }
    .card-body .list-group-item, .card-body p { font-size: 13px; }
    .alert { font-size: 13px; }
    
    /* Style KPI Bawaan */
    .border-left-primary { border-left: .25rem solid #4e73df !important; }
    .border-left-warning { border-left: .25rem solid #f6c23e !important; }
    .border-left-info    { border-left: .25rem solid #36b9cc !important; }
    .border-left-success { border-left: .25rem solid #1cc88a !important; }
    
    .text-xs { font-size: .7rem; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-gray-800 { color: #5a5c69 !important; }
    .fw-bold { font-weight: 700 !important; }
    .h5 { font-size: 1rem; }

    /* Chart Styles */
    .chart-area { position: relative; height: 220px; width: 100%; }
    .chart-pie { position: relative; height: 265px; width: 100%; }

    /* Calendar Fix */
    #calendar { max-height: auto; font-size: 0.7rem; }
    .fc-toolbar-title { font-size: 0.9rem !important; font-weight: bold; }
    .fc-button { font-size: 0.7rem !important; }
    .fc-event { cursor: pointer; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- BAGIAN 1: 4 KARTU KPI --}}
    <div class="row gx-3 mt-2">
        
        {{-- Card 1 --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Masuk (Untuk Rektor)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $untukRektorPending }}</div>
                            <small class="text-muted">Masuk Hari Ini</small>
                        </div>
                        <div class="col-auto"><i class="bi bi-envelope-exclamation-fill h2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Inbox BAU</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $inboxBau }}</div>
                            <small class="text-muted">Total (Int + Eks)</small>
                        </div>
                        <div class="col-auto"><i class="bi bi-inbox-fill h2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Sedang di Rektor</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahKeRektor }}</div>
                            <small class="text-muted">Menunggu Disposisi</small>
                        </div>
                        <div class="col-auto"><i class="bi bi-hourglass-split h2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Siap ke Satker</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $siapKeSatker }}</div>
                            <small class="text-muted">Hasil Disposisi Rektor</small>
                        </div>
                        <div class="col-auto"><i class="bi bi-send-check-fill h2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: CHART GARIS & BAR --}}
    <div class="row">
        {{-- Line Chart --}}
        <div class="col-xl-7 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat Masuk (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
                    <div class="mt-2 text-center small">
                        <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> Rektor (Eks)</span>
                        <span class="me-3"><i class="bi bi-circle-fill text-warning"></i> BAU (Int+Eks)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar Chart --}}
        <div class="col-xl-5 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-2">
                    <h6 class="m-0 fw-bold text-primary">Detail Komposisi Surat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="komposisiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

   {{-- BAGIAN 3: TABEL AKSI & KALENDER --}}
<div class="row">
        {{-- Tabel Aksi Cepat --}}
<div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0" style="font-size:12px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Asal</th>
                                    <th>Perihal</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suratPending as $surat)
                                <tr>
                                    <td>{{ $surat->surat_dari }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($surat->perihal, 30) }}</td>
                                    <td>
                                        @if($surat->status == 'didisposisi') 
                                            <span class="badge bg-success">Siap Kirim (Balikan)</span>
                                        @elseif($surat->status == 'baru_di_bau')
                                            {{-- Bedakan Label badge agar jelas --}}
                                            @if($surat->tipe_surat == 'internal')
                                                <span class="badge bg-info text-white">Baru (Internal)</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Baru (Eksternal)</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- LOGIKA AKSI --}}
                                        @if($surat->status == 'didisposisi')
                                            {{-- Kasus 1: Surat sudah didisposisi Rektor, tugas BAU meneruskan ke Satker --}}
                                            <a href="{{ route('bau.disposisi.index') }}" class="btn btn-success btn-sm py-0" title="Kirim ke Satker">
                                                <i class="bi bi-send"></i>
                                            </a>

                                        @elseif($surat->status == 'baru_di_bau')
                                            {{-- Kasus 2: Surat Baru Masuk (Harus diteruskan ke Rektor) --}}
                                            
                                            @php
                                                // Cek tipe surat untuk menentukan arah Link
                                                $routeTarget = ($surat->tipe_surat == 'internal') 
                                                    ? route('bau.surat.internal')   // Ganti sesuai nama route index internal Anda
                                                    : route('bau.surat.eksternal'); // Ganti sesuai nama route index eksternal Anda
                                            @endphp

                                            <a href="{{ $routeTarget }}" class="btn btn-primary btn-sm py-0" title="Lihat & Teruskan ke Rektor">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="bi bi-check-circle me-1"></i> Tidak ada surat pending. Pekerjaan selesai!
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kalender Agenda BAU --}}
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-calendar-event me-2"></i>Agenda Surat BAU
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL KALENDER --}}
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-dark border-0">
                    <h6 class="modal-title fw-bold" style="color:white;">
                        <i class="bi bi-calendar2-week me-2" style="color:white;"></i>Agenda BAU: <span id="modalDate"></span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
{{-- Library --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
{{-- FIX: TAMBAHKAN SCRIPT FULLCALENDAR INI AGAR TIDAK EROR --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
    const lineLabels = @json($lineLabels);
    const dataRektor = @json($dataRektor);
    const dataBau    = @json($dataBau);
    const komposisiData = @json($komposisiData);
    const calendarEvents = @json($calendarEvents);

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. LINE CHART
        const ctxLine = document.getElementById("trenSuratChart");
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: lineLabels,
                    datasets: [
                        { label: "Untuk Rektor", lineTension: 0.3, backgroundColor: "rgba(78, 115, 223, 0.05)", borderColor: "rgba(78, 115, 223, 1)", pointRadius: 3, pointBackgroundColor: "rgba(78, 115, 223, 1)", data: dataRektor },
                        { label: "Inbox BAU", lineTension: 0.3, backgroundColor: "rgba(246, 194, 62, 0.05)", borderColor: "rgba(246, 194, 62, 1)", pointRadius: 3, pointBackgroundColor: "rgba(246, 194, 62, 1)", data: dataBau }
                    ],
                },
                options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
            });
        }

        // 2. BAR CHART KOMPOSISI
        const ctxKomposisi = document.getElementById("komposisiChart");
        if (ctxKomposisi) {
            new Chart(ctxKomposisi, {
                type: 'bar', 
                data: {
                    labels: ['Rektor (Int)', 'Rektor (Eks)', 'BAU (Int)', 'BAU (Eks)'],
                    datasets: [{
                        label: 'Jumlah Surat',
                        data: komposisiData,
                        backgroundColor: ['#4e73df', '#1114aeff', '#1cc88a', '#f6c23e'], 
                        borderWidth: 1
                    }],
                },
                options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } },
            });
        }

        // 3. FULLCALENDAR
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
                            var badgeClass = (props.tipe === 'Internal') ? 'bg-success text-white' : 'bg-warning text-dark';
                            var borderClass = (props.tipe === 'Internal') ? 'border-success' : 'border-warning';

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
                                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.75rem;">${props.perihal_full}</h6>
                                        <div class="d-flex align-items-center bg-light border rounded p-2">
                                            <i class="bi bi-building fs-5 text-secondary me-2"></i>
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