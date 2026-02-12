@extends('layouts.app')

@push('styles')
<style>
    #editor-wrapper { 
        position: relative; 
        background-color: #525659; 
        padding: 0; 
        height: calc(100vh - 60px); 
        overflow: hidden; 
        display: flex; 
    }
    
    #workspace { 
        flex-grow: 1; 
        overflow: auto; 
        position: relative; 
        background: #3c3f41;
        display: block;
        padding: 40px 0;
        scrollbar-width: thin;
    }
    
    #canvas-container { 
        position: relative; 
        margin: 0 auto;
        box-shadow: 0 0 30px rgba(0,0,0,0.5); 
        background-color: white; 
        /* Ukuran Canvas Dinamis - Mendukung F4 & A4 */
        width: {{ isset($surat->ukuran_kertas) && $surat->ukuran_kertas == 'F4' ? '812px' : '794px' }}; 
        min-height: {{ isset($surat->ukuran_kertas) && $surat->ukuran_kertas == 'F4' ? '1247px' : '1123px' }};
    }

    #pdf-canvas { display: block; width: 100%; }
    
    .control-panel { 
        width: 340px; 
        background: white; 
        box-shadow: -5px 0 15px rgba(0,0,0,0.2); 
        padding: 20px; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        display: flex; 
        flex-direction: column; 
        z-index: 1000;
        position: relative;
    }
    
    .control-panel.collapsed { 
        width: 0; 
        padding: 0; 
        margin-left: 0; 
        opacity: 0; 
        overflow: hidden; 
        pointer-events: none;
    }
    
    #toggle-sidebar {
        position: absolute; 
        top: 20px; 
        right: 360px; 
        z-index: 1100;
        width: 40px; 
        height: 40px; 
        border-radius: 8px; 
        border: none;
        background: #0d6efd; 
        color: white; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .control-panel.collapsed ~ #toggle-sidebar { 
        right: 20px; 
    }

    .draggable-element {
        position: absolute; 
        top: 0; left: 0;
        padding: 0;
        background: transparent !important; 
        border: 1px dashed #0d6efd; 
        color: #0d6efd; 
        cursor: move; 
        display: none; 
        flex-direction: column; 
        align-items: center; 
        z-index: 100;
        box-shadow: none; 
        border-radius: 4px;
        touch-action: none;
    }
    
    .draggable-element img {
        background: transparent;
        mix-blend-mode: multiply; /* Transparansi background barcode */
    }

    .barcode-auto { 
        border-color: #ffc107; 
        color: #856404; 
        background: rgba(255, 255, 255, 0.95);
    }

    .element-preview { 
        border: 1px solid #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        background: #f8f9fa; 
        margin-bottom: 15px; 
    }
</style>
@endpush

@section('content')
<div id="editor-wrapper">
    <div id="workspace">
        <div class="d-flex justify-content-between align-items-center mb-2 px-3 text-white">
            <div class="btn-group shadow-sm">
                <button type="button" class="btn btn-dark btn-sm" id="prev-page">
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button type="button" class="btn btn-light btn-sm fw-bold" disabled>
                    Halaman <span id="page-num">1</span> dari <span id="page-count">-</span>
                </button>
                <button type="button" class="btn btn-dark btn-sm" id="next-page">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-info me-2">Eksternal</span>
                <span class="badge bg-warning text-dark small">Tentukan posisi Barcode TTD & Keabsahan</span>
            </div>
        </div>
        <div id="canvas-container" style="position: relative; margin: 0 auto; overflow: hidden; border: 1px solid #ccc;">
            <canvas id="pdf-canvas"></canvas>
            
            {{-- BARCODE TTD PIMPINAN --}}
            <div id="el-ttd" class="draggable-element" data-x="450" data-y="600" style="width: 95px;">
                <img src="https://placehold.co/100x100/ffffff/000000.png?text=QR+TTD" style="width: 100%;">
                <span class="badge bg-primary w-100" style="font-size: 8px; border-radius: 0; opacity: 0.8;">TTD DIGITAL</span>
            </div>

            {{-- BARCODE KEABSAHAN --}}
            <div id="el-qr" class="draggable-element" data-x="600" data-y="900" style="width: 95px;">
                <img src="https://placehold.co/100x100/ffffff/000000.png?text=QR+LEGAL" style="width: 100%;">
                <span class="badge bg-warning text-dark w-100" style="font-size: 8px; border-radius: 0; opacity: 0.8;">KEABSAHAN</span>
            </div>
        </div>
    </div>

    <div class="control-panel shadow" id="sidebar">
        <h6 class="fw-bold mb-1"><i class="bi bi-gear-fill me-2 text-primary"></i>Layout Eksternal</h6>
        <p class="text-muted" style="font-size: 11px;">Nomor: <strong>{{ $surat->nomor_surat }}</strong></p>
        <hr class="my-2">

        <div class="overflow-auto pe-1" style="flex-grow: 1;">
            <div class="element-preview">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show-ttd" checked>
                    <label class="form-check-label fw-bold small">Barcode Tanda Tangan</label>
                </div>
                <div class="mt-1 small text-muted" style="font-size: 10px;">Posisi QR Identitas Penandatangan</div>
            </div>

            <div class="element-preview border-primary bg-light">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="show-qr" checked>
                    <label class="form-check-label fw-bold small">Barcode Keabsahan</label>
                </div>
                <label class="small text-muted mb-1" style="font-size: 10px;">Metode Penempatan:</label>
                <select id="qr-mode" class="form-select form-select-sm">
                    <option value="auto">Auto (Kanan Bawah)</option>
                    <option value="manual">Manual (Bebas Drag)</option>
                </select>
            </div>
            
            <div class="alert alert-info py-2" style="font-size: 10px;">
                <i class="bi bi-info-circle me-1"></i> Tembusan ke <b>BAU</b> akan dikirim otomatis setelah divalidasi pimpinan.
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary w-100 fw-bold shadow-sm py-2" onclick="finishSigning()">
                <i class="bi bi-send-check-fill me-2"></i>SIMPAN & KIRIM
            </button>
        </div>
    </div>

    <button id="toggle-sidebar" type="button"><i class="bi bi-layout-sidebar-reverse"></i></button>
</div>

{{-- Route disesuaikan untuk proses eksternal --}}
<form id="final-form" action="{{ route('satker.surat-keluar.eksternal.process', $surat->id) }}" method="POST">
    @csrf
    <input type="hidden" name="positions" id="positions-input">
</form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

<script>
$(document).ready(function() {
    const pdfUrl = "{{ asset('storage/' . $surat->file_surat) }}";
    let canvas = document.getElementById('pdf-canvas'), ctx = canvas.getContext('2d');
    let pdfDoc = null, pageNum = 1;

    let pageStates = {};

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    
    pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
        pdfDoc = pdf;
        $('#page-count').text(pdf.numPages);
        
        for(let i = 1; i <= pdf.numPages; i++) {
            pageStates[i] = {
                ttd: { x: 450, y: 600, show: true },
                qr: { x: 600, y: 900, show: true, mode: 'auto' }
            };
        }

        pageNum = 1; 
        renderPage(pageNum);
    });

    function renderPage(num) {
        pdfDoc.getPage(num).then(page => {
            const containerWidth = $('#canvas-container').width();
            const unscaledViewport = page.getViewport({ scale: 1 });
            const dynamicScale = containerWidth / unscaledViewport.width;

            const viewport = page.getViewport({ scale: dynamicScale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            $('#canvas-container').css('height', viewport.height + 'px');

            page.render({ canvasContext: ctx, viewport: viewport }).promise.then(() => {
                applyPageState(num);
                $('#page-num').text(num);
            });
        });
    }

    function saveCurrentState() {
        if (!pageStates[pageNum]) return;
        
        pageStates[pageNum].ttd = {
            x: $('#el-ttd').attr('data-x'),
            y: $('#el-ttd').attr('data-y'),
            show: $('#show-ttd').is(':checked')
        };
        pageStates[pageNum].qr = {
            x: $('#el-qr').attr('data-x'),
            y: $('#el-qr').attr('data-y'),
            show: $('#show-qr').is(':checked'),
            mode: $('#qr-mode').val()
        };
    }

    function applyPageState(num) {
        const state = pageStates[num];
        
        $('#el-ttd').attr('data-x', state.ttd.x).attr('data-y', state.ttd.y)
                  .css('transform', `translate(${state.ttd.x}px, ${state.ttd.y}px)`);
        state.ttd.show ? $('#el-ttd').show() : $('#el-ttd').hide();

        $('#el-qr').attr('data-x', state.qr.x).attr('data-y', state.qr.y)
                  .css('transform', `translate(${state.qr.x}px, ${state.qr.y}px)`);
        state.qr.show ? $('#el-qr').show() : $('#el-qr').hide();

        if (!$('#sidebar').hasClass('collapsed')) {
            $('#show-ttd').prop('checked', state.ttd.show);
            $('#show-qr').prop('checked', state.qr.show);
            $('#qr-mode').val(state.qr.mode);
        }

        if (state.qr.show && state.qr.mode === 'auto') {
            updateQrAuto();
        }
    }

    // Navigasi & Interaction Logic (Sama dengan Internal)
    $('#prev-page').on('click', function() { if (pageNum <= 1) return; saveCurrentState(); pageNum--; renderPage(pageNum); });
    $('#next-page').on('click', function() { if (pageNum >= pdfDoc.numPages) return; saveCurrentState(); pageNum++; renderPage(pageNum); });

    interact('.draggable-element:not(.barcode-auto)').draggable({
        listeners: {
            move(event) {
                let target = event.target;
                let x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                let y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            }
        },
        modifiers: [interact.modifiers.restrictRect({ restriction: 'parent' })]
    });

    function updateQrAuto() {
        const qrEl = $('#el-qr');
        qrEl.addClass('barcode-auto');
        let cw = $('#canvas-container').width();
        let ch = $('#canvas-container').height();
        let nx = cw - 150; 
        let ny = ch - 150; 
        qrEl.css('transform', `translate(${nx}px, ${ny}px)`);
        qrEl.attr('data-x', nx).attr('data-y', ny);
    }

    $('#qr-mode').on('change', function() {
        if ($(this).val() === 'auto') updateQrAuto();
        else $('#el-qr').removeClass('barcode-auto');
    });

    $('#toggle-sidebar').on('click', function() {
        saveCurrentState();
        $('#sidebar').toggleClass('collapsed');
        $(this).find('i').toggleClass('bi-layout-sidebar-reverse bi-layout-sidebar');
        setTimeout(() => { renderPage(pageNum); }, 300);
    });

    $('#show-ttd').on('change', function() { $('#el-ttd').toggle(this.checked); });
    $('#show-qr').on('change', function() { 
        $('#el-qr').toggle(this.checked);
        if(this.checked && $('#qr-mode').val() === 'auto') updateQrAuto();
    });

    window.finishSigning = function() {
        saveCurrentState(); 
        let config = {
            width: canvas.width, 
            height: canvas.height,
            pages: pageStates 
        };
        $('#positions-input').val(JSON.stringify(config));
        $('#final-form').submit();
    };
});
</script>
@endpush