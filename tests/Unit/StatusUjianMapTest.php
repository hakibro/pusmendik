<?php

namespace Tests\Unit;

use App\Services\ApiAkademikService;
use App\Services\PembayaranViewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Status administrasi ujian pussmendik harus memakai definisi tunggal dari gerbang
 * pembayaran ujian apiakademik (flag `terpenuhi`), bukan kolom `status_pembayaran`
 * di DB yang bisa basi/tak sinkron.
 */
class StatusUjianMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** Payload gerbang ujian per lembaga (bentuk `data` berupa daftar). */
    private function gatePayload(): array
    {
        return [
            'lembaga' => ['idunit' => '02', 'kode' => 'SMK'],
            'ujian' => ['id' => 3, 'nama' => 'UTS1', 'idperiode' => '20262027'],
            'item' => [['ipsmain' => 'DU', 'judul' => 'DAFTAR ULANG', 'persen' => 100]],
            'ringkasan' => ['total_siswa' => 3, 'total_terpenuhi' => 1],
            'data' => [
                ['idperson' => '220361', 'nama' => 'Budi', 'terpenuhi' => false, 'total_kekurangan' => 460000, 'item_belum_terpenuhi' => 3],
                ['idperson' => '220119', 'nama' => 'Ahmad', 'terpenuhi' => false, 'total_kekurangan' => 200000, 'item_belum_terpenuhi' => 1],
                ['idperson' => '240855', 'nama' => 'Lulus', 'terpenuhi' => true, 'total_kekurangan' => 0, 'item_belum_terpenuhi' => 0],
            ],
        ];
    }

    private function service(): PembayaranViewService
    {
        return new PembayaranViewService(new ApiAkademikService);
    }

    public function test_map_status_dibangun_dari_flag_terpenuhi(): void
    {
        Http::fake([
            '*/02/ujian/pembayaran*' => Http::response($this->gatePayload()),
            '*' => Http::response([], 404),
        ]);

        $map = $this->service()->statusUjianMap('02');

        $this->assertCount(3, $map);
        $this->assertSame(PembayaranViewService::STATUS_BELUM_LUNAS, $map['220361']['status']);
        $this->assertFalse($map['220361']['terpenuhi']);
        $this->assertSame(460000.0, $map['220361']['total_kekurangan']);
        $this->assertSame(3, $map['220361']['item_belum_terpenuhi']);
        $this->assertSame(PembayaranViewService::STATUS_LUNAS, $map['240855']['status']);
        $this->assertTrue($map['240855']['terpenuhi']);
    }

    public function test_status_siswa_menimpa_status_db_yang_basi(): void
    {
        Http::fake([
            '*/02/ujian/pembayaran*' => Http::response($this->gatePayload()),
            '*' => Http::response([], 404),
        ]);

        $map = $this->service()->statusUjianMap('02');

        // DB bilang "Lunas", gerbang bilang belum terpenuhi -> gerbang menang.
        $status = $this->service()->statusUjianSiswa('220361', $map, 'Lunas');
        $this->assertSame(PembayaranViewService::STATUS_BELUM_LUNAS, $status['status']);
        $this->assertSame('gerbang_ujian', $status['sumber']);

        // Gerbang bilang terpenuhi -> Lunas walau DB bilang belum.
        $status2 = $this->service()->statusUjianSiswa('240855', $map, 'Belum Lunas');
        $this->assertSame(PembayaranViewService::STATUS_LUNAS, $status2['status']);
    }

    public function test_siswa_tak_ada_di_gerbang_jatuh_ke_status_db(): void
    {
        Http::fake([
            '*/02/ujian/pembayaran*' => Http::response($this->gatePayload()),
            '*' => Http::response([], 404),
        ]);

        $map = $this->service()->statusUjianMap('02');
        $status = $this->service()->statusUjianSiswa('999999', $map, 'Belum Lunas');

        $this->assertSame('Belum Lunas', $status['status']);
        $this->assertSame('db', $status['sumber']);

        $this->assertNull($this->service()->statusUjianSiswa('999999', $map, null));
    }

    public function test_gerbang_mati_tidak_mematahkan_halaman(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertSame([], $this->service()->statusUjianMap('02'));

        $status = $this->service()->statusUjianSiswa('220361', [], 'Lunas');
        $this->assertSame('Lunas', $status['status']);
        $this->assertSame('db', $status['sumber']);
    }

    public function test_hasil_gerbang_di_cache(): void
    {
        Http::fake([
            '*/02/ujian/pembayaran*' => Http::response($this->gatePayload()),
            '*' => Http::response([], 404),
        ]);

        $this->service()->statusUjianMap('02');
        $this->service()->statusUjianMap('02');

        // Hanya satu panggilan HTTP meski dipanggil dua kali.
        Http::assertSentCount(1);
    }
}
