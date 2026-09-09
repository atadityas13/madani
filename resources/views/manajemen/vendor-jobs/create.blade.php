@extends('layouts.app')

@section('title', 'Tambah job vendor')
@section('heading', 'Tambah job vendor')
@section('subheading', 'Manajemen')

@section('content')
    @include('manajemen.vendor-jobs._form', [
        'action' => route('manajemen.vendor-jobs.store'),
        'method' => 'POST',
    ])
@endsection
