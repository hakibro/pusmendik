@extends('layouts.app', ['title' => 'Panduan Ujian'])

@section('content')
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Panduan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Panduan Ujian</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Ringkasan panduan dari sistem ujian untuk petugas dan peserta.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-500 shadow-sm">
        @if($meta['updated_at'])
            Update: {{ \Illuminate\Support\Carbon::parse($meta['updated_at'])->translatedFormat('d F Y') }}
        @else
            Sumber: API Panduan
        @endif
        @if($meta['app_version'])
            <span class="ml-2 rounded-full bg-teal-50 px-2 py-1 text-teal-700">v{{ $meta['app_version'] }}</span>
        @endif
    </div>
</div>

@if($meta['error'])
    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
        Panduan belum bisa dimuat: {{ $meta['error'] }}
    </div>
@endif

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
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-teal-700">{{ $guide['role'] ?? 'panduan' }}</span>
                        @if(!empty($guide['audience']))
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-600">{{ $guide['audience'] }}</span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $guide['title'] ?? 'Panduan' }}</h2>
                    @if(!empty($guide['description']))
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $guide['description'] }}</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4">
                @foreach(($guide['sections'] ?? []) as $section)
                    <div class="border-t border-slate-200 pt-4 first:border-t-0 first:pt-0">
                        <h3 class="text-lg font-black text-slate-950">{{ $section['title'] ?? 'Bagian Panduan' }}</h3>
                        <div class="mt-3 grid gap-3 lg:grid-cols-2">
                            @foreach(($section['steps'] ?? []) as $step)
                                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                                    @if(!empty($step['screenshot_url']))
                                        <img src="{{ $step['screenshot_url'] }}" alt="{{ $step['title'] ?? 'Screenshot panduan' }}" class="aspect-video w-full bg-white object-contain">
                                    @endif
                                    <div class="p-4">
                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            @if(!empty($step['id']))
                                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500 ring-1 ring-slate-200">{{ $step['id'] }}</span>
                                            @endif
                                            @if(!empty($step['url']))
                                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-black text-teal-700">{{ $step['url'] }}</span>
                                            @endif
                                        </div>
                                        <h4 class="text-base font-black text-slate-950">{{ $step['title'] ?? 'Langkah Panduan' }}</h4>
                                        @if(!empty($step['instruction']))
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['instruction'] }}</p>
                                        @endif
                                        @if(!empty($step['notes']))
                                            <p class="mt-3 rounded-2xl bg-white px-3 py-2 text-xs font-semibold leading-5 text-slate-500 ring-1 ring-slate-200">{{ $step['notes'] }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
            Data panduan tidak ditemukan.
        </div>
    @endforelse
</div>
@endsection
