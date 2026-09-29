<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Menyusun tampilan pembayaran pusmendik dari respons API akademik.
 *
 * apiakademik mengembalikan bentuk datar per pos (`data` + `per_periode` + `ringkasan`),
 * sedangkan blade pusmendik memakai struktur `periods -> categories -> items`. Service ini
 * yang menjembatani keduanya, jadi tidak ada rumus pembayaran yang ditulis ulang di view.
 */
class PembayaranViewService
{
    public const STATUS_LUNAS = 'Lunas';

    public const STATUS_BELUM_LUNAS = 'Belum Lunas';

    public function __construct(private ApiAkademikService $api) {}

    /**
     * Peta `idyayasan` => status administrasi ujian untuk SELURUH siswa lembaga,
     * diambil dari gerbang pembayaran ujian apiakademik (bentuk `data` berupa daftar).
     *
     * Definisi status sengaja disamakan dengan nilai "Sisa Tunggakan Ujian": siswa
     * dianggap `Lunas` hanya bila tidak ada pos wajib ujian yang belum terpenuhi
     * (flag `terpenuhi`). Inilah definisi tunggal yang dipakai halaman daftar,
     * sehingga status tidak bisa lagi menyimpang dari "Sisa Tunggakan Ujian".
     *
     * Hasil di-cache singkat (5 menit) karena responsnya besar (~335 KB) dan
     * dipanggil pada setiap pemuatan daftar.
     *
     * @return array<string, array{terpenuhi: bool, total_kekurangan: float, item_belum_terpenuhi: int, status: string}>
     */
    public function statusUjianMap(?string $idunit = null): array
    {
        $idunit = $idunit ?: $this->api->idunit();
        $cacheKey = 'pusmendik:ujian-gate:'.$idunit.':'.sha1($this->api->baseUrl());

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($idunit) {
            $result = $this->api->ujianPembayaran($idunit);

            if (! $result['success']) {
                return [];
            }

            $map = [];

            foreach (($result['data']['data'] ?? []) as $row) {
                $idperson = (string) ($row['idperson'] ?? '');

                if ($idperson === '') {
                    continue;
                }

                $terpenuhi = (bool) ($row['terpenuhi'] ?? false);

                $map[$idperson] = [
                    'terpenuhi' => $terpenuhi,
                    'total_kekurangan' => (float) ($row['total_kekurangan'] ?? 0),
                    'item_belum_terpenuhi' => (int) ($row['item_belum_terpenuhi'] ?? 0),
                    'status' => $terpenuhi ? self::STATUS_LUNAS : self::STATUS_BELUM_LUNAS,
                ];
            }

            return $map;
        });
    }

    /**
     * Status administrasi ujian seorang siswa dari peta gerbang (fallback
     * `status_pembayaran` di DB bila siswa tidak ada di gerbang).
     *
     * @param  array<string, array{terpenuhi: bool, total_kekurangan: float, item_belum_terpenuhi: int, status: string}>  $map
     * @return array{status: string, sumber: string, kekurangan: float, item_belum_terpenuhi: int}|null
     */
    public function statusUjianSiswa(?string $idyayasan, array $map, ?string $fallbackDb = null): ?array
    {
        if ($idyayasan !== null && isset($map[$idyayasan])) {
            $row = $map[$idyayasan];

            return [
                'status' => $row['status'],
                'sumber' => 'gerbang_ujian',
                'kekurangan' => $row['total_kekurangan'],
                'item_belum_terpenuhi' => $row['item_belum_terpenuhi'],
            ];
        }

        if ($fallbackDb === null) {
            return null;
        }

        return [
            'status' => $fallbackDb,
            'sumber' => 'db',
            'kekurangan' => 0.0,
            'item_belum_terpenuhi' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{data: array<mixed>, error: string|null}
     */
    public function detail(string $idyayasan, array $query = []): array
    {
        $result = $this->api->pembayaranSiswa($idyayasan, $query);

        if (! $result['success']) {
            return ['data' => [], 'error' => $result['error'] ?? 'Gagal mengambil data pembayaran dari API akademik.'];
        }

        return ['data' => $result['data'] ?? [], 'error' => null];
    }

    /**
     * Bangun view-model pembayaran (total_bill/total_paid/total_remaining/periods/unpaid_periods/bills).
     *
     * @param  array<string, mixed>  $payload  respons `/siswa/{idperson}/pembayaran`
     * @return array<string, mixed>
     */
    public function viewModel(array $payload): array
    {
        $siswa = $payload['siswa'] ?? [];
        $ringkasan = $payload['ringkasan'] ?? [];
        $perPeriode = collect($payload['per_periode'] ?? []);
        $items = collect($payload['data'] ?? []);

        // Dikelompokkan per periode. "Tagihan" = total kredit, "Dibayar" = total debet,
        // "Sisa" = tunggakan jurnal (item jatuh tempo yang belum lunas) — konsisten dengan
        // perhitungan gerbang ujian (tagihan - dibayar per pos, minimum 0).
        $periods = $items
            ->groupBy('idperiode')
            ->map(function (Collection $rows, $idperiode) use ($siswa, $perPeriode) {
                $per = $perPeriode->firstWhere('idperiode', $idperiode) ?? [];

                $categories = $rows
                    ->groupBy(fn ($row) => $row['judul'] ?: ($row['ipsmain'] ?? 'Tagihan'))
                    ->map(fn (Collection $categoryRows, $categoryName) => [
                        'category_name' => $categoryName,
                        'summary' => $this->summary($categoryRows, true),
                        'items' => $categoryRows->map(fn ($row) => $this->bill($row, $categoryName))->values()->all(),
                        'raw' => $categoryRows->values()->all(),
                    ])
                    ->values()
                    ->all();

                return [
                    'period_id' => (string) $idperiode,
                    'kelas_info' => $siswa['nama_kelas'] ?? '-',
                    'summary' => [
                        'total_bill' => (float) ($per['total_kredit'] ?? $rows->sum('jml_kredit')),
                        'total_paid' => (float) ($per['total_debet'] ?? $rows->sum('jml_debet')),
                        'total_remaining' => (float) ($per['total_tunggakan'] ?? $this->selisihJatuhTempo($rows)),
                        'total_item' => (int) ($per['total_item'] ?? $rows->count()),
                        'lunas' => (bool) ($per['lunas'] ?? $this->selisihJatuhTempo($rows) <= 0),
                    ],
                    'total_billed' => (float) ($per['total_kredit'] ?? $rows->sum('jml_kredit')),
                    'total_paid' => (float) ($per['total_debet'] ?? $rows->sum('jml_debet')),
                    'total_remaining' => (float) ($per['total_tunggakan'] ?? $this->selisihJatuhTempo($rows)),
                    'categories' => $categories,
                    'items' => $rows->map(fn ($row) => $this->bill($row))->values()->all(),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $totalBill = (float) ($ringkasan['total_kredit'] ?? $items->sum('jml_kredit'));
        $totalPaid = (float) ($ringkasan['total_debet'] ?? $items->sum('jml_debet'));
        $totalRemaining = (float) ($ringkasan['total_tunggakan'] ?? $this->selisihJatuhTempo($items));
        $totalBelumJatuhTempo = (float) ($ringkasan['total_belum_jatuh_tempo'] ?? $this->belumJatuhTempo($items));

        // Tunggakan syarat ujian = kekurangan untuk memenuhi gerbang ujian, dinilai
        // per pos seperti apiakademik: max(tagihan * persen/100 - dibayar, 0).
        // Inilah nilai yang menentukan boleh/tidaknya ikut ujian, jadi dipakai
        // sebagai "Sisa Tunggakan" utama (bukan tunggakan jatuh tempo jurnal).
        $ujian = $payload['ujian'] ?? null;
        $fokusUjian = $this->fokusUjian($items);
        $sisaSyaratUjian = $ujian !== null ? (float) collect($fokusUjian)->sum('kekurangan') : null;
        $sisaTunggakan = $sisaSyaratUjian ?? $totalRemaining;

        return [
            'total_remaining' => $totalRemaining,
            'total_bill' => $totalBill,
            'total_paid' => $totalPaid,
            // Tunggakan aktif = jatuh tempo & bersisa (jurnal). Belum jatuh tempo =
            // tagihan berjalan yang belum waktunya ditagih.
            'total_tunggakan_aktif' => $totalRemaining,
            'total_belum_jatuh_tempo' => $totalBelumJatuhTempo,
            'persentase_tunggakan' => $this->persentase($totalRemaining, $totalBelumJatuhTempo),

            // Nilai "Sisa Tunggakan" yang ditampilkan: dari syarat ujian bila ada
            // kebijakan ujian, jika tidak fallback ke tunggakan jatuh tempo.
            'sisa_tunggakan' => $sisaTunggakan,
            'sisa_tunggakan_sumber' => $sisaSyaratUjian !== null ? 'syarat_ujian' : 'jatuh_tempo',
            'sisa_tunggakan_syarat_ujian' => $sisaSyaratUjian,
            'sisa_tunggakan_jatuh_tempo' => $totalRemaining,
            'fokus_ujian' => $fokusUjian,
            'fokus_ujian_belum' => collect($fokusUjian)->where('terpenuhi', false)->values()->all(),

            'periods' => $periods->all(),
            'unpaid_periods' => $this->unpaidPeriods($periods->all()),
            'bills' => $items->map(fn ($row) => $this->bill($row))->values()->all(),
            'tunggakan_aktif' => $this->kelompokBills($items, 'aktif'),
            'tunggakan_belum_jatuh_tempo' => $this->kelompokBills($items, 'belum_jatuh_tempo'),
            'ujian' => $ujian,
            'raw_summary' => $ringkasan,
            'raw_payments' => [$payload],
        ];
    }

    /**
     * Rincian syarat ujian per pos (digabung per `ipsmain`, bukan per baris jurnal,
     * supaya persen tidak terhitung berulang bila satu pos punya beberapa jurnal).
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function fokusUjian(Collection $items): array
    {
        return $items
            ->filter(fn ($row) => ! empty($row['item_ujian']))
            ->groupBy('ipsmain')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $tagihan = (float) $rows->sum('jml_kredit');
                $dibayar = (float) $rows->sum('jml_debet');
                $persen = (int) ($first['persen_ujian'] ?? 100);
                $wajibBayar = $tagihan * $persen / 100;

                return [
                    'ipsmain' => $first['ipsmain'] ?? '-',
                    'judul' => $first['judul'] ?? '-',
                    'nama_unit' => $first['nama_unit'] ?? null,
                    'periode' => (string) ($first['idperiode'] ?? '-'),
                    'persen' => $persen,
                    'tagihan' => $tagihan,
                    'dibayar' => $dibayar,
                    'wajib_bayar' => $wajibBayar,
                    'kekurangan' => max($wajibBayar - $dibayar, 0),
                    'terpenuhi' => $dibayar >= $wajibBayar,
                ];
            })
            ->sortByDesc('kekurangan')
            ->values()
            ->all();
    }

    /**
     * Hanya periode/kategori/item yang masih bersisa, untuk surat.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @return array<int, array<string, mixed>>
     */
    public function unpaidPeriods(array $periods): array
    {
        return collect($periods)
            ->filter(fn ($period) => (float) ($period['total_remaining'] ?? 0) > 0)
            ->map(function ($period) {
                $period['categories'] = collect($period['categories'] ?? [])
                    ->map(function ($category) {
                        $category['items'] = collect($category['items'] ?? [])
                            ->filter(fn ($item) => (float) ($item['remaining'] ?? 0) > 0)
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                $period['items'] = collect($period['items'] ?? [])
                    ->filter(fn ($item) => (float) ($item['remaining'] ?? 0) > 0)
                    ->values()
                    ->all();

                return $period;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function bill(array $row, ?string $categoryName = null): array
    {
        $tagihan = (float) ($row['jml_kredit'] ?? 0);
        $dibayar = (float) ($row['jml_debet'] ?? 0);
        $remaining = max((float) ($row['selisih'] ?? ($tagihan - $dibayar)), 0);
        $lunas = ! empty($row['lunas']);
        $jatuhTempo = ! empty($row['jatuh_tempo']);

        // Klasifikasi yang ditampilkan ke pengguna:
        // - Lunas                          : tidak ada sisa.
        // - Tunggakan Aktif (jatuh tempo)  : sisa > 0 dan sudah jatuh tempo -> menahan ujian.
        // - Belum Jatuh Tempo              : sisa > 0 tapi belum waktunya ditagih.
        if ($lunas || $remaining <= 0) {
            $statusSisa = 'lunas';
            $statusLabel = 'Lunas';
        } elseif ($jatuhTempo) {
            $statusSisa = 'aktif';
            $statusLabel = 'Tunggakan Aktif';
        } else {
            $statusSisa = 'belum_jatuh_tempo';
            $statusLabel = 'Belum Jatuh Tempo';
        }

        $itemUjian = (bool) ($row['item_ujian'] ?? false);
        $persenUjian = $row['persen_ujian'] ?? null;
        $terpenuhiUjian = $row['terpenuhi_ujian'] ?? null;

        return [
            'name' => $categoryName ?? ($row['judul'] ?? '-'),
            'period' => (string) ($row['idperiode'] ?? '-'),
            'kelas_info' => '-',
            'unit' => $row['judul'] ?? ($row['ipsmain'] ?? '-'),
            'unit_nama' => $row['nama_unit'] ?? null,
            'amount' => $tagihan,
            'paid' => $dibayar,
            'remaining' => $remaining,
            'journal_date' => $row['tgl_jurnal'] ?? '-',
            'last_updated' => $row['tgl_jurnal'] ?? '-',
            'payment_status' => $lunas ? 'Lunas' : 'Belum Lunas',
            'jatuh_tempo' => $jatuhTempo,
            'lunas' => $lunas,
            'status_sisa' => $statusSisa,
            'status_sisa_label' => $statusLabel,
            // Penanda ujian (ada bila request memakai ?ujian=1).
            'item_ujian' => $itemUjian,
            'persen_ujian' => $persenUjian !== null ? (int) $persenUjian : null,
            'terpenuhi_ujian' => $terpenuhiUjian !== null ? (bool) $terpenuhiUjian : null,
            'kekurangan_ujian' => isset($row['kekurangan_ujian']) ? (float) $row['kekurangan_ujian'] : null,
            'raw' => $row,
        ];
    }

    /**
     * Daftar item tunggakan menurut klasifikasi: `aktif` (jatuh tempo) atau
     * `belum_jatuh_tempo`. Dikelompokkan per pos (judul) agar rapi.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array{pos: string, item_count: int, total: float, items: array<int, array<string, mixed>>}>
     */
    private function kelompokBills(Collection $items, string $klasifikasi): array
    {
        return $items
            ->map(fn ($row) => $this->bill($row))
            ->filter(fn ($bill) => $bill['status_sisa'] === $klasifikasi)
            ->groupBy('unit')
            ->map(fn (Collection $group, $pos) => [
                'pos' => $pos ?: 'Lainnya',
                'item_count' => $group->count(),
                'total' => (float) $group->sum('remaining'),
                'items' => $group->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Persentase tunggakan aktif terhadap total (aktif + belum jatuh tempo).
     */
    private function persentase(float $aktif, float $belumJatuhTempo): ?int
    {
        $total = $aktif + $belumJatuhTempo;

        return $total > 0 ? (int) round($aktif / $total * 100) : null;
    }

    /**
     * Tunggakan jurnal: hanya item jatuh tempo yang belum lunas, jumlahkan sisa positif.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function selisihJatuhTempo(Collection $rows): float
    {
        return (float) $rows
            ->where('jatuh_tempo', true)
            ->where('lunas', false)
            ->sum(fn ($row) => max((float) ($row['selisih'] ?? 0), 0));
    }

    /**
     * Tagihan yang belum jatuh tempo dan belum lunas (sisa positif).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function belumJatuhTempo(Collection $rows): float
    {
        return (float) $rows
            ->where('jatuh_tempo', false)
            ->where('lunas', false)
            ->sum(fn ($row) => max((float) ($row['selisih'] ?? 0), 0));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float|int>
     */
    private function summary(Collection $rows, bool $withPaid = false): array
    {
        $summary = [
            'total_bill' => (float) $rows->sum('jml_kredit'),
            'total_remaining' => $this->selisihJatuhTempo($rows),
            'total_item' => $rows->count(),
        ];

        if ($withPaid) {
            $summary['total_paid'] = (float) $rows->sum('jml_debet');
        }

        return $summary;
    }
}
