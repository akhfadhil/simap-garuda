@extends('errors.layout', [
    'code' => '500',
    'title' => 'Kesalahan Server',
    'message' => 'Terjadi gangguan saat memproses permintaan. Muat ulang halaman, atau kembali ke dashboard jika masalah masih muncul.',
    'tone' => 'red',
])

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
    </svg>
@endsection
