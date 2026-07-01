@extends(Auth::user()->role === 'admin_partai' ? 'layouts.admin' : 'layouts.role-dashboard')

@section('title', 'Edit Pendukung')
@section('admin_active', 'pemetaan-dukungan')
@section('role_active', 'pemetaan-dukungan')
@section('role_title', 'Pemetaan Dukungan')
@section('role_subtitle', 'Edit Data Pendukung')

@section('admin_content')
    @include('pemetaan-dukungan.form-edit')
@endsection

@section('role_content')
    @include('pemetaan-dukungan.form-edit')
@endsection
