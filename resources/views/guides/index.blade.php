@extends('layouts.app', ['title' => 'Panduan Ujian'])

@section('content')
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Panduan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Panduan Ujian</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Panduan ditulis oleh petugas dan dapat berisi heading, tabel, gambar, tautan, serta lampiran dokumen.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-500 shadow-sm">
        @if($meta['updated_at'])
            Update: {{ \Illuminate\Support\Carbon::parse($meta['updated_at'])->translatedFormat('d F Y') }}
        @else
            Sumber: Panduan Lokal
        @endif
    </div>
</div>

<div class="mb-5 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
    <div class="flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('guides.index', ['group' => 'siswa']) }}"
            @class([
                'shrink-0 rounded-2xl px-4 py-2 text-sm font-black ring-1 ring-slate-200',
                'bg-teal-600 text-white ring-teal-600' => $selectedGroup === 'siswa',
                'bg-white text-slate-700' => $selectedGroup !== 'siswa',
                'opacity-50' => ! $hasStudentGuide,
            ])>Siswa</a>
        <a href="{{ route('guides.index', ['group' => 'panitia']) }}"
            @class([
                'shrink-0 rounded-2xl px-4 py-2 text-sm font-black ring-1 ring-slate-200',
                'bg-teal-600 text-white ring-teal-600' => $selectedGroup === 'panitia',
                'bg-white text-slate-700' => $selectedGroup !== 'panitia',
                'opacity-50' => ! $hasCommitteeGuide,
            ])>Panitia</a>
    </div>
    @if($selectedGroup === 'panitia' && $committeeRoles->isNotEmpty())
        <div class="mt-2 flex gap-2 overflow-x-auto border-t border-slate-100 pt-2">
            @foreach($committeeRoles as $role)
                <a href="{{ route('guides.index', ['group' => 'panitia', 'role' => $role['role']]) }}"
                    @class([
                        'shrink-0 rounded-xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                        'bg-slate-950 text-white ring-slate-950' => $selectedRole === $role['role'],
                        'bg-white text-slate-700' => $selectedRole !== $role['role'],
                    ])>{{ $role['title'] }}</a>
            @endforeach
        </div>
    @endif
</div>

<div class="grid gap-5">
    @forelse($guides as $guide)
        <section class="grid gap-5 lg:grid-cols-[16rem_1fr]">
            <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-4 shadow-sm lg:sticky lg:top-24">
                <div class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">{{ $guide->group }}</div>
                <h2 class="mt-2 text-xl font-black text-slate-950">{{ $guide->title }}</h2>
                @if($guide->toc !== [])
                    <nav class="mt-4 grid gap-1 border-t border-slate-100 pt-3">
                        <div class="mb-1 text-xs font-black uppercase tracking-wide text-slate-400">Daftar Isi</div>
                        @foreach($guide->toc as $item)
                            <a href="#{{ $item['id'] }}"
                                @class([
                                    'rounded-xl px-3 py-2 text-sm font-bold text-slate-600 transition hover:bg-teal-50 hover:text-teal-700',
                                    'ml-3 text-xs' => $item['level'] === 3,
                                ])>{{ $item['title'] }}</a>
                        @endforeach
                    </nav>
                @endif
            </aside>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="guide-content">
                    {!! $guide->content_html !!}
                </div>

                @if($guide->attachments->isNotEmpty())
                    <div class="mt-8 border-t border-slate-200 pt-5">
                        <h3 class="text-lg font-black text-slate-950">Lampiran</h3>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach($guide->attachments as $attachment)
                                <a href="{{ $attachment->url }}" target="_blank" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-200 hover:bg-teal-50">
                                    <span class="block text-sm font-black text-slate-950">{{ $attachment->title }}</span>
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $attachment->file_name }} / {{ $attachment->size_label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        </section>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
            Data panduan belum tersedia.
        </div>
    @endforelse
</div>
@endsection
