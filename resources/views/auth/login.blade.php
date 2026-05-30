@extends('layouts.app', ['title' => 'Login Petugas'])

@section('content')
<section class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_.8fr] lg:items-center">
    <div class="rounded-[2rem] bg-slate-950 p-8 text-white shadow-2xl shadow-slate-900/20">
        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-teal-100">Akses Petugas</span>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Masuk untuk menangani rekomendasi.</h1>
        <p class="mt-4 text-sm leading-6 text-slate-300">Menu pembayaran hanya dibuka untuk user sistem ujian dengan role data.</p>
    </div>
    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="text-2xl font-black">Login Petugas</h2>
        <form method="post" action="{{ route('login.store') }}" class="mt-5 grid gap-4">
            @csrf
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
            </label>
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Password
                <input type="password" name="password" required class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
            </label>
            <button class="rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Login</button>
        </form>
    </div>
</section>
@endsection
