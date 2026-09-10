@extends('layouts.talim-webview')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', $gtk->nama_lengkap)

@section('content')
<div class="talim-stat-grid">
    @foreach ($jenisList as $item)
        <a class="talim-stat is-link" href="{{ route('talim.tunjangan.show', $item['kode']) }}">
            <div class="talim-stat__label">{{ $item['judul'] }}</div>
            <div class="talim-stat__value" style="font-size: 0.95rem; font-weight: 600;">{{ $item['deskripsi'] }}</div>
        </a>
    @endforeach
</div>
@endsection
