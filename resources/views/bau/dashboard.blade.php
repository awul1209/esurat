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
    .h5 { font-size: 1.25rem; }

    /* Chart Styles */
    .chart-area { position: relative; height: 200px; width: 100%; }
    .chart-pie { position: relative; height: 245px; width: 100%; }

    /* Calendar Fix */
    #calendar { font-size: 0.75rem; }
    .fc-toolbar-title { font-size: 0.9rem !important; font-weight: bold; }
    .fc-button { font-size: 0.75rem !important; }
    .fc-event { cursor: pointer; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- BAGIAN 1: 4 KARTU KPI (DIPERBAIKI JARAKNYA) --}}
    {{-- 'gx-3' merapatkan jarak horizontal, 'mt-3' mengurangi jarak dari atas --}}
    <div class="row gx-3 mt-2">
        
        {{-- Card 1: Masuk (Rektor) - Pending --}}
        <div class="col-xl-3 col-md-6 mb-3"> {{-- Ubah mb-4 jadi mb-3 --}}
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Masuk (Untuk Rektor)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $untukRektorPending }}</div>
                            <small class="text-muted">Perlu Diteruskan</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-exclamation-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Inbox BAU --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Inbox BAU</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $inboxBau }}</div>
                            <small class="text-muted">Surat Khusus BAU</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-inbox-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Sedang di Rektor --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Sedang di Rektor</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahKeRektor }}</div>
                            <small class="text-muted">Menunggu Disposisi</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Siap ke Satker --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Siap ke Satker</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $siapKeSatker }}</div>
                            <small class="text-muted">Hasil Disposisi Rektor</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send-check-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: CHART GARIS & BAR (KOMPOSISI) --}}
    <div class="row">
        {{-- Line Chart: Tren --}}
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
                        <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> Rektor</span>
                        <span class="me-3"><i class="bi bi-circle-fill text-warning"></i> BAU</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar Chart: Komposisi Detail --}}
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

   {{-- BAGIAN 3: TABEL AKSI & KALENDER (PENGGANTI BAR CHART) --}}
    <div class="row">
        {{-- Tabel Aksi Cepat --}}
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-danger">Tindakan Cepat (Pending di BAU)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr><th>Asal</th><th>Perihal</th><th>Status</th><th class="text-center">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @forelse($suratPending as $surat)
                                <tr>
                                    <td>{{ $surat->surat_dari }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($surat->perihal, 30) }}</td>
                                    <td>
                                        @if($surat->status == 'didisposisi') <span class="badge bg-success">Siap Kirim</span>
                                        @else <span class="badge bg-secondary">Baru</span> @endif
                                    </td>
                                    <td class="text-center">
                                        @if($surat->status == 'didisposisi')
                                            <a href="{{ route('bau.disposisi.index') }}" class="btn btn-success btn-sm py-0"><i class="bi bi-send"></i></a>
                                        @else
                                            <a href="{{ route('bau.surat.eksternal') }}" class="btn btn-primary btn-sm py-0"><i class="bi bi-arrow-right"></i></a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">Tidak ada surat pending.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kalender Agenda BAU (MENGGANTIKAN BAR CHART) --}}
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-success">
                        <i class="bi bi-calendar-event me-2"></i>Agenda Surat BAU (Mendatang)
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Responsive Table Wrapper agar scrollable di mobile --}}
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
                <div class="modal-header bg-warning text-dark border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-calendar2-week me-2"></i>Agenda BAU: <span id="modalDate"></span>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    const lineLabels = @json($lineLabels);
    const dataRektor = @json($dataRektor);
    const dataBau    = @json($dataBau);
    const komposisiData = @json($komposisiData);
    // Data Kalender
    const calendarEvents = @json($calendarEvents);

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. LINE CHART (TETAP)
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

        // 2. BAR CHART KOMPOSISI (TETAP)
        const ctxKomposisi = document.getElementById("komposisiChart");
        if (ctxKomposisi) {
            new Chart(ctxKomposisi, {
                type: 'bar', 
                data: {
                    labels: ['Rektor (Int)', 'Rektor (Eks)', 'BAU (Int)', 'BAU (Eks)'],
                    datasets: [{
                        label: 'Jumlah Surat',
                        data: komposisiData,
                        backgroundColor: ['#4e73df', '#858796', '#f6c23e', '#e74a3b'],
                        borderWidth: 1
                    }],
                },
                options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } },
            });
        }

        // 3. FULLCALENDAR (BARU - PENGGANTI DISTRIBUSI)
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                contentHeight: 'auto', // Tinggi menyesuaikan konten
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: '' // Sederhana saja
                },
                buttonText: { today: 'Hari Ini' },
                events: calendarEvents,
                
                eventDidMount: function(info) {
                    info.el.title = "Klik detail";
                },

                // Logika Klik (Sama seperti dashboard lain)
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
                            var badgeClass = 'bg-warning text-dark'; // Default BAU (Kuning)
                            var borderClass = 'border-warning';

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
                                            <i class="bi bi-building fs-5 text-warning me-2"></i>
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