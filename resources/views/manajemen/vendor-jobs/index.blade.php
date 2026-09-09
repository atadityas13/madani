@extends('layouts.app')

@section('title', 'Job vendor')
@section('heading', 'Job vendor')
@section('subheading', 'Manajemen')

@section('content')
<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="stat-label mb-0">Daftar job vendor</div>
    <a class="btn btn-madani" href="{{ route('manajemen.vendor-jobs.create') }}">Tambah</a>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama job</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Siswa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>{{ $job->nama }}</td>
                        <td>{{ $job->vendor?->name ?? '—' }}</td>
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
                        <td class="text-end">
                            <div class="emis-aksi">
                                <a class="emis-aksi-btn" href="{{ route('manajemen.vendor-jobs.edit', $job) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('manajemen.vendor-jobs.destroy', $job) }}" data-confirm="Hapus job vendor ini?" data-confirm-title="Hapus" data-loading-text="Menghapus…">
                                    @csrf
                                    @method('DELETE')
                                    <button class="emis-aksi-btn" type="submit" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary p-3">Belum ada job vendor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($jobs->hasPages())
    <div class="mt-3">{{ $jobs->links() }}</div>
@endif
@endsection
