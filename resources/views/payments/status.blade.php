@extends('layouts.app', ['title' => 'Cek Pembayaran'])

@section('content')
<section class="mx-auto max-w-3xl">
    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 text-center shadow-sm sm:p-8">
        <span class="inline-flex rounded-full bg-teal-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-teal-700">Cek Administrasi</span>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">Cek Status Pembayaran</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">Ketik nama atau ID Yayasan, lalu pilih siswa dari popup hasil pencarian.</p>
        <div class="relative mt-6">
            <form method="get" class="flex flex-col gap-3 rounded-3xl bg-slate-100 p-2 sm:flex-row">
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
                @else
                    <pre class="max-h-[34rem] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-100">{{ json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            @else
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">Siswa tidak ditemukan.</div>
            @endif
        </div>
    @endif
</section>

<script>
(() => {
    const input = document.getElementById('payment-student-search');
    const results = document.getElementById('payment-student-results');
    if (!input || !results) return;

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
                <a href="${student.url}" class="block border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-teal-50">
                    <span class="block text-sm font-black text-slate-950">${student.nama} - ${student.kelas ?? '-'}</span>
                    <span class="mt-1 block text-xs font-semibold text-slate-500">${student.idyayasan} / ${student.status_pembayaran}</span>
                </a>
            `).join('')
            : '<div class="px-4 py-3 text-sm font-semibold text-slate-500">Siswa tidak ditemukan.</div>';
        results.classList.remove('hidden');
    });

    document.addEventListener('click', (event) => {
        if (!results.contains(event.target) && event.target !== input) {
            results.classList.add('hidden');
        }
    });
})();
</script>
@endsection
