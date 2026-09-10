@extends('layouts.app')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', 'Dokumen tunjangan guru tersertifikasi')

@section('content')
<style>
    .tunjangan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.9rem;
    }
    .tunjangan-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        background: #fff;
        padding: 1.15rem 1.05rem 1.2rem;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s ease, transform .15s ease;
        display: block;
        min-height: 6.5rem;
    }
    .tunjangan-card:hover {
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
        transform: translateY(-1px);
        color: inherit;
    }
    .tunjangan-card__title {
        font-weight: 800;
        font-size: 1.05rem;
        margin: 0 0 0.4rem;
        color: #0f172a;
        letter-spacing: 0.01em;
    }
    .tunjangan-card__desc {
        margin: 0;
        font-size: 0.875rem;
        color: #64748b;
        line-height: 1.4;
    }
</style>

<div class="tunjangan-grid">
    @foreach ($jenisList as $item)
        <a class="tunjangan-card" href="{{ route('tunjangan.jenis.index', $item['kode']) }}">
            <h3 class="tunjangan-card__title">{{ $item['judul'] }}</h3>
            @if (filled($item['deskripsi']))
                <p class="tunjangan-card__desc">{{ $item['deskripsi'] }}</p>
            @endif
        </a>
    @endforeach
</div>
@endsection
