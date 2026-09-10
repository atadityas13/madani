@extends('layouts.talim-webview')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', $gtk->nama_lengkap)

@section('content')
<style>
    .talim-menu-list{display:grid;gap:.65rem}
    .talim-menu-item{display:block;background:#fff;border:1px solid #ececec;border-radius:.85rem;padding:1rem 1.05rem;text-decoration:none;color:inherit}
    .talim-menu-item__title{font-weight:800;color:#0f172a;margin-bottom:.2rem;font-size:1.05rem}
    .talim-menu-item__desc{font-size:.85rem;color:#64748b;line-height:1.35;margin:0}
    .talim-menu-item__chev{float:right;color:#94a3b8;font-weight:700}
</style>

<div class="talim-menu-list">
    @foreach ($jenisList as $item)
        <a class="talim-menu-item" href="{{ route('talim.tunjangan.show', $item['kode']) }}">
            <span class="talim-menu-item__chev">›</span>
            <div class="talim-menu-item__title">{{ $item['judul'] }}</div>
            @if (filled($item['deskripsi']))
                <p class="talim-menu-item__desc">{{ $item['deskripsi'] }}</p>
            @endif
        </a>
    @endforeach
</div>
@endsection
