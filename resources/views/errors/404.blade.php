@extends('errors.layout', [
    'code' => '404',
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Alamat yang dibuka tidak tersedia atau halaman sudah dipindahkan.',
    'tone' => 'amber',
])

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 9.75h.01M14.25 9.75h.01M8 15c1.2-1 2.5-1.5 4-1.5s2.8.5 4 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
    </svg>
@endsection
