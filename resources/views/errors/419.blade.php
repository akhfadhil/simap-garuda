@extends('errors.layout', [
    'code' => '419',
    'title' => 'Sesi Kedaluwarsa',
    'message' => 'Sesi formulir sudah habis. Muat ulang halaman lalu ulangi aksi terakhir.',
    'tone' => 'amber',
])

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
    </svg>
@endsection
