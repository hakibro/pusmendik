@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="mb-4">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
    </div>

    <form method="get" class="mb-4 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('supervisors.index') }}" @class([
                'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                'bg-teal-600 text-white ring-teal-600' => !request('tanggal'),
                'bg-white text-slate-700' => request('tanggal'),
            ])>Semua</a>
            @foreach ($tanggalOptions as $tanggal)
                @php($date = \Illuminate\Support\Carbon::parse($tanggal)->locale('id'))
                <a href="{{ route('supervisors.index', ['tanggal' => $tanggal]) }}"
                    @class([
                        'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                        'bg-teal-600 text-white ring-teal-600' => request('tanggal') === $tanggal,
                        'bg-white text-slate-700' => request('tanggal') !== $tanggal,
                    ])>{{ $date->translatedFormat('l - d/m') }}</a>
            @endforeach
        </div>
    </form>

    @php($roomGroups = $items->groupBy(fn($item) => $item->nama_ruangan ?? '-'))
    <div class="grid gap-3">
        @forelse($roomGroups as $ruangan => $roomRows)
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-700">Ruangan</div>
                        <h2 class="mt-1 text-lg font-black text-slate-950">{{ $ruangan }}</h2>
                    </div>
                    <span
                        class="shrink-0 rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $roomRows->count() }}
                        jadwal</span>
                </div>
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($roomRows->groupBy(fn($item) => ($item->tanggal ?? '-') . '|' . ($item->nama_sesi ?? '-')) as $key => $rows)
                        @php([$tanggal, $sesi] = explode('|', $key, 2))
                        @php($date = $tanggal !== '-' ? \Illuminate\Support\Carbon::parse($tanggal)->locale('id') : null)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-black uppercase tracking-wide text-teal-700">
                                {{ $date ? $date->translatedFormat('l, d/m/Y') : '-' }}</div>
                            <div class="mt-1 text-base font-black text-slate-950">
                                {{ $rows->first()->pengawas ?? 'Belum ada pengawas' }}</div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-700">{{ $sesi }}</span>
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500">{{ $rows->count() }}
                                    jadwal</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Data
                pengawas tidak ditemukan.</div>
        @endforelse
    </div>
@endsection
