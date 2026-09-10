@extends('layouts.app')

@section('title', 'Tunjangan')
@section('heading', 'Tunjangan')
@section('subheading', 'Pilih jenis dokumen tunjangan guru tersertifikasi')

@section('content')
<style>
    .tunjangan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.85rem;
    }
    .tunjangan-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        background: #fff;
        padding: 1.1rem 1rem 1.15rem;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s ease, transform .15s ease;
        display: block;
    }
    .tunjangan-card:hover {
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        transform: translateY(-1px);
        color: inherit;
    }
    .tunjangan-card__title {
        font-weight: 800;
        margin: 0 0 0.35rem;
        color: #0f172a;
    }
    .tunjangan-card__desc {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.35;
    }
</style>

<div class="tunjangan-grid">
    @foreach ($jenisList as $item)
        <a class="tunjangan-card" href="{{ route('tunjangan.jenis.index', $item['kode']) }}">
            <h3 class="tunjangan-card__title">{{ $item['judul'] }}</h3>
            <p class="tunjangan-card__desc">{{ $item['deskripsi'] }}</p>
        </a>
    @endforeach
</div>
@endsection
