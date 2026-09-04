@php
    $jenis = old('jenis', $item->jenis ?? 'pengumuman');
    $audience = old('audience', $item->audience ?? 'semua_guru');
    $selectedIds = collect(old('audience_ids', $item->audience_ids ?? []))->map(fn ($v) => (string) $v)->all();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Jenis</label>
        <select class="form-select" name="jenis" required>
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
        <input class="form-control" type="text" name="judul" maxlength="200" required
            value="{{ old('judul', $item->judul ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Isi</label>
        <textarea class="form-control" name="isi" rows="5" maxlength="5000" required>{{ old('isi', $item->isi ?? '') }}</textarea>
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
    <div class="col-md-6">
        <label class="form-label">Mulai (periode)</label>
        <input class="form-control" type="datetime-local" name="starts_at"
            value="{{ old('starts_at', optional($item->starts_at ?? null)?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Selesai (periode)</label>
        <input class="form-control" type="datetime-local" name="ends_at"
            value="{{ old('ends_at', optional($item->ends_at ?? null)?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
    </div>
</div>

<script>
document.querySelectorAll('.js-audience').forEach((select) => {
    const sync = () => {
        const form = select.closest('form');
        form.querySelectorAll('.js-audience-targets').forEach((box) => {
            const match = box.dataset.audience === select.value;
            box.style.display = match ? '' : 'none';
            box.querySelectorAll('select').forEach((el) => { el.disabled = !match; });
        });
    };
    select.addEventListener('change', sync);
    sync();
});
</script>
