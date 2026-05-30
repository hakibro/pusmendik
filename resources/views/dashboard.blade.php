@extends('layouts.app', ['title' => 'Beranda Pusmendik'])

@section('content')
<section class="overflow-hidden rounded-[2rem] bg-slate-950 text-white shadow-2xl shadow-slate-900/20">
    <div class="grid gap-8 px-5 py-8 sm:px-8 lg:grid-cols-[1.15fr_.85fr] lg:px-12 lg:py-14">
        <div class="flex flex-col justify-center">
            <span class="mb-4 inline-flex w-fit rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-teal-100">Portal Ujian SMK Darut Taqwa</span>
            <h1 class="max-w-3xl text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">Beranda informasi ujian yang cepat, rapi, dan siap dipakai.</h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">Cek administrasi, jadwal, ruangan, sesi, pengawas, dan progres ujian dari satu tempat. Petugas data dapat masuk untuk menangani rekomendasi pembayaran.</p>
            <div class="relative mt-7 max-w-xl">
                <form method="get" action="{{ route('payments.status') }}" class="flex flex-col gap-3 rounded-3xl bg-white p-2 shadow-xl shadow-black/20 sm:flex-row">
                    <input id="home-student-search" name="q" autocomplete="off" class="min-h-12 flex-1 rounded-2xl border-0 px-4 text-slate-900 outline-none ring-1 ring-transparent placeholder:text-slate-400 focus:ring-teal-500" placeholder="Ketik nama atau ID Yayasan siswa">
                    <button disabled class="min-h-12 rounded-2xl bg-slate-200 px-6 text-sm font-black text-slate-500">Cek Status Pembayaran</button>
                </form>
                <div id="home-student-results" class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-3xl border border-slate-200 bg-white text-left text-slate-900 shadow-2xl shadow-slate-900/20"></div>
            </div>
        </div>
        <div class="grid content-center gap-4">
            <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                <div class="text-sm font-semibold text-slate-300">Status hari ini</div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-slate-950"><div class="text-2xl font-black">{{ number_format($stats['siswa']) }}</div><div class="text-xs font-bold text-slate-500">Siswa aktif</div></div>
                    <div class="rounded-2xl bg-white p-4 text-slate-950"><div class="text-2xl font-black">{{ number_format($stats['lunas']) }}</div><div class="text-xs font-bold text-slate-500">Lunas</div></div>
                    <div class="rounded-2xl bg-white p-4 text-slate-950"><div class="text-2xl font-black">{{ number_format($stats['rekom']) }}</div><div class="text-xs font-bold text-slate-500">Rekom</div></div>
                    <div class="rounded-2xl bg-white p-4 text-slate-950"><div class="text-2xl font-black">{{ number_format($stats['jadwal']) }}</div><div class="text-xs font-bold text-slate-500">Jadwal</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('payments.status') }}" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/10">
        <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">ID</div>
        <h2 class="text-lg font-black">Cek Pembayaran</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Verifikasi ringkasan administrasi siswa menggunakan ID Yayasan.</p>
    </a>
    <a href="{{ route('schedules.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/10">
        <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">JW</div>
        <h2 class="text-lg font-black">Jadwal Ujian</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Pantau tanggal, mapel, durasi, dan status ujian.</p>
    </a>
    <a href="{{ route('rooms.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/10">
        <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">RS</div>
        <h2 class="text-lg font-black">Ruangan & Sesi</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Cari penempatan siswa berdasarkan ruangan, sesi, tingkat, dan kelas.</p>
    </a>
    <a href="{{ route('live.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/10">
        <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">LV</div>
        <h2 class="text-lg font-black">Live Ujian</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Lihat progres jawaban siswa pada sesi aktif.</p>
    </a>
</section>

<section class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <h2 class="text-xl font-black tracking-tight">Jadwal Hari Ini</h2>
            <p class="mt-1 text-sm text-slate-500">Data langsung dari sistem ujian.</p>
        </div>
        <a href="{{ route('schedules.index') }}" class="text-sm font-black text-teal-700 hover:text-teal-600">Lihat semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead><tr class="text-left text-xs font-black uppercase tracking-wide text-slate-500"><th class="py-3 pr-4">Ujian</th><th class="px-4 py-3">Mapel</th><th class="px-4 py-3">Durasi</th><th class="pl-4 py-3">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($jadwalHariIni as $item)
                <tr><td class="py-4 pr-4 font-bold">{{ $item->judul }}</td><td class="px-4 py-4 text-slate-600">{{ $item->nama_mapel }}</td><td class="px-4 py-4 text-slate-600">{{ $item->durasi_menit }} menit</td><td class="pl-4 py-4"><span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $item->status }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="py-8 text-center text-slate-500">Tidak ada jadwal hari ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
<script>
(() => {
    const input = document.getElementById('home-student-search');
    const results = document.getElementById('home-student-results');
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
                    <span class="block text-sm font-black">${student.nama} - ${student.kelas ?? '-'}</span>
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
