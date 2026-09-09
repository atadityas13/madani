@extends('layouts.talim-webview')

@section('title', $judul)
@section('heading', $judul)
@section('subheading', $rombel ? $rombel->label().($tahun_label ? ' · '.$tahun_label : '') : 'Belum ada rombel')

@section('content')
    <div class="talim-toolbar">
        <a class="talim-back" href="{{ $kembali_url }}">← Kembali</a>
        <div class="talim-toolbar__meta">{{ number_format($rows->total()) }} siswa</div>
    </div>

    @if (! $rombel)
        <div class="talim-empty">
            <div class="fw-semibold mb-1">Belum ada rombel</div>
            <div class="text-secondary small">Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif.</div>
        </div>
    @else
        <div class="talim-panel">
            @forelse ($rows as $i => $row)
                <div class="talim-incomplete">
                    <div class="talim-incomplete__nama">
                        <span class="text-secondary me-1">{{ $rows->firstItem() + $i }}.</span>
                        @if ($row['url'])
                            <a class="talim-row-link" href="{{ $row['url'] }}">{{ $row['nama'] }}</a>
                        @else
                            {{ $row['nama'] }}
                        @endif
                        @if ($row['jenis_kelamin'])
                            <span class="text-secondary small ms-1">({{ $row['jenis_kelamin'] }})</span>
                        @endif
                    </div>
                    @if ($row['lengkap'])
                        <div class="talim-incomplete__tags">
                            <span class="talim-tag is-ok">Lengkap</span>
                        </div>
                    @elseif (count($row['kekurangan']) > 0)
                        <div class="talim-incomplete__tags">
                            @foreach ($row['kekurangan'] as $tag)
                                <span class="talim-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-secondary text-center py-3 small">Tidak ada siswa pada filter ini.</div>
            @endforelse
        </div>

        @if ($rows->hasPages())
            <div class="talim-pager">
                @if ($rows->onFirstPage())
                    <span class="talim-pager__btn is-disabled">Sebelumnya</span>
                @else
                    <a class="talim-pager__btn" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <span class="talim-pager__meta">{{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
                @if ($rows->hasMorePages())
                    <a class="talim-pager__btn" href="{{ $rows->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="talim-pager__btn is-disabled">Berikutnya</span>
                @endif
            </div>
        @endif
    @endif
@endsection
