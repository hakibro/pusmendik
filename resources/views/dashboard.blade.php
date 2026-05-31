@extends('layouts.app', ['title' => 'Beranda Pusmendik'])

@section('content')
<section class="relative z-20 rounded-[2rem] bg-slate-950 text-white shadow-2xl shadow-slate-900/20">
    <div class="grid gap-8 px-5 py-8 sm:px-8 lg:grid-cols-[1.15fr_.85fr] lg:px-12 lg:py-14">
        <div class="flex flex-col justify-center">
            <span class="mb-4 inline-flex w-fit rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-teal-100">Pusat Informasi Ujian</span>
            <h1 class="max-w-3xl text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">Informasi ujian sekolah dalam satu layar yang mudah dicek.</h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">Pusmendik membantu siswa, wali, dan petugas melihat kesiapan administrasi, jadwal, ruangan, kehadiran, dan hasil ujian dari data terbaru.</p>
        </div>
        <div class="grid content-center gap-3">
            <div class="relative rounded-3xl bg-white p-4 text-slate-950 shadow-xl">
                <div class="px-1 pb-3">
                    <div class="text-sm font-black text-teal-700">Cek Administrasi</div>
                    <div class="mt-1 text-2xl font-black">Status Pembayaran</div>
                </div>
                <form method="get" action="{{ route('payments.status') }}" class="flex flex-col gap-3 rounded-3xl bg-slate-100 p-2">
                    <input id="home-student-search" name="q" autocomplete="off" class="min-h-12 rounded-2xl border border-transparent bg-white px-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-100" placeholder="Ketik nama atau ID Yayasan siswa">
                    <button disabled class="min-h-12 rounded-2xl bg-slate-200 px-6 text-sm font-black text-slate-500">Cek Status Pembayaran</button>
                </form>
                <div id="home-student-results" class="absolute left-0 right-0 top-full z-[90] mt-2 hidden max-h-80 overflow-auto rounded-3xl border border-slate-200 bg-white text-left text-slate-900 shadow-2xl shadow-slate-900/30"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('schedules.index') }}" class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Jadwal</a>
                <a href="{{ route('rooms.index') }}" class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Ruangan</a>
                <a href="{{ route('supervisors.index') }}" class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Pengawas</a>
                <a href="{{ route('results.index') }}" class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Hasil Ujian</a>
            </div>
        </div>
    </div>
</section>

<section class="mt-8 grid gap-4 md:grid-cols-3">
    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-black uppercase tracking-wide text-teal-700">Administrasi</div>
        <h2 class="mt-2 text-xl font-black text-slate-950">Cek sebelum ujian</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Siswa dapat mengecek ringkasan pembayaran dan status rekomendasi sebelum mengikuti ujian.</p>
    </article>
    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-black uppercase tracking-wide text-sky-700">Pelaksanaan</div>
        <h2 class="mt-2 text-xl font-black text-slate-950">Jadwal, sesi, ruangan</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Informasi ujian disusun berdasarkan tanggal, ruangan, sesi, dan penempatan siswa.</p>
    </article>
    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-black uppercase tracking-wide text-rose-700">Monitoring</div>
        <h2 class="mt-2 text-xl font-black text-slate-950">Live progres ujian</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Petugas dapat memantau pengerjaan siswa pada sesi aktif dan rekap kehadiran.</p>
    </article>
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
