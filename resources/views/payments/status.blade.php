@extends('layouts.app', ['title' => 'Cek Pembayaran'])

@php
    $formatValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'ya' : 'tidak';
        }
        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? number_format((float) $value, 2, ',', '.') : number_format((float) $value, 0, ',', '.');
        }
        return $value === null || $value === '' ? '-' : (string) $value;
    };
@endphp

@section('content')
<section class="mx-auto max-w-5xl">
    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 text-center shadow-sm sm:p-8">
        <span class="inline-flex rounded-full bg-teal-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-teal-700">Cek Administrasi</span>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">Cek Status Pembayaran</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">Ketik nama atau ID Yayasan, lalu pilih siswa dari popup hasil pencarian.</p>
        <div class="relative mt-6">
            <form id="payment-search-form" method="get" class="flex flex-col gap-3 rounded-3xl bg-slate-100 p-2 sm:flex-row">
                <input id="payment-student-search" name="q" value="{{ request('q') }}" autocomplete="off" autofocus class="min-h-12 flex-1 rounded-2xl border border-transparent bg-white px-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-100" placeholder="Nama siswa / ID Yayasan">
                <button disabled class="min-h-12 rounded-2xl bg-slate-200 px-6 text-sm font-black text-slate-500">Cek Status Pembayaran</button>
            </form>
            <div id="payment-student-results" class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-3xl border border-slate-200 bg-white text-left shadow-2xl shadow-slate-900/15"></div>
        </div>
    </div>

    @if(request('q'))
        <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            @if($student)
                <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-xl font-black">{{ $student->nama }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $student->idyayasan }} / {{ $student->nama_kelas }}</p>
                    </div>
                    <span @class(['w-fit rounded-full px-3 py-1 text-xs font-black', 'bg-emerald-50 text-emerald-700' => $student->status_pembayaran === 'Lunas', 'bg-rose-50 text-rose-700' => $student->status_pembayaran !== 'Lunas'])>{{ $student->status_pembayaran }}</span>
                </div>
                @if(isset($summary['error']))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ $summary['error'] }}</div>
                @elseif($paymentView)
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Total Tagihan</div>
                            <div class="mt-2 text-2xl font-black text-slate-950">Rp {{ number_format($paymentView['total_bill'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Sudah Dibayar</div>
                            <div class="mt-2 text-2xl font-black text-emerald-700">Rp {{ number_format($paymentView['total_paid'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Sisa Tunggakan</div>
                            <div class="mt-2 text-2xl font-black text-rose-700">Rp {{ number_format($paymentView['total_remaining'], 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="mb-4">
                            <h3 class="text-lg font-black text-slate-950">Data Pembayaran per Periode</h3>
                            <p class="mt-1 text-sm text-slate-500">Dikelompokkan berdasarkan periode, kategori, dan item pembayaran.</p>
                        </div>

                        <div class="grid gap-4">
                            @forelse($paymentView['periods'] as $period)
                                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white open:border-teal-200">
                                    <summary class="flex cursor-pointer list-none flex-col justify-between gap-2 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center">
                                        <div>
                                            <div class="font-black text-slate-950">Periode {{ $period['period_id'] }}</div>
                                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $period['kelas_info'] }}</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs font-black">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Tagihan Rp {{ number_format($period['total_billed'], 0, ',', '.') }}</span>
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Dibayar Rp {{ number_format($period['total_paid'], 0, ',', '.') }}</span>
                                            <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa Rp {{ number_format($period['total_remaining'], 0, ',', '.') }}</span>
                                        </div>
                                    </summary>

                                    <div class="border-t border-slate-200 p-4">
                                        <div class="mb-4 rounded-2xl bg-slate-50 p-4">
                                            <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Summary</div>
                                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                                @forelse(($period['summary'] ?? []) as $key => $value)
                                                    <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $key }}</div>
                                                        <div class="mt-1 font-black text-slate-800">{{ $formatValue($value) }}</div>
                                                    </div>
                                                @empty
                                                    <div class="text-sm text-slate-500">Summary tidak tersedia.</div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div class="grid gap-3">
                                            @forelse(($period['categories'] ?? []) as $category)
                                                <details class="overflow-hidden rounded-2xl border border-slate-200">
                                                    <summary class="flex cursor-pointer list-none flex-col justify-between gap-2 bg-white px-4 py-3 sm:flex-row sm:items-center">
                                                        <div class="font-black text-slate-950">{{ $category['category_name'] }}</div>
                                                        <div class="text-xs font-bold text-slate-500">{{ count($category['items'] ?? []) }} item</div>
                                                    </summary>
                                                    <div class="border-t border-slate-200 bg-slate-50 p-4">
                                                        <div class="mb-3 flex flex-wrap gap-2 text-xs font-black">
                                                            <span class="rounded-full bg-white px-3 py-1 text-slate-700">Tagihan Rp {{ number_format((float) ($category['summary']['total_paid'] ?? 0), 0, ',', '.') }}</span>
                                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Dibayar Rp {{ number_format((float) ($category['summary']['total_billed'] ?? 0), 0, ',', '.') }}</span>
                                                            <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa Rp {{ number_format(abs((float) ($category['summary']['total_remaining'] ?? 0)), 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="mb-4 grid gap-2 sm:grid-cols-3">
                                                            @forelse(($category['summary'] ?? []) as $key => $value)
                                                                <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $key }}</div>
                                                                    <div class="mt-1 font-black text-slate-800">{{ $formatValue($value) }}</div>
                                                                </div>
                                                            @empty
                                                                <div class="text-sm text-slate-500">Summary kategori tidak tersedia.</div>
                                                            @endforelse
                                                        </div>

                                                        <div class="grid gap-2">
                                                            @forelse(($category['items'] ?? []) as $item)
                                                                <details class="rounded-2xl border border-slate-200 bg-white">
                                                                    <summary class="flex cursor-pointer list-none flex-col justify-between gap-2 px-4 py-3 sm:flex-row sm:items-center">
                                                                        <div>
                                                                            <div class="font-bold text-slate-900">{{ $item['unit'] }}</div>
                                                                            <div class="mt-1 text-xs font-semibold text-slate-500">Status: {{ $item['payment_status'] }} / Jurnal: {{ $item['journal_date'] }}</div>
                                                                        </div>
                                                                        <div class="flex flex-wrap gap-2 text-xs font-black">
                                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Bayar Rp {{ number_format($item['paid'], 0, ',', '.') }}</span>
                                                                            <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa Rp {{ number_format($item['remaining'], 0, ',', '.') }}</span>
                                                                        </div>
                                                                    </summary>
                                                                    <div class="border-t border-slate-200 p-4">
                                                                        <div class="grid gap-2 sm:grid-cols-2">
                                                                            @foreach(($item['raw'] ?? []) as $key => $value)
                                                                                <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                                                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $key }}</div>
                                                                                    <div class="mt-1 font-semibold text-slate-800">{{ is_scalar($value) || $value === null ? $formatValue($value) : json_encode($value, JSON_UNESCAPED_UNICODE) }}</div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </details>
                                                            @empty
                                                                <div class="rounded-2xl bg-white px-4 py-6 text-center text-sm text-slate-500">Items tidak tersedia.</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </details>
                                            @empty
                                                <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Categories tidak tersedia.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </details>
                            @empty
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-10 text-center text-slate-500">Tidak ada data periods yang dapat ditampilkan.</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">Data pembayaran tidak tersedia.</div>
                @endif
            @else
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">Siswa tidak ditemukan.</div>
            @endif
        </div>
    @endif
</section>

<div id="payment-loading-overlay" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm">
    <div class="rounded-3xl bg-white p-6 text-center shadow-2xl">
        <div class="mx-auto size-12 animate-spin rounded-full border-4 border-slate-200 border-t-teal-600"></div>
        <div class="mt-4 text-sm font-black text-slate-900">Memuat data pembayaran dari API</div>
        <div class="mt-1 text-xs font-semibold text-slate-500">Mohon tunggu sebentar.</div>
    </div>
</div>

<script>
(() => {
    const input = document.getElementById('payment-student-search');
    const results = document.getElementById('payment-student-results');
    const form = document.getElementById('payment-search-form');
    const loading = document.getElementById('payment-loading-overlay');
    if (!input || !results) return;
    const showLoading = () => {
        loading?.classList.remove('hidden');
        loading?.classList.add('flex');
    };

    form?.addEventListener('submit', () => {
        if (input.value.trim().length >= 2) showLoading();
    });

    let controller;
    input.addEventListener('input', async () => {
        const q = input.value.trim();
        if (controller) controller.abort();
        if (q.length < 2) {
            results.classList.add('hidden');
            results.innerHTML = '';
            return;
        }

        controller = new AbortController();
        const response = await fetch(`{{ route('students.search') }}?q=${encodeURIComponent(q)}`, { signal: controller.signal }).catch(() => null);
        if (!response || !response.ok) return;
        const students = await response.json();
        results.innerHTML = students.length
            ? students.map((student) => `
                <a href="${student.url}" data-payment-loading class="block border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-teal-50">
                    <span class="block text-sm font-black text-slate-950">${student.nama} - ${student.kelas ?? '-'}</span>
                    <span class="mt-1 block text-xs font-semibold text-slate-500">${student.idyayasan} / ${student.status_pembayaran}</span>
                </a>
            `).join('')
            : '<div class="px-4 py-3 text-sm font-semibold text-slate-500">Siswa tidak ditemukan.</div>';
        results.classList.remove('hidden');
        results.querySelectorAll('[data-payment-loading]').forEach((link) => {
            link.addEventListener('click', showLoading);
        });
    });

    document.addEventListener('click', (event) => {
        if (!results.contains(event.target) && event.target !== input) {
            results.classList.add('hidden');
        }
    });
})();
</script>
@endsection
