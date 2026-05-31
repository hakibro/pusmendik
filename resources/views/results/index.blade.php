@extends('layouts.app', ['title' => 'Hasil Ujian'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Hasil Ujian</h1>
        <p class="mt-2 text-sm text-slate-500">Analisis nilai, status pengerjaan, dan rekap hasil berdasarkan filter.</p>
    </div>
    <a href="{{ route('results.download', request()->query()) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:bg-teal-700">Download Excel</a>
</div>

<form method="get" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Cari siswa / ID Yayasan
            <input name="q" value="{{ request('q') }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Tingkat
            <select name="tingkat" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                <option value="">Semua</option>
                @foreach($filterOptions['tingkat'] as $option)
                    <option value="{{ $option }}" @selected((string) request('tingkat') === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Kelas
            <select name="kelas" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                <option value="">Semua</option>
                @foreach($filterOptions['kelas'] as $option)
                    <option value="{{ $option }}" @selected((string) request('kelas') === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Ujian
            <select name="ujian" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                <option value="">Semua</option>
                @foreach($filterOptions['ujian'] as $id => $judul)
                    <option value="{{ $id }}" @selected((string) request('ujian') === (string) $id)>{{ $judul }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Filter</button>
        <a class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50" href="{{ route('results.index') }}">Reset</a>
    </div>
</form>

<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Peserta</div><div class="mt-2 text-2xl font-black">{{ $analysis['total'] }}</div></article>
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Selesai</div><div class="mt-2 text-2xl font-black">{{ $analysis['finished'] }}</div></article>
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Rata-rata</div><div class="mt-2 text-2xl font-black">{{ $analysis['average'] }}</div></article>
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Tertinggi</div><div class="mt-2 text-2xl font-black">{{ $analysis['highest'] }}</div></article>
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Terendah</div><div class="mt-2 text-2xl font-black">{{ $analysis['lowest'] }}</div></article>
    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-black uppercase tracking-wide text-slate-500">Kelulusan</div><div class="mt-2 text-2xl font-black">{{ $analysis['pass_rate'] }}%</div></article>
</section>

<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80">
                <tr>
                    @foreach(['Nama', 'ID Yayasan', 'Kelas', 'Ujian', 'Mapel', 'Tanggal', 'Nilai', 'Benar', 'Salah', 'Kosong', 'Status', 'Lulus'] as $label)
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="transition hover:bg-teal-50/40">
                        <td class="min-w-64 px-4 py-4 font-black text-slate-950">{{ $item->nama }}</td>
                        <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-700">{{ $item->idyayasan }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->nama_kelas }}</td>
                        <td class="min-w-64 px-4 py-4 text-slate-700">{{ $item->judul }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->nama_mapel ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->tanggal }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-lg font-black text-slate-950">{{ number_format((float) $item->nilai, 2, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->jumlah_benar }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->jumlah_salah }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $item->jumlah_tidak_dijawab }}</td>
                        <td class="whitespace-nowrap px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $item->status }}</span></td>
                        <td class="whitespace-nowrap px-4 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-emerald-50 text-emerald-700' => (int) $item->lulus === 1, 'bg-rose-50 text-rose-700' => (int) $item->lulus !== 1])>{{ (int) $item->lulus === 1 ? 'Ya' : 'Tidak' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-10 text-center text-slate-500">Data hasil ujian tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $items->links() }}</div>
@endsection
