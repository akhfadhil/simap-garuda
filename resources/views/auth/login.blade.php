@extends('layouts.guest')

@section('content')
<div class="relative z-10 w-full max-w-md px-5">
    <div class="text-center mb-10">
        <span class="inline-block bg-red-600 text-white text-[10px] tracking-[3px] px-3 py-1 mb-4 rounded font-semibold">Partai Garuda - 2026</span>
        <h1 class="font-display text-6xl tracking-[4px] dark:text-white text-gray-900">SIM<span class="text-red-600">AP</span></h1>
        <p class="text-[11px] dark:text-gray-500 text-gray-400 tracking-[2px] uppercase mt-2">Sistem Rekap dan Saksi Partai Garuda</p>
    </div>

    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 p-9 shadow-xl">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-7 font-semibold">// Login</p>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
                {{ $errors->first() }}
            </div>
            @endif

            <div class="mb-5">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username"
                       class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-3.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" placeholder="Masukkan password"
                       class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-3.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            </div>

            <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-display text-xl tracking-[3px] py-4 rounded-xl active:scale-[0.99] transition">
                MASUK
            </button>
        </form>
    </div>
</div>
@endsection
