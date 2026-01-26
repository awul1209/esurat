@extends('layouts.app')

@push('styles')
<!-- <link href="https://unpkg.com/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet"> -->
<style>
    .card-dashboard {
        transition: all 0.3s ease-in-out;
        border: none;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        background-color: #ffffff;
    }

    .card-dashboard:hover {
        transform: translateY(-7px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15) !important;
    }

    .icon-shape {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .card-dashboard:hover .icon-shape {
        transform: scale(1.1);
    }

    .bg-gradient-pribadi { background: linear-gradient(35deg, #5e72e4, #825ee4); }
    .bg-gradient-umum { background: linear-gradient(35deg, #11cdef, #1171ef); }
    .bg-gradient-total-masuk { background: linear-gradient(35deg, #2dce89, #2dcecc); }
    .bg-gradient-total-keluar { background: linear-gradient(35deg, #f5365c, #f56036); }

    .timeline-container::-webkit-scrollbar { width: 5px; }
    .timeline-container::-webkit-scrollbar-thumb {
        background: #e3e6f0;
        border-radius: 10px;
    }
    
    .hover-shadow {
        transition: all 0.2s ease-in-out;
    }
    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    <div class="row">
        {{-- Card 1: Surat Umum --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-dashboard shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted mb-1 small fw-bold">Surat Umum (Satker)</h6>
                            <span class="h3 font-weight-bold mb-0">{{ $suratUmumCount }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon-shape bg-gradient-umum text-white shadow">
                                <i class="bi bi-megaphone-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-sm">
                        <span class="text-primary mr-2"><i class="bi bi-info-circle"></i></span>
                        <span class="text-nowrap text-muted">Informasi untuk semua</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Card 2: Surat Pribadi --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-dashboard shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted mb-1 small fw-bold">Surat Pribadi & Langsung</h6>
                            <span class="h3 font-weight-bold mb-0">{{ $suratPribadiCount }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon-shape bg-gradient-pribadi text-white shadow">
                                <i class="bi bi-person-badge-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-sm">
                        <span class="text-success mr-2"><i class="bi bi-shield-lock"></i></span>
                        <span class="text-nowrap text-muted">Khusus/Langsung untuk Anda</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Card 3: Total Masuk --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-dashboard shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted mb-1 small fw-bold">Total Surat Masuk</h6>
                            <span class="h3 font-weight-bold mb-0">{{ $totalSuratMasuk }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon-shape bg-gradient-total-masuk text-white shadow">
                                <i class="bi bi-envelope-paper-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-sm">
                        <span class="text-success mr-2"><i class="bi bi-arrow-down-left-circle"></i></span>
                        <span class="text-nowrap text-muted">Semua kategori</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Card 4: Total Keluar --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-dashboard shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted mb-1 small fw-bold">Total Surat Keluar</h6>
                            <span class="h3 font-weight-bold mb-0">{{ $totalSuratKeluar }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon-shape bg-gradient-total-keluar text-white shadow">
                                <i class="bi bi-send-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-sm">
                        <span class="text-danger mr-2"><i class="bi bi-arrow-up-right-circle"></i></span>
                        <span class="text-nowrap text-muted">Arsip keluar Anda</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- LOG AKTIVITAS --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 mb-4 card-dashboard">
                <div class="card-header py-3 bg-white border-0 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i> Log Aktivitas Surat Terbaru
                    </h6>
                </div>
                <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                    <div class="timeline-container px-3">
                        @forelse($activityLogs as $log)
                            <a href="{{ $log['url'] }}" class="text-decoration-none">
                                <div class="mb-4 p-3 rounded-3 border-start border-4 border-{{ $log['color'] }} bg-white shadow-sm hover-shadow">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-{{ $log['color'] }} rounded-pill small">
                                            {{ $log['tipe'] }}
                                        </span>
                                        <span class="text-muted small fw-bold text-truncate ms-2 flex-grow-1">
                                            {{ \Illuminate\Support\Str::limit($log['judul'], 40) }}
                                        </span>
                                        <i class="bi bi-chevron-right text-muted small ms-2"></i>
                                    </div>
                                    
                                    <div class="ms-2 border-start border-2 ps-4 position-relative">
                                        @foreach($log['history'] as $h)
                                            <div class="mb-2 position-relative">
                                                <div class="position-absolute bg-{{ $log['color'] }}" 
                                                     style="width: 10px; height: 10px; border-radius: 50%; left: -30px; top: 5px; border: 2px solid white;">
                                                </div>
                                                <div class="small fw-bold text-dark">{{ $h['status'] }}</div>
                                                <div class="text-muted opacity-75" style="font-size: 0.7rem;">
                                                    {{ $h['waktu'] }} WIB • {{ $h['aktor'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-5">
                                <i class="bi bi-envelope-x fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Belum ada aktivitas surat.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- KALENDER --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4 card-dashboard">
                <div class="card-header py-3 bg-white border-0">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-calendar3 me-2"></i> Kalender Agenda Surat
                    </h6>
                </div>
                <div class="card-body">
                    <div id="calendar" style="font-size: 0.7rem;"></div>
                    <div class="mt-3 small d-flex justify-content-center">
                        <div class="me-3"><span class="badge" style="background: #5e72e4;">&nbsp;</span> Pribadi</div>
                        <div><span class="badge" style="background: #11cdef;">&nbsp;</span> Umum</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetailAgenda" tabindex="-1" aria-labelledby="modalDetailAgendaLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden shadow-lg">
            <div class="modal-header bg-gradient-pribadi p-4 border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-20 p-2 rounded-3 me-3">
                        <i class="bi bi-calendar-event text-white fs-4"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-white">Detail Agenda</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="badge id-badge mb-3" id="detailTipe"></div>
                <h4 class="fw-bold text-dark mb-4" id="detailPerihal"></h4>
                
                <div class="row g-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-hash text-primary me-2 fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Nomor Surat</small>
                                <span id="detailNomor" class="fw-bold text-dark"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-send text-primary me-2 fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Pengirim</small>
                                <span id="detailPengirim" class="fw-bold text-dark"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-light rounded-3">
                            <i class="bi bi-calendar-check text-success me-3 fs-4"></i>
                            <div>
                                <small class="text-muted d-block">Tanggal Agenda</small>
                                <span id="detailTanggal" class="fw-bold text-dark fs-5"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3 border-0">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnLihatSurat" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-file-earmark-text me-2"></i>Buka Surat
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    var eventsData = {!! json_encode($events) !!};

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { 
            left: 'prev', 
            center: 'title', 
            right: 'next' 
        },
        // JANGAN gunakan themeSystem: 'bootstrap5' agar tidak bentrok
        events: eventsData,
        locale: 'id',
        height: '450px',
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var props = info.event.extendedProps;
            
            // 1. Isi Data ke Modal
            document.getElementById('detailPerihal').innerText = info.event.title;
            document.getElementById('detailNomor').innerText = props.nomor || '-';
            document.getElementById('detailPengirim').innerText = props.pengirim || '-';
            document.getElementById('detailTanggal').innerText = props.tgl_format || '-';
            
            const badgeTipe = document.getElementById('detailTipe');
            if(badgeTipe) {
                badgeTipe.innerText = props.tipe;
                badgeTipe.className = 'badge rounded-pill px-3 py-2 ' + 
                                    (props.tipe === 'Pribadi' ? 'bg-primary' : 'bg-info');
            }
            
            const btnLihat = document.getElementById('btnLihatSurat');
            if(btnLihat) btnLihat.href = props.url_view || '#';

            // 2. TRICK: Tampilkan Modal tanpa panggil "new bootstrap.Modal"
            // Kita gunakan cara manual memicu trigger klik pada tombol hidden atau gunakan jQuery jika tersedia
            var modalDetail = document.getElementById('modalDetailAgenda');
            if (window.bootstrap && window.bootstrap.Modal) {
                // Jika library tersedia di global window
                var myModal = bootstrap.Modal.getOrCreateInstance(modalDetail);
                myModal.show();
            } else {
                // Cara paling kasar jika library benar-benar terisolasi: 
                // Kita buat tombol dummy dan kita klik secara otomatis
                let btnTrigger = document.createElement('button');
                btnTrigger.setAttribute('data-bs-toggle', 'modal');
                btnTrigger.setAttribute('data-bs-target', '#modalDetailAgenda');
                btnTrigger.style.display = 'none';
                document.body.appendChild(btnTrigger);
                btnTrigger.click();
                document.body.removeChild(btnTrigger);
            }
        }
    });
    
    calendar.render();
});
</script>

@endpush