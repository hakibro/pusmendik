@extends('layouts.app', ['title' => $title])

@php
    // Definisi warna status — dipakai seragam oleh kartu server-rendered maupun
    // kartu hasil live search, supaya kedua jalur tampil identik.
    // Status administrasi UJIAN tampil sebagai warna background + border kartu
    // (bukan chip), karena itu status paling utama di halaman ini.
    $statusKelas = [
        'lunas' => 'bg-emerald-50 border-emerald-200',
        'belum' => 'bg-rose-50 border-rose-200',
        'teks_lunas' => 'text-emerald-800',
        'teks_belum' => 'text-rose-800',
        'rekom' => 'bg-white text-teal-700 ring-1 ring-teal-200',
        'non_rekom' => 'bg-slate-100 text-slate-600',
    ];
    $card = 'rounded-2xl border p-3';
    $chip = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-black';
    $labelRekom = 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wide';
@endphp

@section('content')
    <div class="mb-4">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-500">Daftar siswa per ruangan dan sesi, lengkap dengan status administrasi ujian
            dan rekomendasinya.</p>
    </div>

    <div class="mb-4 space-y-3">
        {{-- Search Box --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
            <input type="text" id="searchInput" placeholder="Cari siswa (min 2 karakter)..."
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </div>

        {{-- Filter Labels --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('rooms.index') }}" data-filter-ruangan="" @class([
                    'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                    'bg-teal-600 text-white ring-teal-600' => !request('ruangan'),
                    'bg-white text-slate-700' => request('ruangan'),
                ])>Semua Ruangan</a>
                @foreach ($filterOptions['ruangan'] ?? [] as $ruangan)
                    <a href="{{ route('rooms.index', ['ruangan' => $ruangan]) }}" data-filter-ruangan="{{ $ruangan }}"
                        @class([
                            'shrink-0 rounded-2xl px-3 py-2 text-xs font-black ring-1 ring-slate-200',
                            'bg-teal-600 text-white ring-teal-600' => request('ruangan') === $ruangan,
                            'bg-white text-slate-700' => request('ruangan') !== $ruangan,
                        ])>{{ $ruangan }}</a>
                @endforeach
            </div>
        </div>

        {{-- Keterangan warna status --}}
        <div class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
            <span class="mr-1 text-[10px] font-black uppercase tracking-wide text-slate-400">Keterangan</span>
            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-black {{ $statusKelas['lunas'] }} {{ $statusKelas['teks_lunas'] }}">Administrasi
                Lunas</span>
            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-black {{ $statusKelas['belum'] }} {{ $statusKelas['teks_belum'] }}">Administrasi
                Belum Lunas</span>
            <span class="{{ $labelRekom }} {{ $statusKelas['rekom'] }}">
                <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.6 6.1 20.7l1.2-6.6L2.5 9.5l6.6-.9L12 2.5z" />
                </svg>
                Rekom
            </span>
        </div>
    </div>

    <div id="resultsContainer" class="grid gap-3">
        @php($groups = $items->groupBy(fn($item) => ($item->nama_ruangan ?? '-') . '|' . ($item->nama_sesi ?? '-')))
        @forelse($groups as $key => $rows)
            @php([$ruangan, $sesi] = explode('|', $key, 2))
            @php($lunas = $rows->filter(fn($row) => ($statusMap[$row->idyayasan]['status'] ?? $row->status_pembayaran) === 'Lunas')->count())
            @php($rekom = $rows->filter(fn($row) => ($row->rekomendasi ?: 'tidak') === 'ya')->count())
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col gap-2">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-slate-950">{{ $ruangan }}</h2>
                            <span
                                class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-black text-indigo-700">{{ $sesi }}</span>
                            <p class="text-xs font-semibold text-slate-500">{{ $rows->first()->waktu_mulai ?? '-' }}
                                - {{ $rows->first()->waktu_selesai ?? '-' }}</p>
                        </div>
                        <span
                            class="w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $rows->count() }}
                            siswa</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="{{ $chip }} {{ $statusKelas['lunas'] }} {{ $statusKelas['teks_lunas'] }}">{{ $lunas }}
                            lunas</span>
                        <span class="{{ $chip }} {{ $statusKelas['belum'] }} {{ $statusKelas['teks_belum'] }}">{{ $rows->count() - $lunas }} belum
                            lunas</span>
                        <span class="{{ $chip }} {{ $statusKelas['rekom'] }}">{{ $rekom }} rekom</span>
                    </div>
                </div>
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($rows as $item)
                        @php($uStatus = $statusMap[$item->idyayasan]['status'] ?? $item->status_pembayaran)
                        @php($lunasItem = $uStatus === 'Lunas')
                        @php($statusRekom = $item->rekomendasi ?: 'tidak')
                        <article @class([
                            $card,
                            $statusKelas['lunas'] => $lunasItem,
                            $statusKelas['belum'] => !$lunasItem,
                        ])>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-base font-black leading-snug text-slate-950">{{ $item->nama }}</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ $item->idyayasan }} /
                                        {{ $item->nama_kelas }}</div>
                                </div>
                                @if ($statusRekom === 'ya')
                                    <span class="{{ $labelRekom }} {{ $statusKelas['rekom'] }}">
                                        <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2.5l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.6 6.1 20.7l1.2-6.6L2.5 9.5l6.6-.9L12 2.5z" />
                                        </svg>
                                        Rekom
                                    </span>
                                @endif
                            </div>
                            <div
                                class="mt-2 text-xs font-black {{ $lunasItem ? $statusKelas['teks_lunas'] : $statusKelas['teks_belum'] }}">
                                {{ $lunasItem ? 'Lunas' : 'Belum Lunas' }}
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Data ruangan
                tidak ditemukan.</div>
        @endforelse
    </div>

    <script>
        const statusKelas = @json($statusKelas);
        const cardKelas = @json($card);
        const chip = @json($chip);
        const labelRekom = @json($labelRekom);

        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('resultsContainer');
        const filterLinks = document.querySelectorAll('[data-filter-ruangan]');

        let currentFilters = {
            ruangan: '{{ request('ruangan') }}'
        };

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        }

        const bintang = '<svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
            '<path d="M12 2.5l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.6 6.1 20.7l1.2-6.6L2.5 9.5l6.6-.9L12 2.5z"/></svg>';

        function studentCard(student) {
            const lunas = student.status_pembayaran === 'Lunas';
            const rekomYa = student.rekomendasi === 'ya';
            const cardClass = cardKelas + ' ' + (lunas ? statusKelas.lunas : statusKelas.belum);
            const teksClass = lunas ? statusKelas.teks_lunas : statusKelas.teks_belum;
            const label = rekomYa ?
                '<span class="' + labelRekom + ' ' + statusKelas.rekom + '">' + bintang + 'Rekom</span>' :
                '';

            return '<article class="' + cardClass + '">' +
                '<div class="flex items-start justify-between gap-2">' +
                '<div class="min-w-0">' +
                '<div class="text-base font-black leading-snug text-slate-950">' + escapeHtml(student.nama) +
                '</div>' +
                '<div class="mt-1 text-xs font-semibold text-slate-500">' + escapeHtml(student.idyayasan) + ' / ' +
                escapeHtml(student.nama_kelas || '-') + '</div>' +
                '</div>' + label +
                '</div>' +
                '<div class="mt-2 text-xs font-black ' + teksClass + '">' + (lunas ? 'Lunas' : 'Belum Lunas') +
                '</div>' +
                '</article>';
        }

        function performSearch() {
            const query = searchInput.value.trim();

            if (query.length > 0 && query.length < 2) return;

            const params = new URLSearchParams();
            if (query) params.append('q', query);
            if (currentFilters.ruangan) params.append('ruangan', currentFilters.ruangan);

            fetch(`{{ route('rooms.students.search') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsContainer.innerHTML =
                        '<div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Terjadi kesalahan saat memuat data.</div>';
                });
        }

        function displayResults(groups) {
            if (groups.length === 0) {
                resultsContainer.innerHTML =
                    '<div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Data ruangan tidak ditemukan.</div>';
                return;
            }

            let html = '';
            groups.forEach(group => {
                const lunas = group.students.filter(s => s.status_pembayaran === 'Lunas').length;
                const rekom = group.rekom_count ?? group.students.filter(s => s.rekomendasi === 'ya').length;

                html += `
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex flex-col gap-2">
                            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-black text-slate-950">${escapeHtml(group.ruangan)}</h2>
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-black text-indigo-700">${escapeHtml(group.sesi)}</span>
                                    <p class="text-xs font-semibold text-slate-500">${escapeHtml(group.waktu_mulai)} - ${escapeHtml(group.waktu_selesai)}</p>
                                </div>
                                <span class="w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">${group.count} siswa</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="${chip} ${statusKelas.lunas} ${statusKelas.teks_lunas}">${lunas} lunas</span>
                                <span class="${chip} ${statusKelas.belum} ${statusKelas.teks_belum}">${group.count - lunas} belum lunas</span>
                                <span class="${chip} ${statusKelas.rekom}">${rekom} rekom</span>
                            </div>
                        </div>
                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                            ${group.students.map(studentCard).join('')}
                        </div>
                    </section>
                `;
            });

            resultsContainer.innerHTML = html;
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });

        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                // Update active state
                filterLinks.forEach(l => {
                    l.classList.remove('bg-teal-600', 'text-white', 'ring-teal-600');
                    l.classList.add('bg-white', 'text-slate-700');
                });
                this.classList.remove('bg-white', 'text-slate-700');
                this.classList.add('bg-teal-600', 'text-white', 'ring-teal-600');

                // Update filter
                currentFilters.ruangan = this.dataset.filterRuangan;

                // Update URL without reload
                const url = new URL(window.location);
                if (currentFilters.ruangan) {
                    url.searchParams.set('ruangan', currentFilters.ruangan);
                } else {
                    url.searchParams.delete('ruangan');
                }
                window.history.pushState({}, '', url);

                // Perform search
                performSearch();
            });
        });
    </script>
@endsection
