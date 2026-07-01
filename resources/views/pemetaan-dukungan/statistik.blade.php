@extends(Auth::user()->role === 'admin_partai' ? 'layouts.admin' : 'layouts.role-dashboard')

@section('title', 'Statistik Dukungan')
@section('admin_active', 'pemetaan-dukungan')
@section('role_active', 'pemetaan-dukungan')
@section('role_title', 'Pemetaan Dukungan')
@section('role_subtitle', 'Statistik Sebaran')

@section('admin_content')
    @include('pemetaan-dukungan.view-statistik')
@endsection

@section('role_content')
    @include('pemetaan-dukungan.view-statistik')
@endsection
