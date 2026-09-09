@extends('layouts.talim-webview')

@section('title', 'Wali Kelas')
@section('heading', 'Wali Kelas')
@section('subheading', $rombel ? $rombel->label().($tahun_label ? ' · '.$tahun_label : '') : 'Belum ada rombel')

@section('content')
@if (! $rombel)
    <div class="talim-empty">
        <div class="fw-semibold mb-1">Belum ada rombel</div>
        <div class="text-secondary small">Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif. Hubungi admin.</div>
    </div>
@else
    <div class="talim-stat-grid">
        @foreach ($cards as $card)
            @if ($card['url'])
                <a class="talim-stat {{ $card['tone'] }} is-link" href="{{ $card['url'] }}">
                    <div class="talim-stat__label">{{ $card['label'] }}</div>
                    <div class="talim-stat__value">{{ number_format($card['value']) }}</div>
                </a>
            @else
                <div class="talim-stat {{ $card['tone'] }}">
                    <div class="talim-stat__label">{{ $card['label'] }}</div>
                    <div class="talim-stat__value">{{ number_format($card['value']) }}</div>
                </div>
            @endif
        @endforeach
    </div>

    <section class="talim-section">
        <h2 class="talim-section__title">5 login siswa terakhir</h2>
        <div class="talim-panel">
            <table class="talim-table">
                <thead>
                    <tr>
                        <th style="width: 2.5rem;">No</th>
                        <th>Nama</th>
                        <th>Waktu login</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($login_terakhir as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @if ($row['url'])
                                    <a class="talim-row-link" href="{{ $row['url'] }}">{{ $row['nama'] }}</a>
                                @else
                                    {{ $row['nama'] }}
                                @endif
                            </td>
                            <td>{{ $row['waktu'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-secondary text-center py-3">Belum ada siswa yang login.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="talim-section">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <h2 class="talim-section__title mb-0">Siswa belum lengkap</h2>
            @if ($belum_lengkap_url)
                <a class="small" href="{{ $belum_lengkap_url }}">Lihat semua</a>
            @endif
        </div>
        <div class="talim-panel">
            @forelse ($belum_lengkap as $row)
                <div class="talim-incomplete">
                    <div class="talim-incomplete__nama">
                        @if ($row['url'])
                            <a class="talim-row-link" href="{{ $row['url'] }}">{{ $row['nama'] }}</a>
                        @else
                            {{ $row['nama'] }}
                        @endif
                    </div>
                    <div class="talim-incomplete__tags">
                        @foreach ($row['kekurangan'] as $tag)
                            <span class="talim-tag">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-secondary text-center py-3 small">Semua siswa sudah lengkap.</div>
            @endforelse
        </div>
    </section>
@endif
@endsection
