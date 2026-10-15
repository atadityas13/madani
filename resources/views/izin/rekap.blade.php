@extends($layout)

@section('title', 'Rekap SIA')
@section('heading', 'Rekap SIA')
@section('subheading', $rombel_wali ? $rombel_wali->label().($tahun_label ? ' · '.$tahun_label : '') : 'Sakit, izin, dan alpa per kelas')

@section('content')
@if ($webview && $kembali_url)
    <div class="talim-toolbar">
        <a class="talim-back" href="{{ $kembali_url }}">← Kembali</a>
    </div>
@endif

<form method="GET" class="{{ $webview ? 'mb-3' : 'siswa-index-toolbar mb-3' }}" action="{{ url()->current() }}">
    <div class="{{ $webview ? 'd-flex align-items-center gap-2 flex-wrap' : 'siswa-index-toolbar__filters align-items-center' }}">
        <label class="form-label mb-0 text-nowrap" for="tanggal">Tanggal</label>
        <input class="form-control" type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()" style="max-width: 12rem;">
    </div>
</form>

@if ($webview)
    <div class="talim-stat-grid mb-3">
        <div class="talim-stat"><div class="talim-stat__label">Hadir</div><div class="talim-stat__value">{{ number_format($rekap['totals']['hadir']) }}</div></div>
        <div class="talim-stat is-warn"><div class="talim-stat__label">Sakit</div><div class="talim-stat__value">{{ number_format($rekap['totals']['sakit']) }}</div></div>
        <div class="talim-stat"><div class="talim-stat__label">Izin</div><div class="talim-stat__value">{{ number_format($rekap['totals']['izin']) }}</div></div>
        <div class="talim-stat"><div class="talim-stat__label">Alpa</div><div class="talim-stat__value">{{ number_format($rekap['totals']['alpa']) }}</div></div>
    </div>
@endif

<div class="{{ $webview ? 'talim-panel mb-3' : 'madani-card mb-3' }}">
    <div class="table-responsive">
        <table class="{{ $webview ? 'talim-table' : 'table table-hover mb-0 align-middle' }}">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th class="text-end">Siswa</th>
                    <th class="text-end">Hadir</th>
                    <th class="text-end">Sakit</th>
                    <th class="text-end">Izin</th>
                    <th class="text-end">Alpa</th>
                    <th class="text-end">SIA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekap['rows'] as $row)
                    <tr>
                        <td>{{ $row['rombel'] }}</td>
                        <td class="text-end">{{ number_format($row['jumlah_siswa']) }}</td>
                        <td class="text-end">{{ number_format($row['hadir']) }}</td>
                        <td class="text-end">{{ number_format($row['sakit']) }}</td>
                        <td class="text-end">{{ number_format($row['izin']) }}</td>
                        <td class="text-end">{{ number_format($row['alpa']) }}</td>
                        <td class="text-end">{{ number_format($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-3">Tidak ada data rombel.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($rekap['rows']) > 1)
                <tfoot>
                    <tr class="fw-semibold">
                        <td>Total</td>
                        <td class="text-end">{{ number_format($rekap['totals']['jumlah_siswa']) }}</td>
                        <td class="text-end">{{ number_format($rekap['totals']['hadir']) }}</td>
                        <td class="text-end">{{ number_format($rekap['totals']['sakit']) }}</td>
                        <td class="text-end">{{ number_format($rekap['totals']['izin']) }}</td>
                        <td class="text-end">{{ number_format($rekap['totals']['alpa']) }}</td>
                        <td class="text-end">{{ number_format($rekap['totals']['total']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<h2 class="{{ $webview ? 'talim-section__title' : 'h6 mb-2' }}">Rincian ketidakhadiran</h2>
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
                </tr>
            </thead>
            <tbody>
                @forelse ($rincian as $i => $izin)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $izin->siswa?->nama }}</td>
                        @if ($admin)
                            <td>{{ $izin->rombel?->label() }}</td>
                        @endif
                        <td>{{ $izin->labelJenis() }}</td>
                        <td>{{ $izin->alasan ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $admin ? 5 : 4 }}" class="text-center text-secondary py-3">Tidak ada laporan sakit, izin, atau alpa pada tanggal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
