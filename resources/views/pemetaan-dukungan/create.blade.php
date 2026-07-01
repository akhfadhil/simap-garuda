@extends(Auth::user()->role === 'admin_partai' ? 'layouts.admin' : 'layouts.role-dashboard')

@section('title', 'Tambah Pendukung')
@section('admin_active', 'pemetaan-dukungan')
@section('role_active', 'pemetaan-dukungan')
@section('role_title', 'Pemetaan Dukungan')
@section('role_subtitle', 'Tambah Pendukung Baru')

@section('admin_content')
    @include('pemetaan-dukungan.form-create')
@endsection

@section('role_content')
    @include('pemetaan-dukungan.form-create')
@endsection
