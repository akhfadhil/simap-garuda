@extends(Auth::user()->role === 'admin_partai' ? 'layouts.admin' : 'layouts.role-dashboard')

@section('title', 'Pemetaan Dukungan')
@section('admin_active', 'pemetaan-dukungan')
@section('role_active', 'pemetaan-dukungan')
@section('role_title', 'Pemetaan Dukungan')
@section('role_subtitle', 'Data Pendukung')

@section('admin_content')
    @include('pemetaan-dukungan.list')
@endsection

@section('role_content')
    @include('pemetaan-dukungan.list')
@endsection
