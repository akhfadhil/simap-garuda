@extends('errors.layout', [
    'code' => '503',
    'title' => 'Layanan Sementara Tidak Tersedia',
    'message' => 'Sistem sedang dalam pemeliharaan atau terlalu sibuk. Silakan coba lagi beberapa saat lagi.',
    'tone' => 'blue',
])

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M6 7v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7M9 11h6M10 15h4M8 7l1-3h6l1 3"/>
    </svg>
@endsection
