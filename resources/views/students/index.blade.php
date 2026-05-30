@extends('layouts.app', ['title' => 'Pembayaran'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Petugas Data</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Pembayaran</h1>
        <p class="mt-2 text-sm text-slate-500">Cari siswa, cek status administrasi, dan simpan penanganan rekomendasi.</p>
    </div>
    <form method="post" action="{{ route('students.sync') }}">
        @csrf
        <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:bg-teal-700">Sync Data</button>
    </form>
</div>

<form method="get" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Cari nama / ID / NIS<input name="q" value="{{ request('q') }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100"></label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Kelas
            <select name="kelas" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100"><option value="">Semua</option>@foreach($kelas as $k)<option @selected(request('kelas')===$k)>{{ $k }}</option>@endforeach</select>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Status Pembayaran
            <select name="status_pembayaran" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100"><option value="">Semua</option><option @selected(request('status_pembayaran')==='Lunas')>Lunas</option><option @selected(request('status_pembayaran')==='Belum Lunas')>Belum Lunas</option></select>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Status Rekom
            <select name="rekomendasi" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100"><option value="">Semua</option><option value="ya" @selected(request('rekomendasi')==='ya')>Ya</option><option value="tidak" @selected(request('rekomendasi')==='tidak')>Tidak</option></select>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Petugas
            <select name="petugas" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                <option value="">Semua</option>
                @foreach($petugas as $namaPetugas)
                    <option value="{{ $namaPetugas }}" @selected(request('petugas') === $namaPetugas)>{{ $namaPetugas }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Filter</button>
        <a class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50" href="{{ route('students.index') }}">Reset</a>
    </div>
</form>

<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">ID Yayasan</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Nama</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Kelas</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Pembayaran</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Rekom</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Nominal Rekom</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Petugas</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($students as $student)
                <tr class="transition hover:bg-teal-50/40">
                    <td class="whitespace-nowrap px-4 py-4 font-black text-slate-950">{{ $student->idyayasan }}</td>
                    <td class="min-w-64 px-4 py-4 font-semibold text-slate-800">{{ $student->nama }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $student->nama_kelas }}</td>
                    <td class="whitespace-nowrap px-4 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-emerald-50 text-emerald-700' => $student->status_pembayaran === 'Lunas', 'bg-rose-50 text-rose-700' => $student->status_pembayaran !== 'Lunas'])>{{ $student->status_pembayaran }}</span></td>
                    <td class="whitespace-nowrap px-4 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-teal-50 text-teal-700' => $student->rekomendasi === 'ya', 'bg-slate-100 text-slate-600' => $student->rekomendasi !== 'ya'])>{{ $student->rekomendasi }}</span></td>
                    <td class="whitespace-nowrap px-4 py-4 font-black text-slate-800">{{ $student->rekomendasi === 'ya' && $student->nominal_rekom ? 'Rp '.number_format((float) $student->nominal_rekom, 0, ',', '.') : '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $student->rekomendasi === 'ya' ? ($student->handled_by_name ?? '-') : '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right"><a class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700" href="{{ route('students.show', $student->id) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Data siswa tidak ditemukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $students->links() }}</div>
@endsection
