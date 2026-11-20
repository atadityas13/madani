@extends('layouts.app')

@section('title', 'Sync GTK Simpatisans')
@section('heading', 'Sync GTK Simpatisans')
@section('subheading', 'Bandingkan dulu, baru terapkan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <a class="btn btn-outline-secondary" href="{{ route('gtk.index') }}">Kembali ke GTK</a>
    @if (count($tambah) > 0 || count($perbarui) > 0)
        <form method="POST" action="{{ route('gtk.sync-simpatisans.terapkan') }}" data-confirm="Terapkan penambahan dan pembaruan ini? Data yang sudah terisi di Madani tidak ditimpa, kecuali DUK." data-confirm-title="Terapkan sinkron" data-loading-text="Menerapkan…">
            @csrf
            <button class="btn btn-madani" type="submit">Terapkan penambahan dan pembaruan</button>
        </form>
    @endif
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="alert alert-light border">
    Ditemukan {{ $total_remote }} guru di Simpatisans. {{ $sama }} sudah sama.
    Guru dicocokkan lewat NIP (angka saja, tanpa spasi). NUPTK hanya dipakai bila NIP di Madani kosong dan NUPTK itu unik.
    DUK selalu mengikuti Simpatisans. Isian lain hanya diisi bila di Madani masih kosong.
    Tenaga kependidikan tidak diusulkan untuk dihapus.
</div>

@foreach ($peringatan as $pesan)
    <div class="alert alert-warning">{{ $pesan }}</div>
@endforeach

@if (count($bentrok) > 0)
    <div class="madani-card p-0 mb-3">
        <div class="p-3 border-bottom fw-semibold">NUPTK sama, NIP berbeda — tidak diubah</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Madani</th>
                        <th>NIP Madani</th>
                        <th>Simpatisans</th>
                        <th>NIP Simpatisans</th>
                        <th>NUPTK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bentrok as $item)
                        <tr>
                            <td>{{ $item['nama_lokal'] }}</td>
                            <td>{{ $item['nip_lokal'] ?: '—' }}</td>
                            <td>{{ $item['nama_remote'] }}</td>
                            <td>{{ $item['nip_remote'] }}</td>
                            <td>{{ $item['nuptk'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="madani-card p-0 mb-3">
    <div class="p-3 border-bottom fw-semibold">Akan ditambahkan ({{ count($tambah) }})</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th>DUK</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tambah as $row)
                    <tr>
                        <td>{{ $row['nama'] }}</td>
                        <td>{{ $row['nip'] ?: '—' }}</td>
                        <td>{{ $row['nuptk'] ?: '—' }}</td>
                        <td>{{ filled($row['duk']) ? $row['duk'] : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-secondary p-3">Tidak ada guru baru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="madani-card p-0 mb-3">
    <div class="p-3 border-bottom fw-semibold">Akan diperbarui ({{ count($perbarui) }})</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Perubahan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perbarui as $item)
                    <tr>
                        <td>{{ $item['gtk']->namaLengkap }}</td>
                        <td>{{ $item['gtk']->nip ?: '—' }}</td>
                        <td>
                            @foreach ($item['perubahan'] as $ubah)
                                <div>
                                    {{ $ubah['label'] }}:
                                    {{ $ubah['dari'] ?? 'kosong' }} → {{ $ubah['ke'] }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-secondary p-3">Tidak ada pembaruan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="madani-card p-0 mb-3">
    <div class="p-3 border-bottom fw-semibold">Ada di Madani, tidak ada di Simpatisans ({{ count($hapus) }})</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hapus as $item)
                    <tr>
                        <td>{{ $item['gtk']->namaLengkap }}</td>
                        <td>{{ $item['gtk']->nip ?: '—' }}</td>
                        <td>{{ $item['gtk']->nuptk ?: '—' }}</td>
                        <td class="text-end">
                            @if ($item['bisa_dihapus'])
                                <form method="POST" action="{{ route('gtk.destroy', $item['gtk']) }}" data-confirm="{{ $item['gtk']->namaLengkap }} tidak ada di Simpatisans. Hapus dari Madani?" data-confirm-title="Hapus GTK" data-loading-text="Menghapus…">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="kembali" value="sync">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            @else
                                <span class="text-secondary">Masih wali kelas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-secondary p-3">Tidak ada guru Madani di luar Simpatisans.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
