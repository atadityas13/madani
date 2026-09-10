@extends('layouts.talim-webview')

@section('title', 'Preview · '.$labelJenis)
@section('heading', $labelJenis)
@section('subheading', $periodeLabel)

@section('content')
<style>
    .talim-pdf-viewer{background:#e2e8f0;border-radius:.75rem;min-height:60vh;padding:.75rem}
    .talim-pdf-viewer__pages{display:flex;flex-direction:column;gap:.75rem;align-items:center}
    .talim-pdf-viewer__pages canvas{max-width:100%;height:auto;background:#fff;box-shadow:0 2px 10px rgba(15,23,42,.12)}
    .talim-pdf-status{text-align:center;color:#64748b;padding:2rem .5rem;font-size:.9rem}
</style>

<div class="talim-toolbar mb-3">
    <a class="talim-back" href="{{ $kembaliUrl }}">← Kembali</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ $downloadUrl }}">Unduh</a>
</div>

<div class="talim-pdf-viewer">
    <div class="talim-pdf-status" id="pdfStatus">Memuat PDF…</div>
    <div class="talim-pdf-viewer__pages" id="pdfPages"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    (() => {
        const streamUrl = @json($streamUrl);
        const statusEl = document.getElementById('pdfStatus');
        const pagesEl = document.getElementById('pdfPages');

        if (! window['pdfjsLib']) {
            statusEl.textContent = 'Gagal memuat viewer PDF.';
            return;
        }

        window['pdfjsLib'].GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        fetch(streamUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/pdf' } })
            .then((res) => {
                if (! res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.arrayBuffer();
            })
            .then((data) => window['pdfjsLib'].getDocument({ data }).promise)
            .then(async (pdf) => {
                statusEl.textContent = '';
                statusEl.hidden = true;
                const scale = Math.min(1.35, (window.innerWidth - 48) / 600);

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const viewport = page.getViewport({ scale: scale > 0.6 ? scale : 1 });
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    pagesEl.appendChild(canvas);
                    await page.render({ canvasContext: ctx, viewport }).promise;
                }
            })
            .catch((err) => {
                statusEl.hidden = false;
                statusEl.textContent = 'PDF tidak bisa ditampilkan. Coba Unduh. (' + (err && err.message ? err.message : 'error') + ')';
            });
    })();
</script>
@endsection
