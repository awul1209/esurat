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

    .border-left-danger  { border-left: .25rem solid #e74a3b !important; }
.border-left-dark    { border-left: .25rem solid #5a5c69 !important; }

    /* Chart Styles */
    .chart-area { position: relative; height: 230px; width: 100%; }
    .chart-pie { position: relative; height: 225px; width: 100%; }

    /* Calendar Fix */
    #calendar { max-height: auto; font-size: 0.7rem; }
    .fc-toolbar-title { font-size: 0.9rem !important; font-weight: bold; }
    .fc-button { font-size: 0.7rem !important; }
    .fc-event { cursor: pointer; }

    /* card 4 */
    /* Gaya dasar konsisten dengan 6 kartu di atas */
.border-left-primary { border-left: .25rem solid #4e73df !important; }
.border-left-danger  { border-left: .25rem solid #e74a3b !important; }
.border-left-info    { border-left: .25rem solid #36b9cc !important; }
.border-left-warning { border-left: .25rem solid #f6c23e !important; }

/* Animasi khusus untuk Card 4 */
.hover-animate {
    transition: all 0.3s ease-in-out;
    cursor: pointer;
}

.hover-animate:hover {
    transform: translateY(-5px); /* Mengangkat kartu */
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important; /* Bayangan lebih dalam */
}

/* Link styling agar rapi */
.card-link {
    font-size: 0.75rem;
    text-decoration: none;
    transition: color 0.2s;
}
.card-link:hover {
    text-decoration: underline;
}

.hover-animate {
    transition: all 0.3s ease-in-out;
}
.hover-animate:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
{{-- BAGIAN 1: 6 KARTU KPI --}}
<div class="row gx-3 mt-2">
    
    {{-- Card 1: Masuk Rektor (Primary) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-primary shadow h-100 py-2 hover-animate"  style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Masuk (Rektor)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $untukRektorPending ?? 0 }}</div>
                        <small class="text-muted">Hari Ini</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-envelope-exclamation-fill h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Keluar Rektor (Danger) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-danger shadow h-100 py-2 hover-animate"   style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Keluar Rektor</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $keluarRektorHariIni ?? 0 }}</div>
                        <small class="text-muted">Hari Ini</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-file-earmark-arrow-up-fill h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Inbox BAU (Warning) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-warning shadow h-100 py-2 hover-animate"   style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Inbox BAU</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $inboxBau ?? 0 }}</div>
                        <small class="text-muted">Hari Ini</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-inbox-fill h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 4: Keluar BAU (Dark) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-dark shadow h-100 py-2 hover-animate"   style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-dark text-uppercase mb-1">Keluar BAU</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $keluarBauHariIni ?? 0 }}</div>
                        <small class="text-muted">Hari Ini</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-person-badge-fill h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 5: Sedang di Rektor (Info) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-info shadow h-100 py-2 hover-animate"   style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Di Rektor</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $sudahKeRektor ?? 0 }}</div>
                        <small class="text-muted">Proses Review</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-hourglass-split h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 6: Siap ke Satker (Success) --}}
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-left-success shadow h-100 py-2 hover-animate"   style="cursor: default;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Siap ke Satker</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $siapKeSatker ?? 0 }}</div>
                        <small class="text-muted">Disposisi Rektor</small>
                    </div>
                    <div class="col-auto"><i class="bi bi-send-check-fill h2 text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- BAGIAN 2: 4 KARTU PENDING TASK (MODERN & CONSISTENT) --}}
<div class="row gx-3">
    
    {{-- Card 1: Masuk Rektor --}}
    <div class="col-3 mb-4">
        {{-- TAMBAHKAN hover-animate DI SINI --}}
        <div class="card border-left-primary shadow h-100 py-2 hover-animate">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Verifikasi SM Rektor</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $rektorMasukPending }}</div>
                        <a href="{{ route('bau.surat.internal') }}" class="card-link text-primary mt-2 d-inline-block">
                            Cek Antrean <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-shield-lock-fill h2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Keluar Rektor Internal --}}
    <div class="col-3 mb-4">
        {{-- TAMBAHKAN hover-animate DI SINI --}}
        <div class="card border-left-danger shadow h-100 py-2 hover-animate">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Verifikasi SK Rektor (Int)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $rektorKeluarInternalPending }}</div>
                        <a href="{{ route('bau.surat-internal-rektor.index') }}" class="card-link text-danger mt-2 d-inline-block">
                            Tinjau Draft <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-medical h2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Keluar Rektor Eksternal --}}
    <div class="col-3 mb-4">
        {{-- TAMBAHKAN hover-animate DI SINI --}}
        <div class="card border-left-info shadow h-100 py-2 hover-animate">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Verifikasi SK Rektor (Eks)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $rektorKeluarEksternalPending }}</div>
                        <a href="{{ route('bau.verifikasi-rektor.index') }}" class="card-link text-info mt-2 d-inline-block">
                            Tinjau Draft <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-globe2 h2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 4: Inbox BAU --}}
    <div class="col-3 mb-4">
        {{-- hover-animate SUDAH ADA --}}
        <div class="card border-left-warning shadow h-100 py-2 hover-animate">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Inbox BAU </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $bauInboxPending }}</div>
                        <a href="{{ route('bau.inbox') }}" class="card-link text-warning mt-2 d-inline-block fw-bold">
                            Buka Sekarang <i class="bi bi-cursor-fill"></i>
                        </a>
                    </div>
                    <div class="col-auto">
                        <div class="p-2 bg-light rounded-circle">
                            <i class="bi bi-lightning-charge-fill h2 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

    {{-- BAGIAN 2: CHART GARIS & BAR --}}
    <div class="row">
        {{-- Line Chart --}}
        <div class="col-xl-8 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Tren Surat (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trenSuratChart"></canvas>
                    </div>
<div class="mt-2 text-center small">
    <span class="me-2"><i class="bi bi-circle-fill text-primary"></i> Masuk Rektor</span>
    <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> Keluar Rektor</span>
    <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> Masuk BAU</span>
    <span class="me-2"><i class="bi bi-circle-fill text-dark"></i> Keluar BAU</span>
</div>
                </div>
            </div>
        </div>

{{-- Pie Chart Komposisi BAU --}}
<div class="col-xl-4 col-lg-4">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary">Komposisi Surat BAU</h6>
        </div>
        <div class="card-body">
            <div class="chart-pie pt-1 pb-1">
                <canvas id="komposisiChart"></canvas>
            </div>
            <div class="mt-2 text-center small">
                <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> Keluar Int</span>
                <span class="me-2"><i class="bi bi-circle-fill text-success"></i> Keluar Eks</span>
                <span class="me-2"><i class="bi bi-circle-fill text-primary"></i> Masuk Int</span>
                <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> Masuk Eks</span>
            </div>
        </div>
    </div>
</div>
    </div>

   {{-- BAGIAN 3: TABEL AKSI & KALENDER --}}
<div class="row">
        {{-- Tabel Aksi Cepat --}}
<div class="col-lg-8">
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
        <div class="col-lg-4">
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
    // Perbaikan: Mapping 4 variabel baru dari controller
    const dataRektorMasuk = @json($dataRektorMasuk);
    const dataRektorKeluar = @json($dataRektorKeluar);
    const dataBauMasuk    = @json($dataBauMasuk);
    const dataBauKeluar   = @json($dataBauKeluar);
    
    // Variabel lainnya tetap
    const komposisiData = @json($komposisiData);
    const calendarEvents = @json($calendarEvents);

    document.addEventListener('DOMContentLoaded', function() {
        
// 1. LINE CHART
const ctxLine = document.getElementById("trenSuratChart");
if (ctxLine) {
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: lineLabels, // Diambil dari controller
            datasets: [
                { 
                    label: "Masuk Rektor", 
                    lineTension: 0.3, 
                    backgroundColor: "rgba(78, 115, 223, 0.05)", 
                    borderColor: "#4e73df", 
                    pointBackgroundColor: "#4e73df", 
                    data: dataRektorMasuk // Variabel dari controller
                },
                { 
                    label: "Keluar Rektor", 
                    lineTension: 0.3, 
                    backgroundColor: "rgba(231, 74, 59, 0.05)", 
                    borderColor: "#e74a3b", 
                    pointBackgroundColor: "#e74a3b", 
                    data: dataRektorKeluar // Variabel dari controller
                },
                { 
                    label: "Masuk BAU", 
                    lineTension: 0.3, 
                    backgroundColor: "rgba(246, 194, 62, 0.05)", 
                    borderColor: "#f6c23e", 
                    pointBackgroundColor: "#f6c23e", 
                    data: dataBauMasuk // Variabel dari controller
                },
                { 
                    label: "Keluar BAU", 
                    lineTension: 0.3, 
                    backgroundColor: "rgba(90, 92, 105, 0.05)", 
                    borderColor: "#5a5c69", 
                    pointBackgroundColor: "#5a5c69", 
                    data: dataBauKeluar // Variabel dari controller
                }
            ],
        },
        options: { 
            maintainAspectRatio: false, 
            responsive: true, 
            plugins: { 
                legend: { 
                    display: false // Kita set false karena sudah pakai legend manual di bawah canvas
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { precision: 0 } 
                }, 
                x: { 
                    grid: { display: false } 
                } 
            } 
        }
    });
}

// 2. PIE CHART KOMPOSISI BAU
const ctxKomposisi = document.getElementById("komposisiChart");
if (ctxKomposisi) {
    new Chart(ctxKomposisi, {
        type: 'pie', // Berubah menjadi pie
        data: {
            labels: ['Keluar Internal', 'Keluar Eksternal', 'Masuk Internal', 'Masuk Eksternal'],
            datasets: [{
                data: komposisiData, // Diambil dari controller
                backgroundColor: ['#e74a3b', '#1cc88a', '#4e73df', '#f6c23e'], // Merah, Hijau, Biru, Kuning
                hoverBackgroundColor: ['#be2617', '#17a673', '#2e59d9', '#dda20a'],
                hoverBorderColor: "rgb(20, 20, 20)",
                // Efek Explode (irisan menonjol seperti di gambar)
                offset: [20, 0, 0, 0], 
                borderWidth: 2,
            }],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false // Menggunakan legend manual di bawah
                },
                tooltip: {
                    backgroundColor: "rgb(12, 10, 10)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 5,
                    yPadding: 5,
                    displayColors: false,
                    caretPadding: 10,
                }
            },
            cutout: '0%', // 0% untuk Pie penuh, jika ingin Donut ubah ke 70%
        },
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