@extends('layouts.app', ['title' => 'Rekom'])

@section('content')
    <div class="mb-6">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Petugas Data</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Rekom</h1>
            <p class="mt-2 text-sm text-slate-500">Cari siswa, cek status administrasi, dan simpan penanganan rekomendasi.
            </p>
        </div>
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($paymentSummary as $summary)
            <button type="button" data-summary-open="summary-{{ $loop->index }}"
                class="rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/10">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-700">Tingkat</div>
                        <div class="mt-1 text-2xl font-black text-slate-950">{{ $summary['tingkat'] }}</div>
                    </div>
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $summary['total'] }}
                        siswa</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-2xl bg-emerald-50 p-3">
                        <div class="text-xs font-bold text-emerald-700">Lunas</div>
                        <div class="text-xl font-black text-emerald-800">{{ $summary['lunas'] }}</div>
                    </div>
                    <div class="rounded-2xl bg-rose-50 p-3">
                        <div class="text-xs font-bold text-rose-700">Belum</div>
                        <div class="text-xl font-black text-rose-800">{{ $summary['belum'] }}</div>
                    </div>
                </div>
            </button>

            <div id="summary-{{ $loop->index }}" data-summary-modal
                class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
                <div class="max-h-[85vh] w-full max-w-4xl overflow-auto rounded-3xl bg-white shadow-2xl">
                    <div
                        class="sticky top-0 z-10 mb-4 flex items-center justify-between gap-3 border-b border-slate-100 bg-white/95 p-5 backdrop-blur">
                        <div>
                            <div class="text-xs font-black uppercase tracking-wide text-teal-700">Detail Pembayaran</div>
                            <h2 class="mt-1 text-2xl font-black text-slate-950">Tingkat {{ $summary['tingkat'] }}</h2>
                        </div>
                        <button type="button" data-summary-close
                            class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">Tutup</button>
                    </div>

                    <div class="grid gap-3 p-5 pt-0">
                        @foreach ($summary['classes'] as $class)
                            <details class="rounded-2xl border border-slate-200 bg-slate-50">
                                <summary
                                    class="flex cursor-pointer list-none flex-col justify-between gap-2 px-4 py-3 sm:flex-row sm:items-center">
                                    <div class="font-black text-slate-950">{{ $class['kelas'] }}</div>
                                    <div class="flex gap-2 text-xs font-black">
                                        <span class="rounded-full bg-white px-3 py-1 text-slate-700">{{ $class['total'] }}
                                            siswa</span>
                                        <span
                                            class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">{{ $class['lunas'] }}
                                            lunas</span>
                                        <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">{{ $class['belum'] }}
                                            belum</span>
                                    </div>
                                </summary>
                                <div class="border-t border-slate-200 bg-white p-3">
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($class['students'] as $studentItem)
                                            <div class="rounded-2xl border border-slate-200 p-3">
                                                <div class="font-black text-slate-950">{{ $studentItem->nama }}</div>
                                                <div class="mt-1 text-xs font-semibold text-slate-500">
                                                    {{ $studentItem->idyayasan }}</div>
                                                <span
                                                    @class([
                                                        'mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black',
                                                        'bg-emerald-50 text-emerald-700' =>
                                                            $studentItem->status_pembayaran === 'Lunas',
                                                        'bg-rose-50 text-rose-700' => $studentItem->status_pembayaran !== 'Lunas',
                                                    ])>{{ $studentItem->status_pembayaran }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <form method="get" id="filter-form" onsubmit="return false"
        class="mb-6 rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <label class="grid gap-1 text-xs font-black uppercase tracking-wide text-slate-500">Cari nama / ID / NIS
                <div class="relative">
                    <input name="q" id="search-input" value="{{ request('q') }}" autocomplete="off"
                        class="min-h-10 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <div id="search-spinner" class="absolute right-3 top-1/2 hidden -translate-y-1/2">
                        <div class="size-4 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"></div>
                    </div>
                </div>
            </label>
            <label class="grid gap-1 text-xs font-black uppercase tracking-wide text-slate-500">Kelas
                <select name="kelas" id="filter-kelas"
                    class="min-h-10 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <option value="">Semua</option>
                    @foreach ($kelas as $k)
                        <option @selected(request('kelas') === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-black uppercase tracking-wide text-slate-500">Status Pembayaran
                <select name="status_pembayaran" id="filter-status-pembayaran"
                    class="min-h-10 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <option value="">Semua</option>
                    <option @selected(request('status_pembayaran') === 'Lunas')>Lunas</option>
                    <option @selected(request('status_pembayaran') === 'Belum Lunas')>Belum Lunas</option>
                </select>
            </label>
            <label class="grid gap-1 text-xs font-black uppercase tracking-wide text-slate-500">Status Rekom
                <select name="rekomendasi" id="filter-rekomendasi"
                    class="min-h-10 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <option value="">Semua</option>
                    <option value="ya" @selected(request('rekomendasi') === 'ya')>Ya</option>
                    <option value="tidak" @selected(request('rekomendasi') === 'tidak')>Tidak</option>
                </select>
            </label>
            <label class="grid gap-1 text-xs font-black uppercase tracking-wide text-slate-500">Petugas
                <select name="petugas" id="filter-petugas"
                    class="min-h-10 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                    <option value="">Semua</option>
                    @foreach ($petugas as $namaPetugas)
                        <option value="{{ $namaPetugas }}" @selected(request('petugas') === $namaPetugas)>{{ $namaPetugas }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 sm:mt-4">
            <button type="button" id="filter-button"
                class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Filter</button>
            <a class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                href="{{ route('students.index') }}">Reset</a>
        </div>
    </form>

    {{-- Desktop: table --}}
    <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">ID Yayasan
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Pembayaran
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Rekom</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Nominal
                            Rekom</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Petugas
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="table-body" class="divide-y divide-slate-100">
                    @forelse($students as $student)
                        @php($statusRekom = $student->rekomendasi ?: 'tidak')
                        <tr class="transition hover:bg-teal-50/40">
                            <td class="whitespace-nowrap px-4 py-4 font-black text-slate-950">{{ $student->idyayasan }}
                            </td>
                            <td class="min-w-64 px-4 py-4 font-semibold text-slate-800">{{ $student->nama }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $student->nama_kelas }}</td>
                            <td class="whitespace-nowrap px-4 py-4"><span
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-black',
                                        'bg-emerald-50 text-emerald-700' => $student->status_pembayaran === 'Lunas',
                                        'bg-rose-50 text-rose-700' => $student->status_pembayaran !== 'Lunas',
                                    ])>{{ $student->status_pembayaran }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4"><span
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-black',
                                        'bg-teal-50 text-teal-700' => $statusRekom === 'ya',
                                        'bg-slate-100 text-slate-600' => $statusRekom !== 'ya',
                                    ])>{{ $statusRekom }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4 font-black text-slate-800">
                                {{ $statusRekom === 'ya' && $student->nominal_rekom ? 'Rp ' . number_format((float) $student->nominal_rekom, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                {{ $statusRekom === 'ya' ? $student->handled_by_name ?? '-' : '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right"><a data-api-loading
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                                    href="{{ route('students.show', $student->id) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Data siswa tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile: cards --}}
    <div id="card-container" class="block space-y-3 lg:hidden">
        @forelse($students as $student)
            @php($statusRekom = $student->rekomendasi ?: 'tidak')
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-black text-slate-950">{{ $student->nama }}</div>
                        <div class="text-xs font-semibold text-slate-500">{{ $student->idyayasan }}</div>
                    </div>
                    <a data-api-loading
                        class="shrink-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                        href="{{ route('students.show', $student->id) }}">Detail</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400">Kelas</span>
                        <div class="font-semibold text-slate-800">{{ $student->nama_kelas }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400">Pembayaran</span>
                        <div><span @class([
                            'mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-black',
                            'bg-emerald-50 text-emerald-700' => $student->status_pembayaran === 'Lunas',
                            'bg-rose-50 text-rose-700' => $student->status_pembayaran !== 'Lunas',
                        ])>{{ $student->status_pembayaran }}</span></div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400">Rekom</span>
                        <div><span @class([
                            'mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-black',
                            'bg-teal-50 text-teal-700' => $statusRekom === 'ya',
                            'bg-slate-100 text-slate-600' => $statusRekom !== 'ya',
                        ])>{{ $statusRekom }}</span></div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400">Nominal Rekom</span>
                        <div class="font-black text-slate-800">
                            {{ $statusRekom === 'ya' && $student->nominal_rekom ? 'Rp ' . number_format((float) $student->nominal_rekom, 0, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs font-semibold text-slate-400">Petugas</span>
                        <div class="font-semibold text-slate-600">
                            {{ $statusRekom === 'ya' ? $student->handled_by_name ?? '-' : '-' }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Data siswa
                tidak ditemukan.</div>
        @endforelse
    </div>

    <div id="pagination" class="mt-5">{{ $students->links() }}</div>
    <div id="api-loading-overlay"
        class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm">
        <div class="rounded-3xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto size-12 animate-spin rounded-full border-4 border-slate-200 border-t-teal-600"></div>
            <div class="mt-4 text-sm font-black text-slate-900">Memuat data pembayaran dari API</div>
            <div class="mt-1 text-xs font-semibold text-slate-500">Mohon tunggu sebentar.</div>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-api-loading]').forEach((link) => {
            link.addEventListener('click', () => {
                document.getElementById('api-loading-overlay')?.classList.remove('hidden');
                document.getElementById('api-loading-overlay')?.classList.add('flex');
            });
        });
    </script>
    <script>
        document.querySelectorAll('[data-summary-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.summaryOpen);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            });
        });
        document.querySelectorAll('[data-summary-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.fixed');
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            });
        });
        document.querySelectorAll('[data-summary-modal]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target !== modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        });
    </script>
    <script>
        (function() {
            const searchInput = document.getElementById('search-input');
            const spinner = document.getElementById('search-spinner');
            const tableBody = document.getElementById('table-body');
            const cardContainer = document.getElementById('card-container');
            const filterKelas = document.getElementById('filter-kelas');
            const filterStatus = document.getElementById('filter-status-pembayaran');
            const filterRekom = document.getElementById('filter-rekomendasi');
            const filterPetugas = document.getElementById('filter-petugas');
            const filterButton = document.getElementById('filter-button');
            let searchTimeout = null;
            let currentAbort = null;

            function getFilters() {
                const params = new URLSearchParams();
                const q = searchInput.value.trim();
                if (q.length >= 2) params.set('q', q);
                if (filterKelas.value) params.set('kelas', filterKelas.value);
                if (filterStatus.value) params.set('status_pembayaran', filterStatus.value);
                if (filterRekom.value) params.set('rekomendasi', filterRekom.value);
                if (filterPetugas.value) params.set('petugas', filterPetugas.value);
                return params;
            }

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function renderRows(data) {
                if (!tableBody) return;

                if (data.length === 0) {
                    tableBody.innerHTML =
                        '<tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Data siswa tidak ditemukan.</td></tr>';
                    return;
                }

                tableBody.innerHTML = data.map(function(s) {
                    const statusRekom = s.rekomendasi || 'tidak';
                    const nominalRekom = statusRekom === 'ya' && s.nominal_rekom ? 'Rp ' + Number(s
                            .nominal_rekom)
                        .toLocaleString('id-ID') : '-';
                    const handledBy = statusRekom === 'ya' ? (s.handled_by_name || '-') : '-';

                    const paymentClass = s.status_pembayaran === 'Lunas' ?
                        'bg-emerald-50 text-emerald-700' :
                        'bg-rose-50 text-rose-700';
                    const rekomClass = statusRekom === 'ya' ?
                        'bg-teal-50 text-teal-700' :
                        'bg-slate-100 text-slate-600';

                    return '<tr class="transition hover:bg-teal-50/40">' +
                        '<td class="whitespace-nowrap px-4 py-4 font-black text-slate-950">' + escapeHtml(s
                            .idyayasan) + '</td>' +
                        '<td class="min-w-64 px-4 py-4 font-semibold text-slate-800">' + escapeHtml(s.nama) +
                        '</td>' +
                        '<td class="whitespace-nowrap px-4 py-4 text-slate-600">' + escapeHtml(s.kelas) +
                        '</td>' +
                        '<td class="whitespace-nowrap px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-black ' +
                        paymentClass + '">' + escapeHtml(s.status_pembayaran) + '</span></td>' +
                        '<td class="whitespace-nowrap px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-black ' +
                        rekomClass + '">' + statusRekom + '</span></td>' +
                        '<td class="whitespace-nowrap px-4 py-4 font-black text-slate-800">' + nominalRekom +
                        '</td>' +
                        '<td class="whitespace-nowrap px-4 py-4 text-slate-600">' + escapeHtml(handledBy) +
                        '</td>' +
                        '<td class="whitespace-nowrap px-4 py-4 text-right"><a class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700" href="' +
                        escapeHtml(s.url) + '">Detail</a></td>' +
                        '</tr>';
                }).join('');
            }

            function renderCards(data) {
                if (!cardContainer) return;

                if (data.length === 0) {
                    cardContainer.innerHTML =
                        '<div class="rounded-3xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Data siswa tidak ditemukan.</div>';
                    return;
                }

                cardContainer.innerHTML = data.map(function(s) {
                    const statusRekom = s.rekomendasi || 'tidak';
                    const nominalRekom = statusRekom === 'ya' && s.nominal_rekom ? 'Rp ' + Number(s
                            .nominal_rekom)
                        .toLocaleString('id-ID') : '-';
                    const handledBy = statusRekom === 'ya' ? (s.handled_by_name || '-') : '-';

                    const paymentClass = s.status_pembayaran === 'Lunas' ?
                        'bg-emerald-50 text-emerald-700' :
                        'bg-rose-50 text-rose-700';
                    const rekomClass = statusRekom === 'ya' ?
                        'bg-teal-50 text-teal-700' :
                        'bg-slate-100 text-slate-600';

                    return '<div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">' +
                        '<div class="flex items-start justify-between gap-3">' +
                        '<div>' +
                        '<div class="font-black text-slate-950">' + escapeHtml(s.nama) + '</div>' +
                        '<div class="text-xs font-semibold text-slate-500">' + escapeHtml(s.idyayasan) +
                        '</div>' +
                        '</div>' +
                        '<a class="shrink-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700" href="' +
                        escapeHtml(s.url) + '">Detail</a>' +
                        '</div>' +
                        '<div class="mt-3 grid grid-cols-2 gap-2 text-sm">' +
                        '<div><span class="text-xs font-semibold text-slate-400">Kelas</span><div class="font-semibold text-slate-800">' +
                        escapeHtml(s.kelas) + '</div></div>' +
                        '<div><span class="text-xs font-semibold text-slate-400">Pembayaran</span><div><span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-black ' +
                        paymentClass + '">' + escapeHtml(s.status_pembayaran) + '</span></div></div>' +
                        '<div><span class="text-xs font-semibold text-slate-400">Rekom</span><div><span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-black ' +
                        rekomClass + '">' + statusRekom + '</span></div></div>' +
                        '<div><span class="text-xs font-semibold text-slate-400">Nominal Rekom</span><div class="font-black text-slate-800">' +
                        nominalRekom + '</div></div>' +
                        '<div class="col-span-2"><span class="text-xs font-semibold text-slate-400">Petugas</span><div class="font-semibold text-slate-600">' +
                        escapeHtml(handledBy) + '</div></div>' +
                        '</div>' +
                        '</div>';
                }).join('');
            }

            function fetchData() {
                if (currentAbort) {
                    currentAbort.abort();
                }
                currentAbort = new AbortController();

                const params = getFilters();
                const url = '/ajax/rekom-siswa?' + params.toString();

                showSpinner();

                fetch(url, {
                        signal: currentAbort.signal
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        renderRows(data);
                        renderCards(data);
                        hideSpinner();
                        currentAbort = null;
                    })
                    .catch(function(err) {
                        if (err.name === 'AbortError') return;
                        hideSpinner();
                        currentAbort = null;
                    });
            }

            function showSpinner() {
                spinner?.classList.remove('hidden');
            }

            function hideSpinner() {
                spinner?.classList.add('hidden');
            }

            // Debounced search on input
            searchInput?.addEventListener('input', function() {
                if (searchTimeout) clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchData, 300);
            });

            // Filter button triggers search
            filterButton?.addEventListener('click', function() {
                if (searchTimeout) clearTimeout(searchTimeout);
                fetchData();
            });

            // Select/dropdown changes trigger search
            [filterKelas, filterStatus, filterRekom, filterPetugas].forEach(function(el) {
                el?.addEventListener('change', function() {
                    if (searchTimeout) clearTimeout(searchTimeout);
                    fetchData();
                });
            });

            // Enter key on search input
            searchInput?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchTimeout) clearTimeout(searchTimeout);
                    fetchData();
                }
            });
        })();
    </script>
@endsection
