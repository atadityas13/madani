@extends('layouts.talim-webview')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', $gtk->nama_lengkap)

@section('content')
<style>
    .talim-menu-list { display: grid; gap: 0.65rem; }
    .talim-menu-item {
        display: block;
        background: #fff;
        border: 1px solid var(--madani-line, #ececec);
        border-radius: 0.75rem;
        padding: 0.95rem 1rem;
        text-decoration: none;
        color: inherit;
    }
    .talim-menu-item__title {
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.2rem;
    }
    .talim-menu-item__desc {
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.35;
        margin: 0;
    }
</style>

<div class="talim-menu-list">
    @foreach ($jenisList as $item)
        <a class="talim-menu-item" href="{{ route('talim.tunjangan.show', $item['kode']) }}">
            <div class="talim-menu-item__title">{{ $item['judul'] }}</div>
            @if (filled($item['deskripsi']))
                <p class="talim-menu-item__desc">{{ $item['deskripsi'] }}</p>
            @endif
        </a>
    @endforeach
</div>
@endsection
