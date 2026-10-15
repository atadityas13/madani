@extends($layout)

@section('title', 'Surat izin/sakit')
@section('heading', 'Surat izin/sakit')
@section('subheading', $rombel_wali ? $rombel_wali->label().($tahun_label ? ' · '.$tahun_label : '') : 'Cetak surat izin dan sakit siswa')

@section('content')
@if ($webview && $kembali_url)
    <div class="talim-toolbar">
        <a class="talim-back" href="{{ $kembali_url }}">← Kembali</a>
    </div>
@endif

<form method="GET" class="{{ $webview ? 'mb-3' : 'siswa-index-toolbar mb-3' }}" action="{{ url()->current() }}">
    <div class="{{ $webview ? 'd-flex align-items-center gap-2 flex-wrap mb-2' : 'siswa-index-toolbar__filters align-items-center' }}">
        <input class="form-control" type="date" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()" aria-label="Tanggal" style="max-width: 12rem;">
        <select class="form-select" name="jenis" onchange="this.form.submit()" aria-label="Jenis" style="max-width: 9rem;">
            <option value="">Izin &amp; sakit</option>
            <option value="izin" @selected($jenis === 'izin')>Izin</option>
            <option value="sakit" @selected($jenis === 'sakit')>Sakit</option>
        </select>
        @if ($admin)
            <select class="form-select" name="rombel_id" onchange="this.form.submit()" aria-label="Kelas" style="max-width: 12rem;">
                <option value="">Semua kelas</option>
                @foreach ($rombels as $rombel)
                    <option value="{{ $rombel->id }}" @selected((string) $rombelId === (string) $rombel->id)>{{ $rombel->label() }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="{{ $webview ? 'd-flex gap-2' : 'siswa-index-toolbar__search' }}">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIS">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </div>
</form>

<div class="{{ $webview ? 'talim-panel' : 'madani-card' }}">
    <div class="table-responsive">
        <table class="{{ $webview ? 'talim-table' : 'table table-hover mb-0 align-middle' }}">
            <thead>
                <tr>
                    <th style="width: 3rem;">No</th>
                    <th>Nama</th>
                    @if ($admin)
                        <th>Kelas</th>
                    @endif
                    <th>Jenis</th>
                    <th>Alasan</th>
                    <th style="width: 8rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $izin)
                    <tr>
                        <td>{{ $items->firstItem() + $i }}</td>
                        <td>{{ $izin->siswa?->nama }}</td>
                        @if ($admin)
                            <td>{{ $izin->rombel?->label() }}</td>
                        @endif
                        <td>{{ $izin->labelJenis() }}</td>
                        <td>{{ $izin->alasan ?: '—' }}</td>
                        <td>
                            @if ($izin->punyaSuratOrtu())
                                <a class="btn btn-sm btn-outline-primary" href="{{ route($surat_route, $izin) }}" target="_blank">Surat</a>
                            @else
                                <span class="text-secondary small">Belum ada surat</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $admin ? 6 : 5 }}" class="text-center text-secondary py-3">Tidak ada surat izin/sakit pada tanggal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endif
@endsection
