@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="mb-4">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Info Ujian</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
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
    </div>

    <div id="resultsContainer" class="grid gap-3">
        @php($groups = $items->groupBy(fn($item) => ($item->nama_ruangan ?? '-') . '|' . ($item->nama_sesi ?? '-')))
        @forelse($groups as $key => $rows)
            @php([$ruangan, $sesi] = explode('|', $key, 2))
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
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
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($rows as $item)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="text-base font-black leading-snug text-slate-950">{{ $item->nama }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $item->idyayasan }} /
                                {{ $item->nama_kelas }}</div>
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
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('resultsContainer');
        const filterLinks = document.querySelectorAll('[data-filter-ruangan]');

        let currentFilters = {
            ruangan: '{{ request('ruangan') }}'
        };

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
                html += `
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-slate-950">${group.ruangan}</h2>
                                <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-black text-indigo-700">${group.sesi}</span>
                                <p class="text-xs font-semibold text-slate-500">${group.waktu_mulai} - ${group.waktu_selesai}</p>
                            </div>
                            <span class="w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">${group.count} siswa</span>
                        </div>
                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                `;

                group.students.forEach(student => {
                    html += `
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="text-base font-black leading-snug text-slate-950">${student.nama}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">${student.idyayasan} / ${student.nama_kelas || '-'}</div>
                        </article>
                    `;
                });

                html += `
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
