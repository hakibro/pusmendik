@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="mb-4">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
    </div>

    <form method="get" class="mb-4 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
            <input name="q" value="{{ request('q') }}"
                class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100 lg:col-span-2"
                placeholder="Nama / ID Yayasan">
            @foreach (['tingkat' => 'Tingkat', 'kelas' => 'Kelas', 'ruangan' => 'Ruangan', 'sesi' => 'Sesi'] as $name => $label)
                <select name="{{ $name }}"
                    class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold outline-none focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <option value="">{{ $label }}</option>
                    @foreach ($filterOptions[$name] ?? [] as $option)
                        <option value="{{ $option }}" @selected((string) request($name) === (string) $option)>{{ $option }}</option>
                    @endforeach
                </select>
            @endforeach
        </div>
        <div class="mt-3 flex gap-2">
            <button class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white">Filter</button>
            <a href="{{ route('rooms.index') }}"
                class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-700">Reset</a>
        </div>
    </form>

    @php($groups = $items->getCollection()->groupBy(fn($item) => ($item->nama_ruangan ?? '-') . '|' . ($item->nama_sesi ?? '-')))
    <div class="grid gap-3">
        @forelse($groups as $key => $rows)
            @php([$ruangan, $sesi] = explode('|', $key, 2))
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">{{ $ruangan }}</h2>
                        <p class="text-xs font-semibold text-slate-500">{{ $sesi }} /
                            {{ $rows->first()->waktu_mulai ?? '-' }} - {{ $rows->first()->waktu_selesai ?? '-' }}</p>
                    </div>
                    <span
                        class="w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $rows->count() }}
                        siswa</span>
                </div>
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($rows as $item)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="text-base font-black leading-snug text-slate-950">{{ $item->nama }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $item->idyayasan }} /
                                {{ $item->nama_kelas }}</div>

                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Data ruangan
                tidak ditemukan.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
@endsection
