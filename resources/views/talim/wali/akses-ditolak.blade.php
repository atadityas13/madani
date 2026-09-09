@extends('layouts.talim-webview')

@section('title', 'Wali Kelas')
@section('heading', 'Wali Kelas')
@section('subheading', 'Akses ditolak')

@section('content')
<div class="talim-empty">
    <div class="fw-semibold mb-2">Anda tidak memiliki akses wali kelas</div>
    <div class="text-secondary small">
        Silahkan menghubungi Admin jika anda adalah wali kelas.
    </div>
</div>
@endsection
