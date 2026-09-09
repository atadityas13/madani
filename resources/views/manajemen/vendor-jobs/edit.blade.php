@extends('layouts.app')

@section('title', 'Ubah job vendor')
@section('heading', 'Ubah job vendor')
@section('subheading', 'Manajemen')

@section('content')
    @include('manajemen.vendor-jobs._form', [
        'action' => route('manajemen.vendor-jobs.update', $job),
        'method' => 'PUT',
    ])
@endsection
