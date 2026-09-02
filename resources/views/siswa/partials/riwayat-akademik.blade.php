@php
    $masuk = $siswa->dataMasukAkademik();
    $riwayatRombel = $siswa->rombels
        ->sortByDesc(fn ($rombel) => $rombel->tahunAjaran?->tanggal_mulai?->format('Ymd') ?? '0')
        ->values();
    $filterNilai = (string) request('nilai', '1');
    if (! array_key_exists($filterNilai, $emis['semester_nilai'])) {
        $filterNilai = '1';
    }
@endphp

<div class="madani-card p-4 mb-3">
    <div class="stat-label mb-3">Status peserta didik</div>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Status peserta didik</label>
            <input class="form-control bg-light" value="{{ $masuk['status'] }}" readonly>
        </div>
        @if ($masuk['status'] === 'Pindahan')
            <div class="col-md-3">
                <label class="form-label">Alasan</label>
                <input class="form-control bg-light" value="{{ $masuk['alasan'] ?: '—' }}" readonly>
            </div>
        @endif
        <div class="col-md-{{ $masuk['status'] === 'Pindahan' ? '3' : '5' }}">
            <label class="form-label">Nama sekolah asal</label>
            <input class="form-control bg-light" value="{{ $masuk['nama_sekolah_asal'] ?: '—' }}" readonly>
        </div>
        <div class="col-md-{{ $masuk['status'] === 'Pindahan' ? '3' : '4' }}">
            <label class="form-label">NPSN sekolah asal</label>
            <input class="form-control bg-light" value="{{ $masuk['npsn_asal'] ?: '—' }}" readonly>
        </div>
        <div class="col-12">
            <div class="form-text">Data diisi otomatis dari Rekam didik dan Mutasi.</div>
        </div>
    </div>
</div>

<div class="madani-card p-0 mb-3">
    <div class="p-4 pb-0">
        <div class="stat-label mb-3">Riwayat rombel</div>
    </div>
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Tahun ajaran</th>
                <th>Tingkat</th>
                <th>Rombel</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayatRombel as $rombel)
                <tr>
                    <td>{{ $rombel->tahunAjaran?->nama ?: '—' }}</td>
                    <td>{{ $rombel->tingkat ?: '—' }}</td>
                    <td>{{ $rombel->nama }}{{ $rombel->program ? ' · '.$rombel->program : '' }}</td>
                    <td>{{ $rombel->pivot->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-secondary p-3">Belum ada riwayat rombel.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="madani-card p-4">
    <div class="stat-label mb-3">Data nilai</div>
    <form method="GET" class="row g-3 align-items-end mb-3">
        <input type="hidden" name="tab" value="aktivitas">
        <div class="col-md-4">
            <label class="form-label">Semester / UAM</label>
            <select class="form-select" name="nilai" onchange="this.form.submit()">
                @foreach ($emis['semester_nilai'] as $key => $label)
                    <option value="{{ $key }}" @selected($filterNilai === (string) $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Mata pelajaran</th>
                    <th>Nilai</th>
                    <th>Predikat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" class="text-secondary p-3">
                        Data nilai {{ $emis['semester_nilai'][$filterNilai] }} akan diambil dari Tracer.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
