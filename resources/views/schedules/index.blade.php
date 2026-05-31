@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="mb-4">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
    </div>

    <form method="get" class="mb-4 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('schedules.index') }}"
                @class([
                    'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                    'bg-teal-600 text-white ring-teal-600' => !request('tanggal'),
                    'bg-white text-slate-700' => request('tanggal'),
                ])>Semua</a>
            @foreach ($tanggalOptions as $tanggal)
                @php($date = \Illuminate\Support\Carbon::parse($tanggal)->locale('id'))
                <a href="{{ route('schedules.index', ['tanggal' => $tanggal]) }}"
                    @class([
                        'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                        'bg-teal-600 text-white ring-teal-600' => request('tanggal') === $tanggal,
                        'bg-white text-slate-700' => request('tanggal') !== $tanggal,
                    ])>{{ $date->translatedFormat('l - d/m') }}</a>
            @endforeach
        </div>
    </form>

    @php($groups = $items->groupBy('tanggal'))
    <div class="grid gap-3">
        @forelse($groups as $tanggal => $rows)
            @php($date = \Illuminate\Support\Carbon::parse($tanggal)->locale('id'))
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">{{ $date->translatedFormat('l') }}</h2>
                        <p class="text-xs font-semibold text-slate-500">{{ $date->translatedFormat('d F Y') }}</p>
                    </div>
                    <span
                        class="w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $rows->count() }}
                        ujian</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($rows as $item)
                        <article
                            class="group relative w-fit rounded-md border border-slate-100 bg-white p-2 shadow-sm transition-all duration-200 hover:border-indigo-100 hover:shadow-md hover:shadow-indigo-50/50">
                            <h3
                                class="whitespace-nowrap text-xs font-semibold leading-none text-slate-900 group-hover:text-indigo-600 transition-colors">
                                {{ $item->judul }}
                            </h3>

                            <div class="mt-1.5 flex items-center gap-1.5 text-[10px]">
                                <p class="whitespace-nowrap text-slate-400">
                                    {{ $item->nama_mapel ?? '-' }}
                                </p>

                                <span class="text-slate-200">•</span>

                                <span class="inline-flex items-center gap-0.5 font-medium text-slate-500">
                                    <svg class="h-2.5 w-2.5 text-slate-400" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ $item->durasi_menit }}m
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Jadwal tidak
                ditemukan.</div>
        @endforelse
    </div>
@endsection
