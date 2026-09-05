@extends('layouts.app')

@section('title', 'Pembaca notifikasi')
@section('heading', 'Pembaca notifikasi')
@section('subheading', $notifikasi->judul)

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <a href="{{ route('notifikasi.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali ke notifikasi</a>
    <div class="text-secondary small">
        {{ \App\Models\Notifikasi::jenisOptions()[$notifikasi->jenis] ?? $notifikasi->jenis }}
        ·
        {{ \App\Models\Notifikasi::audienceOptions()[$notifikasi->audience] ?? $notifikasi->audience }}
    </div>
</div>

<div class="madani-card p-3 mb-3">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('notifikasi.pembaca', $notifikasi) }}">
        <div class="col-md-3">
            <label class="form-label">Penerima</label>
            <select class="form-select" name="tipe" id="filterTipe">
                <option value="semua" @selected($tipe === 'semua')>Semua ({{ $countSemua }})</option>
                <option value="guru" @selected($tipe === 'guru')>Guru ({{ $countGuru }})</option>
                <option value="siswa" @selected($tipe === 'siswa')>Siswa ({{ $countSiswa }})</option>
            </select>
        </div>
        <div class="col-md-4 js-rombel-filter" @style(['display: none' => $tipe !== 'siswa'])>
            <label class="form-label">Rombel</label>
            <select class="form-select" name="rombel_id">
                <option value="">Semua rombel</option>
                @foreach ($rombels as $rombel)
                    <option value="{{ $rombel->id }}" @selected((string) $rombelId === (string) $rombel->id)>
                        {{ $rombel->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit">Terapkan</button>
        </div>
    </form>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Identitas</th>
                    <th>Rombel</th>
                    <th>Dibaca</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reads as $read)
                    @php
                        $reader = $read->reader;
                        $isGuru = $reader instanceof \App\Models\User;
                        $isSiswa = $reader instanceof \App\Models\Siswa;
                        $nama = $isGuru
                            ? ($reader->gtk?->nama ?? $reader->name ?? '—')
                            : ($isSiswa ? $reader->nama : '—');
                        $identitas = $isGuru
                            ? ($reader->gtk?->nip ?: $reader->username)
                            : ($isSiswa ? $reader->nisn : null);
                        $rombelLabel = $isSiswa
                            ? ($reader->rombels->first()?->label() ?? '—')
                            : '—';
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $nama }}</td>
                        <td class="text-secondary small">
                            @if ($isGuru)
                                Guru
                            @elseif ($isSiswa)
                                Siswa
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-secondary small">{{ $identitas ?: '—' }}</td>
                        <td class="text-secondary small">{{ $rombelLabel }}</td>
                        <td class="text-nowrap text-secondary small">
                            {{ $read->read_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-secondary p-3">Belum ada yang membaca notifikasi ini{{ $tipe !== 'semua' ? ' pada filter ini' : '' }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($reads->hasPages())
    <div class="mt-3">
        {{ $reads->links() }}
    </div>
@endif

<script>
(() => {
    const tipe = document.getElementById('filterTipe');
    const rombel = document.querySelector('.js-rombel-filter');
    if (!tipe || !rombel) return;
    const sync = () => {
        rombel.style.display = tipe.value === 'siswa' ? '' : 'none';
    };
    tipe.addEventListener('change', sync);
})();
</script>
@endsection
