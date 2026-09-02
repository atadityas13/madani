@extends('layouts.app')

@section('title', $heading)
@section('heading', $heading)
@section('subheading', $subheading)

@section('content')
<div class="madani-card p-4" style="max-width: 640px;">
    <div class="stat-label mb-2">Menyusul</div>
    <p class="text-secondary mb-0">{{ $keterangan }}</p>
</div>
@endsection
