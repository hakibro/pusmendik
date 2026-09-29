<?php

namespace Tests\Unit;

use App\Services\ApiAkademikService;
use App\Services\PembayaranViewService;
use Tests\TestCase;

/**
 * Pemetaan respons apiakademik (bentuk datar) ke view-model pusmendik.
 */
class PembayaranViewServiceTest extends TestCase
{
    private function payload(): array
    {
        return [
            'siswa' => ['idperson' => '220361', 'nama' => 'Budi', 'nama_kelas' => 'XI BD 1'],
            'ringkasan' => [
                'total_kredit' => 1500000.0,
                'total_debet' => 1100000.0,
                'total_tunggakan' => 400000.0,
                'total_belum_jatuh_tempo' => 200000.0,
                'lunas' => false,
            ],
            'per_periode' => [
                ['idperiode' => '20262027', 'total_kredit' => 1000000.0, 'total_debet' => 600000.0, 'total_tunggakan' => 400000.0, 'total_item' => 2, 'lunas' => false],
                ['idperiode' => '20252026', 'total_kredit' => 500000.0, 'total_debet' => 500000.0, 'total_tunggakan' => 0.0, 'total_item' => 1, 'lunas' => true],
            ],
            'data' => [
                ['idperiode' => '20262027', 'ipsmain' => 'SPP', 'judul' => 'SPP', 'jml_kredit' => 600000.0, 'jml_debet' => 600000.0, 'selisih' => 0.0, 'lunas' => true, 'jatuh_tempo' => true, 'tgl_jurnal' => '2026-07-01', 'item_ujian' => true, 'persen_ujian' => 100, 'terpenuhi_ujian' => true, 'kekurangan_ujian' => 0.0],
                ['idperiode' => '20262027', 'ipsmain' => 'DU', 'judul' => 'DAFTAR ULANG', 'jml_kredit' => 400000.0, 'jml_debet' => 0.0, 'selisih' => 400000.0, 'lunas' => false, 'jatuh_tempo' => true, 'tgl_jurnal' => '2026-07-02', 'item_ujian' => true, 'persen_ujian' => 50, 'terpenuhi_ujian' => false, 'kekurangan_ujian' => 200000.0],
                ['idperiode' => '20262027', 'ipsmain' => 'OKT', 'judul' => 'OKTOBER', 'jml_kredit' => 200000.0, 'jml_debet' => 0.0, 'selisih' => 200000.0, 'lunas' => false, 'jatuh_tempo' => false, 'tgl_jurnal' => '2026-10-01'],
                ['idperiode' => '20252026', 'ipsmain' => 'SPP', 'judul' => 'SPP', 'jml_kredit' => 500000.0, 'jml_debet' => 500000.0, 'selisih' => 0.0, 'lunas' => true, 'jatuh_tempo' => true, 'tgl_jurnal' => '2025-07-01'],
            ],
        ];
    }

    private function service(): PembayaranViewService
    {
        return new PembayaranViewService(new ApiAkademikService);
    }

    public function test_totals_diambil_dari_ringkasan(): void
    {
        $vm = $this->service()->viewModel($this->payload());

        $this->assertSame(1500000.0, $vm['total_bill']);
        $this->assertSame(1100000.0, $vm['total_paid']);
        $this->assertSame(400000.0, $vm['total_remaining']);
        $this->assertCount(2, $vm['periods']);
    }

    public function test_periode_terbaru_di_depan_dan_kategori_per_pos(): void
    {
        $vm = $this->service()->viewModel($this->payload());
        $periode = $vm['periods'][0];

        $this->assertSame('20262027', $periode['period_id']);
        $this->assertSame('XI BD 1', $periode['kelas_info']);
        $this->assertSame(1000000.0, $periode['total_billed']);
        $this->assertSame(400000.0, $periode['total_remaining']);
        $this->assertCount(3, $periode['categories']);

        // Kategori "DAFTAR ULANG" berisi tagihan 400.000 yang belum dibayar.
        $du = collect($periode['categories'])->firstWhere('category_name', 'DAFTAR ULANG');
        $this->assertNotNull($du);
        $this->assertSame(400000.0, $du['summary']['total_bill']);
        $this->assertSame(400000.0, $du['summary']['total_remaining']);
    }

    public function test_item_amount_paid_remaining_konsisten(): void
    {
        $vm = $this->service()->viewModel($this->payload());
        $item = $vm['bills'][1];

        $this->assertSame('DAFTAR ULANG', $item['unit']);
        $this->assertSame(400000.0, $item['amount']);
        $this->assertSame(0.0, $item['paid']);
        $this->assertSame(400000.0, $item['remaining']);
        $this->assertSame('Belum Lunas', $item['payment_status']);
        $this->assertSame('Lunas', $vm['bills'][0]['payment_status']);
    }

    public function test_unpaid_periods_hanya_yang_bersisa(): void
    {
        $vm = $this->service()->viewModel($this->payload());

        $this->assertCount(1, $vm['unpaid_periods']);
        $this->assertSame('20262027', $vm['unpaid_periods'][0]['period_id']);

        // Hanya item bersisa yang disertakan (SPP lunas tidak ikut): DAFTAR ULANG + OKTOBER.
        $this->assertCount(2, $vm['unpaid_periods'][0]['items']);
        $units = collect($vm['unpaid_periods'][0]['items'])->pluck('unit')->all();
        $this->assertContains('DAFTAR ULANG', $units);
        $this->assertContains('OKTOBER', $units);
        $this->assertNotContains('SPP', $units);
    }

    public function test_tunggakan_aktif_dipisah_dari_belum_jatuh_tempo(): void
    {
        $vm = $this->service()->viewModel($this->payload());

        // 400rb jatuh tempo (DAFTAR ULANG), 200rb belum jatuh tempo (OKTOBER).
        $this->assertSame(400000.0, $vm['total_tunggakan_aktif']);
        $this->assertSame(200000.0, $vm['total_belum_jatuh_tempo']);
        // Persentase = 400rb / (400rb + 200rb) = 67%.
        $this->assertSame(67, $vm['persentase_tunggakan']);

        // Dikelompokkan per pos; hanya item jatuh tempo yang masuk "aktif".
        $this->assertCount(1, $vm['tunggakan_aktif']);
        $this->assertSame('DAFTAR ULANG', $vm['tunggakan_aktif'][0]['pos']);
        $this->assertSame(400000.0, $vm['tunggakan_aktif'][0]['total']);

        // Item belum jatuh tempo ada di kelompoknya sendiri.
        $this->assertCount(1, $vm['tunggakan_belum_jatuh_tempo']);
        $this->assertSame('OKTOBER', $vm['tunggakan_belum_jatuh_tempo'][0]['pos']);
        $this->assertSame(200000.0, $vm['tunggakan_belum_jatuh_tempo'][0]['total']);
    }

    public function test_item_menandai_status_sisa_dan_persen_ujian(): void
    {
        $vm = $this->service()->viewModel($this->payload());

        $du = collect($vm['bills'])->firstWhere('unit', 'DAFTAR ULANG');
        $this->assertSame('aktif', $du['status_sisa']);
        $this->assertSame('Tunggakan Aktif', $du['status_sisa_label']);
        $this->assertTrue($du['item_ujian']);
        $this->assertSame(50, $du['persen_ujian']);
        $this->assertFalse($du['terpenuhi_ujian']);
        $this->assertSame(200000.0, $du['kekurangan_ujian']);

        $okt = collect($vm['bills'])->firstWhere('unit', 'OKTOBER');
        $this->assertSame('belum_jatuh_tempo', $okt['status_sisa']);

        $spp = collect($vm['bills'])->firstWhere('unit', 'SPP');
        $this->assertSame('lunas', $spp['status_sisa']);
        $this->assertSame(100, $spp['persen_ujian']);
        $this->assertTrue($spp['terpenuhi_ujian']);
    }

    public function test_sisa_tunggakan_diambil_dari_syarat_ujian(): void
    {
        $payload = $this->payload();
        $payload['ujian'] = [
            'nama' => 'UTS1',
            'idperiode' => '20262027',
            'lolos' => false,
            'item_wajib' => 2,
            'item_belum_terpenuhi' => 1,
        ];

        $vm = $this->service()->viewModel($payload);

        // Sumber nilainya adalah syarat ujian, bukan tunggakan jatuh tempo (400rb).
        $this->assertSame('syarat_ujian', $vm['sisa_tunggakan_sumber']);
        // DAFTAR ULANG: tagihan 400rb × persen 50% = wajib 200rb, dibayar 0 -> kurang 200rb.
        // SPP: wajib 600rb, dibayar 600rb -> kurang 0.
        $this->assertSame(200000.0, $vm['sisa_tunggakan']);
        $this->assertSame(200000.0, $vm['sisa_tunggakan_syarat_ujian']);
        $this->assertSame(400000.0, $vm['sisa_tunggakan_jatuh_tempo']);

        // Rincian syarat ujian per pos.
        $this->assertCount(2, $vm['fokus_ujian']);
        $du = collect($vm['fokus_ujian'])->firstWhere('judul', 'DAFTAR ULANG');
        $this->assertSame(50, $du['persen']);
        $this->assertSame(400000.0, $du['tagihan']);
        $this->assertSame(200000.0, $du['wajib_bayar']);
        $this->assertSame(200000.0, $du['kekurangan']);
        $this->assertFalse($du['terpenuhi']);

        // Hanya pos belum terpenuhi yang masuk daftar fokus.
        $this->assertCount(1, $vm['fokus_ujian_belum']);
        $this->assertSame('DAFTAR ULANG', $vm['fokus_ujian_belum'][0]['judul']);
    }

    public function test_tanpa_kebijakan_ujian_sisa_tunggakan_fallback_ke_jatuh_tempo(): void
    {
        $vm = $this->service()->viewModel($this->payload());

        $this->assertSame('jatuh_tempo', $vm['sisa_tunggakan_sumber']);
        $this->assertNull($vm['sisa_tunggakan_syarat_ujian']);
        $this->assertSame(400000.0, $vm['sisa_tunggakan']);
    }
}
