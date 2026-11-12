@extends('layouts.app')

@section('title', 'Monitoring GTK')
@section('heading', 'Monitoring')
@section('subheading', 'Guru dan Tendik')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1 flex-wrap" method="GET" style="max-width: 480px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau NIP" style="min-width: 220px;">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 4rem;">No</th>
                    <th>Nama Guru</th>
                    <th>Terakhir login Aplikasi pada</th>
                    <th class="text-end" style="width: 9rem;">Jumlah Jurnal tercatat</th>
                    <th>Terakhir mengisi Jurnal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['no'] }}</td>
                        <td>{{ $row['nama'] }}</td>
                        <td>{{ $row['terakhir_login'] }}</td>
                        <td class="text-end">{{ number_format($row['jumlah_jurnal']) }}</td>
                        <td>{{ $row['terakhir_jurnal'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-secondary text-center py-4">Belum ada akun Ta'lim GTK.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
