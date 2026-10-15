@extends('layouts.app', ['title' => 'Beranda Pusmendik'])

@section('content')
    <section class="relative z-20 rounded-[2rem] bg-slate-950 text-white shadow-2xl shadow-slate-900/20">
        <div class="grid gap-8 px-5 py-8 sm:px-8 lg:grid-cols-[1.15fr_.85fr] lg:px-12 lg:py-14">
            <div class="flex flex-col justify-center">
                <span
                    class="mb-4 inline-flex w-fit rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-teal-100">Pusat
                    Informasi Ujian</span>
                <h1 class="max-w-3xl text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">Informasi ujian sekolah
                    dalam satu layar yang mudah dicek.</h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">Pusmendik membantu siswa, wali, dan
                    petugas melihat kesiapan administrasi, jadwal, ruangan, kehadiran, dan hasil ujian dari data terbaru.
                </p>
            </div>
            <div class="grid content-center gap-3">
                <div class="relative rounded-3xl bg-white p-4 text-slate-950 shadow-xl">
                    <div class="px-1 pb-3">
                        <div class="text-sm font-black text-teal-700">Cek Administrasi</div>
                        <div class="mt-1 text-2xl font-black">Status Pembayaran</div>
                    </div>
                    <form method="get" action="{{ route('payments.status') }}"
                        class="flex flex-col gap-3 rounded-3xl bg-slate-100 p-2">
                        <input id="home-student-search" name="q" autocomplete="off"
                            class="min-h-12 rounded-2xl border border-transparent bg-white px-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-100"
                            placeholder="Ketik nama atau ID Yayasan siswa">
                        <button type="submit" id="home-student-submit" disabled
                            class="min-h-12 rounded-2xl bg-slate-950 px-6 text-sm font-black text-white transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 disabled:hover:bg-slate-200">Cek
                            Status Pembayaran</button>
                    </form>
                    <div id="home-student-results"
                        class="absolute left-0 right-0 top-full z-[90] mt-2 hidden max-h-80 overflow-auto rounded-3xl border border-slate-200 bg-white text-left text-slate-900 shadow-2xl shadow-slate-900/30">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('schedules.index') }}"
                        class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Jadwal</a>
                    <a href="{{ route('rooms.index') }}"
                        class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Ruangan</a>
                    <a href="{{ route('supervisors.index') }}"
                        class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Pengawas</a>
                    <a href="{{ route('results.index') }}"
                        class="rounded-3xl bg-white/10 p-4 font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">Hasil
                        Ujian</a>
                </div>
            </div>
        </div>
    </section>

    @if ($apkDownload)
        <section
            class="relative z-10 mt-8 overflow-hidden rounded-[2rem] border border-teal-200 bg-gradient-to-br from-teal-600 to-teal-800 p-5 text-white shadow-xl shadow-teal-900/20 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span
                        class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-3xl ring-1 ring-white/20">
                        📱
                    </span>
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.2em] text-teal-100">Aplikasi Android Siswa
                        </div>
                        <h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">{{ $apkDownload['app_name'] }}
                        </h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-teal-50">Pasang aplikasi di ponsel Android untuk
                            mengikuti ujian dan memantau informasi akademik. Ukuran
                            {{ $apkDownload['size_label'] }}@if ($apkDownload['uploaded_at']), diperbarui
                                {{ \Illuminate\Support\Carbon::parse($apkDownload['uploaded_at'])->translatedFormat('d M Y') }}@endif.
                        </p>
                    </div>
                </div>
                <a href="{{ $apkDownload['url'] }}" download
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-6 py-4 text-sm font-black text-teal-800 shadow-lg shadow-teal-950/20 transition hover:bg-teal-50 sm:w-auto">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" />
                    </svg>
                    Download APK
                </a>
            </div>

            @if ($exeDownload)
                {{-- Opsi sekunder: sengaja dibuat kecil & bergaya tenang agar siswa
                     tidak salah mengunduh installer Windows. --}}
                <div class="mt-6 border-t border-white/15 pt-4">
                    <details class="group text-xs">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-1.5 font-semibold text-teal-100/70 transition hover:text-teal-50">
                            <svg class="size-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            Untuk petugas: unduh aplikasi versi Windows
                        </summary>
                        <div class="mt-2 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                            <p class="text-[11px] font-semibold leading-5 text-teal-100/80">Khusus perangkat
                                <strong class="text-teal-50">komputer/laptop Windows</strong> (petugas & pengawas).
                                Siswa tidak perlu file ini.</p>
                            <a href="{{ $exeDownload['url'] }}" download
                                class="mt-2 inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-[11px] font-bold text-white/90 transition hover:bg-white/20">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" />
                                </svg>
                                Unduh .exe &middot; {{ $exeDownload['app_name'] }} ({{ $exeDownload['size_label'] }})
                            </a>
                        </div>
                    </details>
                </div>
            @endif
        </section>
    @endif

    <section class="mt-8 grid gap-4 md:grid-cols-4">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-black uppercase tracking-wide text-teal-700">Administrasi</div>
            <h2 class="mt-2 text-xl font-black text-slate-950">Cek sebelum ujian</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Siswa dapat mengecek ringkasan pembayaran dan status
                rekomendasi sebelum mengikuti ujian.</p>
        </article>
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-black uppercase tracking-wide text-orange-500">Panduan</div>
            <h2 class="mt-2 text-xl font-black text-slate-950">Panduan penggunaan sistem</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Tersedia panduan lengkap, tutorial, dan bantuan teknis bagi
                siswa, maupun petugas ujian.</p>
        </article>
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-black uppercase tracking-wide text-sky-700">Pelaksanaan</div>
            <h2 class="mt-2 text-xl font-black text-slate-950">Jadwal, sesi, ruangan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Informasi ujian disusun berdasarkan tanggal, ruangan, sesi, dan
                penempatan siswa.</p>
        </article>
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-black uppercase tracking-wide text-rose-700">Monitoring</div>
            <h2 class="mt-2 text-xl font-black text-slate-950">Live progres ujian</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Petugas dapat memantau pengerjaan siswa pada sesi aktif dan
                rekap kehadiran.</p>
        </article>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-xl font-black tracking-tight">Jadwal Hari Ini</h2>
                <p class="mt-1 text-sm text-slate-500">Data langsung dari sistem ujian.</p>
            </div>
            <a href="{{ route('schedules.index') }}" class="text-sm font-black text-teal-700 hover:text-teal-600">Lihat
                semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        <th class="py-3 pr-4">Ujian</th>
                        <th class="px-4 py-3">Mapel</th>
                        <th class="px-4 py-3">Durasi</th>
                        <th class="pl-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jadwalHariIni as $item)
                        <tr>
                            <td class="py-4 pr-4 font-bold">{{ $item->judul }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $item->nama_mapel }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $item->durasi_menit }} menit</td>
                            <td class="pl-4 py-4"><span
                                    class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $item->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500">Tidak ada jadwal hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (() => {
            const input = document.getElementById('home-student-search');
            const results = document.getElementById('home-student-results');
            const submit = document.getElementById('home-student-submit');
            if (!input || !results) return;

            // Tombol submit baru aktif saat ada kata kunci (min. 2 karakter).
            const syncSubmit = () => {
                if (submit) submit.disabled = input.value.trim().length < 2;
            };
            input.addEventListener('input', syncSubmit);
            syncSubmit();

            // Di layar sempit dropdown `absolute` jatuh di bawah lipatan layar,
            // jadi tampilkan sebagai blok biasa di dalam alur dokumen.
            const syncResultsPosition = () => {
                const stacked = window.matchMedia('(max-width: 1023px)').matches;
                results.classList.toggle('static', stacked);
                results.classList.toggle('mt-2', stacked);
                results.classList.toggle('absolute', !stacked);
            };
            syncResultsPosition();
            window.addEventListener('resize', syncResultsPosition);

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
                const response = await fetch(`{{ route('students.search') }}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal
                }).catch(() => null);
                if (!response || !response.ok) return;
                const students = await response.json();
                results.innerHTML = students.length ?
                    students.map((student) => `
                <a href="${student.url}" class="block border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-teal-50">
                    <span class="flex items-center gap-2">
                        <span class="block text-sm font-black">${student.nama} - ${student.kelas ?? '-'}</span>
                        ${student.rekomendasi === 'ya' ? '<span class="shrink-0 rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-black text-teal-700">Rekom Ya</span>' : ''}
                    </span>
                    <span class="mt-1 block text-xs font-semibold text-slate-500">${student.idyayasan} / ${student.status_pembayaran}</span>
                </a>
            `).join('') :
                    '<div class="px-4 py-3 text-sm font-semibold text-slate-500">Siswa tidak ditemukan.</div>';
                results.classList.remove('hidden');
                syncResultsPosition();
            });

            // Enter di input langsung mengirim form (pola sama dengan halaman Rekom).
            input.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                if (input.value.trim().length >= 2) input.form?.submit();
            });

            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== input) {
                    results.classList.add('hidden');
                }
            });
        })();
    </script>
@endsection
