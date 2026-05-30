@extends('layouts.app', ['title' => 'Live Ujian'])

@section('content')
<div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Monitoring</p>
    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Live Ujian</h1>
    <p class="mt-2 text-sm text-slate-500">Sesi aktif hari ini berdasarkan jam server Asia/Jakarta.</p>
</div>

<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-xl font-black">Progress per Ruangan</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ujian</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Sesi</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ruangan</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Peserta</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Sudah Dijawab</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Belum</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($sessions as $item)
                <tr><td class="px-4 py-4 font-bold">{{ $item->judul }}</td><td class="px-4 py-4 text-slate-600">{{ $item->nama_sesi }}</td><td class="px-4 py-4 text-slate-600">{{ $item->nama_ruangan }}</td><td class="px-4 py-4 font-black">{{ $item->peserta }}</td><td class="px-4 py-4 text-emerald-700 font-black">{{ $item->dijawab }}</td><td class="px-4 py-4 text-rose-700 font-black">{{ $item->belum }}</td></tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Tidak ada sesi aktif saat ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-xl font-black">Detail per Siswa</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ruangan</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Siswa</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ujian</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Soal</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Dijawab</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Belum</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($details as $item)
                <tr><td class="px-4 py-4 font-bold">{{ $item->nama_ruangan }}</td><td class="px-4 py-4">{{ $item->nama }}<br><span class="text-xs font-semibold text-slate-500">{{ $item->idyayasan }}</span></td><td class="px-4 py-4 text-slate-600">{{ $item->judul }}</td><td class="px-4 py-4">{{ $item->jumlah_soal }}</td><td class="px-4 py-4 text-emerald-700 font-black">{{ $item->jumlah_dijawab }}</td><td class="px-4 py-4 text-rose-700 font-black">{{ $item->jumlah_tidak_dijawab }}</td><td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $item->status }}</span></td></tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada detail sesi aktif.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
