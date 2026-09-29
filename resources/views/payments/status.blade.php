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
                    <span @class(['w-fit rounded-full px-3 py-1 text-xs font-black', 'bg-emerald-50 text-emerald-700' => ($statusAdmin ?? $student->status_pembayaran) === 'Lunas', 'bg-rose-50 text-rose-700' => ($statusAdmin ?? $student->status_pembayaran) !== 'Lunas'])>{{ $statusAdmin ?? $student->status_pembayaran }}</span>
                </div>
                @if(isset($paymentView['error']))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ $paymentView['error'] }}</div>
                @elseif($paymentView)
                    @php
                        $totalBill = (float) ($paymentView['total_bill'] ?? 0);
                        $totalPaid = (float) ($paymentView['total_paid'] ?? 0);
                        $tunggakanAktif = (float) ($paymentView['total_tunggakan_aktif'] ?? $paymentView['total_remaining'] ?? 0);
                        $belumJatuhTempo = (float) ($paymentView['total_belum_jatuh_tempo'] ?? 0);
                        $totalSisa = $tunggakanAktif + $belumJatuhTempo;
                        $persenAktif = $paymentView['persentase_tunggakan'] ?? ($totalSisa > 0 ? (int) round($tunggakanAktif / $totalSisa * 100) : null);
                        $persenBelum = $persenAktif !== null ? 100 - $persenAktif : null;
                        $jumlahItemAktif = collect($paymentView['tunggakan_aktif'] ?? [])->sum('item_count');
                        $jumlahItemBelum = collect($paymentView['tunggakan_belum_jatuh_tempo'] ?? [])->sum('item_count');
                        $sisaTunggakan = (float) ($paymentView['sisa_tunggakan'] ?? $tunggakanAktif);
                        $sumberSisa = $paymentView['sisa_tunggakan_sumber'] ?? 'jatuh_tempo';
                    @endphp
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Total Tagihan</div>
                            <div class="mt-2 text-2xl font-black text-slate-950">Rp {{ number_format($totalBill, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-400">Sejak periode awal sampai sekarang</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Sudah Dibayar</div>
                            <div class="mt-2 text-2xl font-black text-emerald-700">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-400">{{ $totalBill > 0 ? number_format($totalPaid / $totalBill * 100, 1, ',', '.') : '0' }}% dari total tagihan</div>
                        </div>
                        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5">
                            <div class="text-xs font-black uppercase tracking-wide text-rose-600">
                                {{ $sumberSisa === 'syarat_ujian' ? 'Sisa Tunggakan Ujian' : 'Sisa Tunggakan Jatuh Tempo (Ngalah Mobile)' }}
                            </div>
                            <div class="mt-2 text-2xl font-black text-rose-700">Rp {{ number_format($sisaTunggakan, 0, ',', '.') }}</div>
                            @if($sumberSisa === 'syarat_ujian')
                                <div class="mt-1 text-xs font-semibold text-rose-500">Dihitung dari syarat ujian (persen per pos). Tunggakan jatuh tempo (Ngalah Mobile) Rp {{ number_format($tunggakanAktif, 0, ',', '.') }}.</div>
                            @elseif($totalSisa > 0)
                                <div class="mt-1 text-xs font-semibold text-rose-500">{{ $persenAktif }}% dari total sisa Rp {{ number_format($totalSisa, 0, ',', '.') }}</div>
                            @else
                                <div class="mt-1 text-xs font-semibold text-emerald-600">Tidak ada sisa tagihan</div>
                            @endif
                        </div>
                    </div>

                    @if($totalSisa > 0)
                        <div class="mt-4 rounded-3xl border border-slate-200 bg-white p-5">
                            <div class="mb-3 flex flex-col justify-between gap-1 sm:flex-row sm:items-end">
                                <div>
                                    <h3 class="text-lg font-black text-slate-950">Komposisi Sisa Tagihan</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">Rincian <span class="font-bold">seluruh</span> sisa tagihan (Ngalah Mobile). Izin ujian dihitung terpisah dari <span class="font-bold">syarat ujian</span> per pos — lihat bagian Fokus Ujian.</p>
                                </div>
                                <div class="text-sm font-black text-slate-700">Total Sisa Rp {{ number_format($totalSisa, 0, ',', '.') }}</div>
                            </div>
                            <div class="flex h-4 w-full overflow-hidden rounded-full bg-slate-100">
                                @if($tunggakanAktif > 0)
                                    <div class="flex h-full items-center justify-center bg-rose-500 text-[10px] font-black text-white" style="width: {{ max($persenAktif, 6) }}%">{{ $persenAktif }}%</div>
                                @endif
                                @if($belumJatuhTempo > 0)
                                    <div class="flex h-full items-center justify-center bg-amber-300 text-[10px] font-black text-amber-900" style="width: {{ max($persenBelum, 6) }}%">{{ $persenBelum }}%</div>
                                @endif
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div class="flex items-start gap-3 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3">
                                    <span class="mt-0.5 h-3 w-3 shrink-0 rounded-full bg-rose-500"></span>
                                    <div class="text-sm">
                                        <div class="font-black text-rose-700">Tunggakan Aktif (Ngalah Mobile)</div>
                                        <div class="text-rose-700/90">Rp {{ number_format($tunggakanAktif, 0, ',', '.') }} <span class="text-xs font-semibold">• {{ $jumlahItemAktif }} item • {{ $persenAktif }}%</span></div>
                                        <div class="mt-0.5 text-xs font-semibold text-rose-500">Sudah jatuh tempo &amp; belum dilunasi.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3">
                                    <span class="mt-0.5 h-3 w-3 shrink-0 rounded-full bg-amber-400"></span>
                                    <div class="text-sm">
                                        <div class="font-black text-amber-800">Belum Jatuh Tempo</div>
                                        <div class="text-amber-800/90">Rp {{ number_format($belumJatuhTempo, 0, ',', '.') }} <span class="text-xs font-semibold">• {{ $jumlahItemBelum }} item • {{ $persenBelum }}%</span></div>
                                        <div class="mt-0.5 text-xs font-semibold text-amber-600">Tagihan berjalan, belum waktunya ditagih.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(($paymentView['ujian'] ?? null) || ($paymentView['tunggakan_aktif'] ?? []) !== [])
                        <div class="mt-4 rounded-3xl border border-teal-200 bg-teal-50/50 p-5">
                            <div class="mb-3 flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                <div>
                                    <h3 class="text-lg font-black text-teal-900">Fokus Ujian</h3>
                                    <p class="mt-0.5 text-xs text-teal-700/80">Item yang menentukan boleh/tidaknya ikut ujian. Persentase menunjukkan porsi tagihan yang wajib dipenuhi untuk pos tersebut.</p>
                                </div>
                                @if($paymentView['ujian'] ?? null)
                                    @php $statusUjian = $paymentView['ujian']; @endphp
                                    <div class="flex flex-col items-start gap-1 sm:items-end">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ ($statusUjian['lolos'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ ($statusUjian['lolos'] ?? false) ? 'Gerbang ujian terpenuhi' : 'Belum lolos gerbang ujian' }}</span>
                                        <span class="text-xs font-semibold text-teal-800">Kebijakan: {{ $statusUjian['nama'] ?? '-' }}
                                            @if($statusUjian['idperiode'] ?? null) • periode {{ $statusUjian['idperiode'] }} @endif
                                            • {{ $statusUjian['item_wajib'] ?? 0 }} item wajib
                                            @if(($statusUjian['item_belum_terpenuhi'] ?? 0) > 0) • <span
                                                class="font-black text-rose-600">{{ $statusUjian['item_belum_terpenuhi'] }} belum
                                                terpenuhi</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @php
                                $fokusBelum = collect($paymentView['fokus_ujian_belum'] ?? [])->values();
                            @endphp
                            @if($fokusBelum->isNotEmpty())
                                <div class="grid gap-2">
                                    @foreach($fokusBelum as $pos)
                                        <div class="flex flex-col justify-between gap-2 rounded-2xl border border-rose-200 bg-white px-4 py-3 sm:flex-row sm:items-center">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-black text-slate-900">{{ $pos['judul'] }}</span>
                                                    <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-black text-teal-700">Wajib {{ $pos['persen'] }}% untuk ujian</span>
                                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-black text-slate-600">Periode {{ $pos['periode'] }}</span>
                                                </div>
                                                <div class="mt-1 text-xs font-semibold text-slate-500">Tagihan Rp {{ number_format($pos['tagihan'], 0, ',', '.') }} • Dibayar Rp {{ number_format($pos['dibayar'], 0, ',', '.') }} • Wajib Rp {{ number_format($pos['wajib_bayar'], 0, ',', '.') }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-black text-rose-700">Kurang Rp {{ number_format($pos['kekurangan'], 0, ',', '.') }}</div>
                                                <div class="text-xs font-semibold text-slate-500">agar syarat ujian terpenuhi</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($paymentView['ujian'] ?? null)
                                <div class="rounded-2xl bg-emerald-50 px-4 py-4 text-center text-sm font-semibold text-emerald-700">Semua pos wajib ujian sudah terpenuhi.</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-5">
                        <div class="mb-4">
                            <h3 class="text-lg font-black text-slate-950">Data Pembayaran per Periode</h3>
                            <p class="mt-1 text-sm text-slate-500">Dikelompokkan berdasarkan periode, kategori, dan item pembayaran. Badge <span class="font-bold text-rose-700">Tunggakan Aktif</span> dan <span class="font-bold text-amber-700">Belum Jatuh Tempo</span> membantu membedakan mana yang menahan ujian.</p>
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
                                                            <span class="rounded-full bg-white px-3 py-1 text-slate-700">Tagihan Rp {{ number_format((float) ($category['summary']['total_bill'] ?? 0), 0, ',', '.') }}</span>
                                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Dibayar Rp {{ number_format((float) ($category['summary']['total_paid'] ?? 0), 0, ',', '.') }}</span>
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
                                                                <details @class([
                                                                    'rounded-2xl border bg-white',
                                                                    'border-rose-300' => ($item['status_sisa'] ?? '') === 'aktif',
                                                                    'border-slate-200' => ($item['status_sisa'] ?? '') !== 'aktif',
                                                                ])>
                                                                    <summary class="flex cursor-pointer list-none flex-col justify-between gap-2 px-4 py-3 sm:flex-row sm:items-center">
                                                                        <div>
                                                                            <div class="flex flex-wrap items-center gap-2">
                                                                                <span class="font-bold text-slate-900">{{ $item['unit'] }}</span>
                                                                                @if(($item['status_sisa'] ?? '') === 'aktif')
                                                                                    <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-black text-rose-700">Tunggakan Aktif</span>
                                                                                @elseif(($item['status_sisa'] ?? '') === 'belum_jatuh_tempo')
                                                                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-black text-amber-800">Belum Jatuh Tempo</span>
                                                                                @else
                                                                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-700">Lunas</span>
                                                                                @endif
                                                                                @if(($item['persen_ujian'] ?? null) !== null)
                                                                                    <span @class([
                                                                                        'rounded-full px-2.5 py-0.5 text-[11px] font-black',
                                                                                        'bg-teal-100 text-teal-700' => $item['terpenuhi_ujian'] ?? false,
                                                                                        'bg-rose-100 text-rose-700' => !($item['terpenuhi_ujian'] ?? false),
                                                                                    ])>Ujian {{ $item['persen_ujian'] }}% {{ ($item['terpenuhi_ujian'] ?? false) ? '✓' : '✗' }}</span>
                                                                                @elseif($item['item_ujian'] ?? false)
                                                                                    <span class="rounded-full bg-teal-50 px-2.5 py-0.5 text-[11px] font-black text-teal-700">Item Ujian</span>
                                                                                @endif
                                                                            </div>
                                                                            <div class="mt-1 text-xs font-semibold text-slate-500">Tanggal: {{ $item['journal_date'] }}@if($item['unit_nama'] ?? null) • {{ $item['unit_nama'] }}@endif</div>
                                                                        </div>
                                                                        <div class="flex flex-wrap gap-2 text-xs font-black">
                                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Bayar Rp {{ number_format($item['paid'], 0, ',', '.') }}</span>
                                                                            <span @class([
                                                                                'rounded-full px-3 py-1',
                                                                                'bg-rose-100 text-rose-700' => ($item['status_sisa'] ?? '') === 'aktif',
                                                                                'bg-amber-50 text-amber-700' => ($item['status_sisa'] ?? '') === 'belum_jatuh_tempo',
                                                                                'bg-slate-50 text-slate-500' => ($item['status_sisa'] ?? '') === 'lunas',
                                                                            ])>Sisa Rp {{ number_format($item['remaining'], 0, ',', '.') }}</span>
                                                                            @if(($item['kekurangan_ujian'] ?? null) !== null && $item['kekurangan_ujian'] > 0)
                                                                                <span class="rounded-full bg-rose-600 px-3 py-1 text-white">Kurang untuk ujian Rp {{ number_format($item['kekurangan_ujian'], 0, ',', '.') }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </summary>
                                                                    <div class="border-t border-slate-200 p-4">
                                                                        <div class="grid gap-2 sm:grid-cols-2">
                                                                            @foreach(($item['raw'] ?? []) as $key => $value)
                                                                                <div @class([
                                                                                    'rounded-xl px-3 py-2 text-sm',
                                                                                    'bg-rose-50 ring-1 ring-rose-100' => $key === 'item_ujian' && $value === true,
                                                                                    'bg-slate-50' => !($key === 'item_ujian' && $value === true),
                                                                                ])>
                                                                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $key === 'tgl_jurnal' ? 'tanggal' : $key }}</div>
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
