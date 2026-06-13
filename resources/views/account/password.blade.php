@extends('layouts.app')
@section('title', 'Ubah Password')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-8">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Akun</p>
        <h1 class="font-display text-4xl tracking-[2px] dark:text-gray-100 text-gray-800">UBAH PASSWORD</h1>
        <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">Ganti password login untuk akun {{ Auth::user()->username }}.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
        {{ $errors->first() }}
    </div>
    @endif

    <div class="dark:bg-gray-900 bg-white rounded-xl border dark:border-gray-800 border-gray-200 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('password.update') }}" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Password Lama</label>
                <input type="password" name="current_password" required
                       class="w-full dark:bg-gray-950 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Password Baru</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full dark:bg-gray-950 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                <p class="text-[11px] dark:text-gray-600 text-gray-400 mt-1.5">Minimal 6 karakter.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       class="w-full dark:bg-gray-950 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            </div>

            <div class="flex items-center justify-between gap-3 flex-wrap pt-2">
                <a href="{{ route('dashboard.' . Auth::user()->role) }}"
                   class="px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-800 hover:bg-gray-100 transition">
                    Kembali
                </a>
                <button class="px-5 py-2.5 rounded-lg text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition">
                    Simpan Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
