@extends('layouts.talim-webview')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', 'Akses ditolak')

@section('content')
<div class="talim-empty">
    <div class="fw-semibold mb-2">Anda tidak memiliki akses Tunjangan</div>
    <div class="text-secondary small">
        Menu ini hanya untuk guru tersertifikasi (NRG terisi). Hubungi admin jika data NRG Anda belum lengkap.
    </div>
</div>
@endsection
