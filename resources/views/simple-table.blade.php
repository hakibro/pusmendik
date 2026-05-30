@extends('layouts.app', ['title' => $title])

@section('content')
@php($filterOptions = $filterOptions ?? [])
<div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Pusmendik</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
    </div>
</div>

<form method="get" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($filters as $name => $label)
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">
                {{ $label }}
                @if(isset($filterOptions[$name]))
                    <select name="{{ $name }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                        <option value="">Semua</option>
                        @foreach($filterOptions[$name] as $option)
                            <option value="{{ $option }}" @selected((string) request($name) === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="{{ str_contains($name, 'tanggal') ? 'date' : 'text' }}" name="{{ $name }}" value="{{ request($name) }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                @endif
            </label>
        @endforeach
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Filter</button>
        <a class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50" href="{{ url()->current() }}">Reset</a>
    </div>
</form>

<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80">
                <tr>
                    @foreach($columns as $label)<th class="whitespace-nowrap px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">{{ $label }}</th>@endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="transition hover:bg-teal-50/40">
                        @foreach(array_keys($columns) as $key)
                            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-700">{{ $item->{$key} ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="px-4 py-10 text-center text-slate-500">Data tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $items->links() }}</div>
@endsection
