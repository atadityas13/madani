@php
    $jenis = old('jenis', $item->jenis ?? 'notifikasi');
    $audience = old('audience', $item->audience ?? 'semua_guru');
    $usePeriode = (string) old('use_periode', ($item->use_periode ?? false) ? '1' : '0') === '1';
    $soundKey = old('sound_key', $item->sound_key ?? 'default');
    $priority = old('priority', $item->priority ?? 'normal');
    $selectedIds = collect(old('audience_ids', $item->audience_ids ?? []))->map(fn ($v) => (string) $v)->all();
    $mediaImages = $mediaImages ?? collect();
    $mediaAudios = $mediaAudios ?? collect();
@endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Jenis</label>
                <select class="form-select js-jenis" name="jenis" required>
                    @foreach (\App\Models\Notifikasi::jenisOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($jenis === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Penerima</label>
                <select class="form-select js-audience" name="audience" required>
                    @foreach (\App\Models\Notifikasi::audienceOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($audience === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Judul</label>
                <input class="form-control js-judul" type="text" name="judul" maxlength="200" required
                    value="{{ old('judul', $item->judul ?? '') }}">
                <div class="mt-1 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary js-ph" data-target=".js-judul"
                        data-ph="{{ '{'.'{'.'nama'.'}'.'}' }}">{{ '{'.'{'.'nama'.'}'.'}' }}</button>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Isi</label>
                <textarea class="form-control js-isi" name="isi" rows="5" maxlength="5000" required>{{ old('isi', $item->isi ?? '') }}</textarea>
                <div class="mt-1 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary js-ph" data-target=".js-isi"
                        data-ph="{{ '{'.'{'.'nama'.'}'.'}' }}">{{ '{'.'{'.'nama'.'}'.'}' }}</button>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gambar (upload)</label>
                <input class="form-control js-gambar-file" type="file" name="gambar" accept="image/*">
                @if (! empty($item?->gambar_url))
                    <div class="form-text">Saat ini: <a href="{{ $item->gambar_url }}" target="_blank" rel="noopener">lihat</a></div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Gambar dari pustaka</label>
                <select class="form-select js-gambar-media" name="gambar_media_id">
                    <option value="">—</option>
                    @foreach ($mediaImages as $media)
                        <option value="{{ $media->id }}" data-url="{{ $media->url }}">{{ $media->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Audio (upload)</label>
                <input class="form-control" type="file" name="audio" accept="audio/*">
                @if (! empty($item?->audio_url))
                    <div class="form-text">Saat ini: <a href="{{ $item->audio_url }}" target="_blank" rel="noopener">dengar</a></div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Audio dari pustaka</label>
                <select class="form-select js-audio-media" name="audio_media_id">
                    <option value="">—</option>
                    @foreach ($mediaAudios as $media)
                        <option value="{{ $media->id }}" data-url="{{ $media->url }}">{{ $media->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tautan eksternal (opsional)</label>
                <input class="form-control" type="url" name="link" maxlength="500"
                    value="{{ old('link', $item->link ?? '') }}" placeholder="https://...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Channel suara</label>
                <select class="form-select" name="sound_key">
                    @foreach (\App\Models\Notifikasi::soundOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($soundKey === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">Alarm = bunyi lewat stream alarm (bisa terdengar saat HP hening), seperti CBTApp.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Prioritas</label>
                <select class="form-select" name="priority">
                    @foreach (\App\Models\Notifikasi::priorityOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($priority === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jadwal kirim</label>
                <input class="form-control" type="datetime-local" name="scheduled_at"
                    value="{{ old('scheduled_at', $item?->scheduled_at?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
                <div class="form-text">Kosongkan = segera.</div>
            </div>
            <div class="col-12 js-audience-targets" data-audience="gtk" @style(['display: none' => $audience !== 'gtk'])>
                <label class="form-label">Pilih GTK</label>
                <select class="form-select" name="audience_ids[]" multiple size="6">
                    @foreach ($gtks as $gtk)
                        <option value="{{ $gtk->id }}" @selected(in_array((string) $gtk->id, $selectedIds, true))>
                            {{ $gtk->nama }} @if($gtk->nip) ({{ $gtk->nip }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 js-audience-targets" data-audience="siswa" @style(['display: none' => $audience !== 'siswa'])>
                <label class="form-label">Pilih siswa</label>
                <select class="form-select" name="audience_ids[]" multiple size="6">
                    @foreach ($siswas as $siswa)
                        <option value="{{ $siswa->id }}" @selected(in_array((string) $siswa->id, $selectedIds, true))>
                            {{ $siswa->nama }} @if($siswa->nisn) ({{ $siswa->nisn }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 js-audience-targets" data-audience="rombel" @style(['display: none' => $audience !== 'rombel'])>
                <label class="form-label">Pilih rombel</label>
                <select class="form-select" name="audience_ids[]" multiple size="6">
                    @foreach ($rombels as $rombel)
                        <option value="{{ $rombel->id }}" @selected(in_array((string) $rombel->id, $selectedIds, true))>
                            {{ $rombel->tingkat }} {{ $rombel->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 js-periode-wrap" @style(['display: none' => $jenis !== 'pengingat'])>
                <div class="form-check mb-2">
                    <input class="form-check-input js-use-periode" type="checkbox" name="use_periode" value="1" id="usePeriode{{ $item->id ?? 'new' }}"
                        @checked($usePeriode)>
                    <label class="form-check-label" for="usePeriode{{ $item->id ?? 'new' }}">
                        Gunakan periode waktu (tidak bisa ditutup + countdown)
                    </label>
                </div>
                <div class="row g-3 js-periode-dates" @style(['display: none' => ! $usePeriode])>
                    <div class="col-md-6">
                        <label class="form-label">Mulai</label>
                        <input class="form-control" type="datetime-local" name="starts_at"
                            value="{{ old('starts_at', $item?->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Selesai</label>
                        <input class="form-control" type="datetime-local" name="ends_at"
                            value="{{ old('ends_at', $item?->ends_at?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="border rounded-3 p-3 bg-dark text-white sticky-top" style="top: 1rem;">
            <div class="small text-white-50 mb-2">Preview (contoh: Budi Santoso)</div>
            <div class="fw-semibold text-success small mb-1">Ta'lim</div>
            <div class="fw-bold js-preview-title mb-1">Judul</div>
            <div class="small text-white-50 js-preview-body mb-2">Isi notifikasi</div>
            <img class="img-fluid rounded js-preview-img d-none mb-2" alt="" style="max-height: 140px; object-fit: cover; width: 100%;">
            <div class="small text-info js-preview-audio d-none"><i class="bi bi-music-note-beamed"></i> Ada audio</div>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.currentScript.previousElementSibling.parentElement;
    const form = root.closest('form');
    if (!form || form.dataset.notifBound) return;
    form.dataset.notifBound = '1';

    const syncAudience = () => {
        const select = form.querySelector('.js-audience');
        form.querySelectorAll('.js-audience-targets').forEach((box) => {
            const match = box.dataset.audience === select.value;
            box.style.display = match ? '' : 'none';
            box.querySelectorAll('select').forEach((el) => { el.disabled = !match; });
        });
    };
    const syncJenis = () => {
        const jenis = form.querySelector('.js-jenis')?.value;
        const wrap = form.querySelector('.js-periode-wrap');
        if (wrap) wrap.style.display = jenis === 'pengingat' ? '' : 'none';
        syncPeriode();
    };
    const syncPeriode = () => {
        const jenis = form.querySelector('.js-jenis')?.value;
        const checked = form.querySelector('.js-use-periode')?.checked;
        const dates = form.querySelector('.js-periode-dates');
        if (dates) dates.style.display = (jenis === 'pengingat' && checked) ? '' : 'none';
    };
    const renderPreview = (tpl) => {
        const o = '{' + '{';
        const c = '}' + '}';
        return tpl.replaceAll(o + 'nama' + c, 'Budi Santoso');
    };
    const syncPreview = () => {
        form.querySelector('.js-preview-title').textContent = renderPreview(form.querySelector('.js-judul')?.value || 'Judul');
        form.querySelector('.js-preview-body').textContent = renderPreview(form.querySelector('.js-isi')?.value || 'Isi notifikasi');
        const img = form.querySelector('.js-preview-img');
        const file = form.querySelector('.js-gambar-file')?.files?.[0];
        const mediaUrl = form.querySelector('.js-gambar-media')?.selectedOptions?.[0]?.dataset?.url;
        if (file) {
            img.src = URL.createObjectURL(file);
            img.classList.remove('d-none');
        } else if (mediaUrl) {
            img.src = mediaUrl;
            img.classList.remove('d-none');
        } else if (img.dataset.existing) {
            img.src = img.dataset.existing;
            img.classList.remove('d-none');
        } else {
            img.classList.add('d-none');
        }
        const audioOpt = form.querySelector('.js-audio-media')?.selectedOptions?.[0]?.dataset?.url;
        const audioEl = form.querySelector('.js-preview-audio');
        if (audioOpt || form.querySelector('input[name=audio]')?.files?.length) {
            audioEl.classList.remove('d-none');
        } else {
            audioEl.classList.add('d-none');
        }
    };

    form.querySelectorAll('.js-ph').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = form.querySelector(btn.dataset.target);
            if (!el) return;
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            el.value = el.value.slice(0, start) + btn.dataset.ph + el.value.slice(end);
            el.focus();
            syncPreview();
        });
    });

    form.querySelector('.js-audience')?.addEventListener('change', syncAudience);
    form.querySelector('.js-jenis')?.addEventListener('change', syncJenis);
    form.querySelector('.js-use-periode')?.addEventListener('change', syncPeriode);
    form.querySelector('.js-judul')?.addEventListener('input', syncPreview);
    form.querySelector('.js-isi')?.addEventListener('input', syncPreview);
    form.querySelector('.js-gambar-file')?.addEventListener('change', syncPreview);
    form.querySelector('.js-gambar-media')?.addEventListener('change', syncPreview);
    form.querySelector('.js-audio-media')?.addEventListener('change', syncPreview);
    form.querySelector('input[name=audio]')?.addEventListener('change', syncPreview);

    @if (! empty($item?->gambar_url))
    form.querySelector('.js-preview-img').dataset.existing = @json($item->gambar_url);
    @endif

    syncAudience();
    syncJenis();
    syncPreview();
})();
</script>
