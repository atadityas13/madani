@extends('layouts.app')

@section('title', 'Job saya')
@section('heading', 'Job saya')
@section('subheading', 'Daftar pekerjaan kartu e-pelajar')

@section('content')
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <div class="stat-label mb-0">Daftar job</div>
    @if ($foto === 'sudah')
        <span class="badge text-bg-success">Filter: sudah foto</span>
    @elseif ($foto === 'belum')
        <span class="badge text-bg-warning">Filter: belum foto</span>
    @endif
    @if ($foto !== '')
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('vendor.jobs.index') }}">Reset filter</a>
    @endif
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama job</th>
                    <th>Status</th>
                    <th>Siswa</th>
                    <th>Foto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    @php
                        $belum = max(0, (int) $job->siswas_count - (int) $job->sudah_foto_count);
                    @endphp
                    <tr>
                        <td>{{ $job->nama }}</td>
                        <td>
                            @switch($job->status)
                                @case('aktif')
                                    <span class="badge text-bg-success">Aktif</span>
                                    @break
                                @case('selesai')
                                    <span class="badge text-bg-secondary">Selesai</span>
                                    @break
                                @default
                                    <span class="badge text-bg-light text-dark">Draft</span>
                            @endswitch
                        </td>
                        <td>{{ number_format($job->siswas_count) }}</td>
                        <td>
                            <span class="text-success">{{ number_format($job->sudah_foto_count) }}</span>
                            /
                            <span class="text-danger">{{ number_format($belum) }}</span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-madani" href="{{ route('vendor.jobs.show', $job) }}">Kelola</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-secondary p-3">
                            @if ($foto !== '')
                                Tidak ada job dengan filter ini.
                            @else
                                Belum ada job ditugaskan. Hubungi admin madrasah.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
