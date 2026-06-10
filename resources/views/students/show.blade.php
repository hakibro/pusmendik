@extends('layouts.app', ['title' => 'Detail Rekom'])

@php
    $formatValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'ya' : 'tidak';
        }
        if (is_numeric($value)) {
            return str_contains((string) $value, '.')
                ? number_format((float) $value, 2, ',', '.')
                : number_format((float) $value, 0, ',', '.');
        }
        return $value === null || $value === '' ? '-' : (string) $value;
    };
    $formatDate = fn($value) => $value ? \Illuminate\Support\Carbon::parse($value)->translatedFormat('d M Y') : '-';
    $formatTime = fn($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('H:i') : '-';
@endphp

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('students.index') }}" class="text-sm font-black text-teal-700 hover:text-teal-600">Kembali ke
                Rekom</a>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $student->nama }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $student->idyayasan }} / {{ $student->nama_kelas }} /
                {{ $student->status_pembayaran }}</p>
        </div>
        <a class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:bg-teal-700"
            href="{{ route('students.print', $student->id) }}" target="_blank">Cetak Surat</a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Total Tagihan</div>
            <div class="mt-2 text-2xl font-black text-slate-950">Rp
                {{ number_format($paymentView['total_bill'], 0, ',', '.') }}</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Sudah Dibayar</div>
            <div class="mt-2 text-2xl font-black text-emerald-700">Rp
                {{ number_format($paymentView['total_paid'], 0, ',', '.') }}</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Sisa Tunggakan</div>
            <div class="mt-2 text-2xl font-black text-rose-700">Rp
                {{ number_format($paymentView['total_remaining'], 0, ',', '.') }}</div>
        </div>
    </div>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h2 class="text-xl font-black">Informasi Ujian Aktif</h2>
                <p class="mt-1 text-sm text-slate-500">Data diambil dari tahun ajaran aktif dan paket ujian aktif.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-black">
                <span class="rounded-full bg-teal-50 px-3 py-1 text-teal-700">TA:
                    {{ $examInfo['academicYear']->nama ?? '-' }}</span>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">Paket:
                    {{ $examInfo['examPackage']->nama ?? '-' }}</span>
            </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="font-black text-slate-950">Ruangan & Sesi</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($examInfo['roomSessions'] as $roomSession)
                        <div class="px-4 py-3 text-sm">
                            <div class="font-black text-slate-900">{{ $roomSession->nama_ruangan ?? '-' }}</div>
                            <div class="mt-1 font-semibold text-slate-600">{{ $roomSession->nama_sesi ?? '-' }} /
                                {{ $formatTime($roomSession->waktu_mulai) }}-{{ $formatTime($roomSession->waktu_selesai) }}
                            </div>
                            <div class="mt-2 text-xs font-black text-teal-700">
                                {{ $roomSession->status_kehadiran ?? 'belum diisi' }}</div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">Belum ada ruangan/sesi aktif.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="font-black text-slate-950">Jadwal & Kehadiran</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($examInfo['attendance'] as $attendance)
                        <div class="px-4 py-3 text-sm">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-400">
                                {{ $formatDate($attendance->tanggal) }}</div>
                            <div class="mt-1 font-black text-slate-900">{{ $attendance->judul ?? '-' }}</div>
                            <div class="mt-1 font-semibold text-slate-600">{{ $attendance->nama_mapel ?? '-' }} /
                                {{ $attendance->nama_ruangan ?? '-' }} / {{ $attendance->nama_sesi ?? '-' }}</div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-black">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Hadir:
                                    {{ $attendance->status_kehadiran ?? 'belum diisi' }}</span>
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">Enroll:
                                    {{ $attendance->status_enrollment ?? '-' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">Belum ada jadwal aktif untuk siswa ini.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="font-black text-slate-950">Hasil Ujian</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($examInfo['results'] as $result)
                        <div class="px-4 py-3 text-sm">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-400">
                                {{ $formatDate($result->tanggal) }}</div>
                            <div class="mt-1 font-black text-slate-900">{{ $result->judul ?? '-' }}</div>
                            <div class="mt-1 font-semibold text-slate-600">{{ $result->nama_mapel ?? '-' }} /
                                {{ $result->nama_ruangan ?? '-' }} / {{ $result->nama_sesi ?? '-' }}</div>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs font-black">
                                <span class="rounded-xl bg-slate-100 px-3 py-2 text-slate-700">Nilai:
                                    {{ $formatValue($result->nilai) }}</span>
                                <span class="rounded-xl bg-slate-100 px-3 py-2 text-slate-700">Status:
                                    {{ $result->status ?? '-' }}</span>
                                <span class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-700">Benar:
                                    {{ $formatValue($result->jumlah_benar) }}</span>
                                <span class="rounded-xl bg-rose-50 px-3 py-2 text-rose-700">Salah:
                                    {{ $formatValue($result->jumlah_salah) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">Belum ada hasil ujian aktif.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_.8fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-black">Data Pembayaran per Periode</h2>
                <p class="mt-1 text-sm text-slate-500">Struktur mengikuti JSON API: periods, summary, categories, dan items.
                </p>
            </div>

            <div class="grid gap-4">
                @forelse($paymentView['periods'] as $period)
                    <details
                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white open:border-teal-200">
                        <summary
                            class="flex cursor-pointer list-none flex-col justify-between gap-2 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center">
                            <div>
                                <div class="font-black text-slate-950">Periode {{ $period['period_id'] }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">{{ $period['kelas_info'] }}</div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs font-black">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Tagihan Rp
                                    {{ number_format($period['total_billed'], 0, ',', '.') }}</span>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Dibayar Rp
                                    {{ number_format($period['total_paid'], 0, ',', '.') }}</span>
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa Rp
                                    {{ number_format($period['total_remaining'], 0, ',', '.') }}</span>
                            </div>
                        </summary>

                        <div class="border-t border-slate-200 p-4">
                            <div class="mb-4 rounded-2xl bg-slate-50 p-4">
                                <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Summary</div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @forelse(($period['summary'] ?? []) as $key => $value)
                                        <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                {{ $key }}</div>
                                            <div class="mt-1 font-black text-slate-800">{{ $formatValue($value) }}</div>
                                        </div>
                                    @empty
                                        <div class="text-sm text-slate-500">Summary tidak tersedia.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="grid gap-3">
                                @forelse(($period['categories'] ?? []) as $category)
                                    <details class="group/category overflow-hidden rounded-2xl border border-slate-200">
                                        <summary
                                            class="flex cursor-pointer list-none flex-col justify-between gap-2 bg-white px-4 py-3 sm:flex-row sm:items-center">
                                            <div class="font-black text-slate-950">{{ $category['category_name'] }}</div>
                                            <div class="text-xs font-bold text-slate-500">{{ count($category['items']) }}
                                                item</div>
                                        </summary>
                                        <div class="border-t border-slate-200 bg-slate-50 p-4">
                                            <div class="mb-3 flex flex-wrap gap-2 text-xs font-black">
                                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Tagihan Rp
                                                    {{ number_format((float) ($category['summary']['total_paid'] ?? 0), 0, ',', '.') }}</span>
                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Dibayar
                                                    Rp
                                                    {{ number_format((float) ($category['summary']['total_billed'] ?? 0), 0, ',', '.') }}</span>
                                                <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa Rp
                                                    {{ number_format(abs((float) ($category['summary']['total_remaining'] ?? 0)), 0, ',', '.') }}</span>
                                            </div>
                                            <div class="mb-4 grid gap-2 sm:grid-cols-3">
                                                @forelse(($category['summary'] ?? []) as $key => $value)
                                                    <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                                        <div
                                                            class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                            {{ $key }}</div>
                                                        <div class="mt-1 font-black text-slate-800">
                                                            {{ $formatValue($value) }}</div>
                                                    </div>
                                                @empty
                                                    <div class="text-sm text-slate-500">Summary kategori tidak tersedia.
                                                    </div>
                                                @endforelse
                                            </div>

                                            <div class="grid gap-2">
                                                @forelse(($category['items'] ?? []) as $item)
                                                    <details class="rounded-2xl border border-slate-200 bg-white">
                                                        <summary
                                                            class="flex cursor-pointer list-none flex-col justify-between gap-2 px-4 py-3 sm:flex-row sm:items-center">
                                                            <div>
                                                                <div class="font-bold text-slate-900">{{ $item['unit'] }}
                                                                </div>
                                                                <div class="mt-1 text-xs font-semibold text-slate-500">
                                                                    Status: {{ $item['payment_status'] }} / Jurnal:
                                                                    {{ $item['journal_date'] }}</div>
                                                            </div>
                                                            <div class="flex flex-wrap gap-2 text-xs font-black">
                                                                <span
                                                                    class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Rp
                                                                    {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                                <span
                                                                    class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Bayar
                                                                    Rp
                                                                    {{ number_format($item['paid'], 0, ',', '.') }}</span>
                                                                <span
                                                                    class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Sisa
                                                                    Rp
                                                                    {{ number_format($item['remaining'], 0, ',', '.') }}</span>
                                                            </div>
                                                        </summary>
                                                        <div class="border-t border-slate-200 p-4">
                                                            <div class="grid gap-2 sm:grid-cols-2">
                                                                @foreach ($item['raw'] ?? [] as $key => $value)
                                                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                                                        <div
                                                                            class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                                            {{ $key }}</div>
                                                                        <div class="mt-1 font-semibold text-slate-800">
                                                                            {{ is_scalar($value) || $value === null ? $formatValue($value) : json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </details>
                                                @empty
                                                    <div
                                                        class="rounded-2xl bg-white px-4 py-6 text-center text-sm text-slate-500">
                                                        Items tidak tersedia.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </details>
                                @empty
                                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                        Categories tidak tersedia.</div>
                                @endforelse
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-10 text-center text-slate-500">Tidak
                        ada data periods yang dapat ditampilkan.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black">Penanganan Rekom</h2>
            <p class="mt-1 text-sm text-slate-500">Catatan otomatis akan menjadi wali membayar Rp. nominal.</p>
            <form method="post" action="{{ route('students.recommendation', $student->id) }}" class="mt-5 grid gap-4">
                @csrf
                <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Nominal Rekom
                    <input type="text" name="nominal_rekom" id="nominal-rekom-input" inputmode="numeric"
                        value="{{ old('nominal_rekom', $handler ? (int) $handler->nominal_rekom : '') }}" required
                        class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                </label>
                <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Catatan Tambahan
                    <textarea name="catatan"
                        class="min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('catatan') }}</textarea>
                </label>
                @if ($handler)
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        Terakhir ditangani oleh <strong
                            class="text-slate-900">{{ $handler->handled_by_name ?? 'Petugas' }}</strong> dengan nominal
                        <strong class="text-slate-900">Rp
                            {{ number_format((float) $handler->nominal_rekom, 0, ',', '.') }}</strong>.
                    </div>
                @endif
                <button
                    class="rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Simpan
                    Rekomendasi</button>
            </form>
        </section>
    </div>
    <script>
        (function() {
            const input = document.getElementById('nominal-rekom-input');
            if (!input) return;

            // Format existing value on load
            input.value = formatRupiah(unformatRupiah(input.value));

            input.addEventListener('input', function() {
                const raw = unformatRupiah(this.value);
                this.value = formatRupiah(raw);
            });

            input.closest('form').addEventListener('submit', function() {
                // Strip separators so server receives clean integer
                input.value = unformatRupiah(input.value);
            });

            function formatRupiah(value) {
                if (!value) return '';
                const num = parseInt(value, 10);
                if (isNaN(num) || num < 0) return '';
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function unformatRupiah(value) {
                if (!value) return '';
                // Ambil bagian integer saja (buang koma/titik desimal)
                const cleaned = value.replace(/\./g, '').replace(/,.*$/, '');
                return cleaned.replace(/[^\d]/g, '');
            }
        })();
    </script>
@endsection
