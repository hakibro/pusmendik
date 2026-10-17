<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Export kehadiran per hari (`GET /kehadiran/download`).
 *
 * Aturan produk yang dikunci di sini:
 * 1. Export HANYA memakai filter `tanggal`; filter lain di halaman (q, tingkat,
 *    kelas, sesi, ruangan, status_kehadiran) SENGAJA diabaikan supaya berkas
 *    berisi seluruh siswa yang ujian pada hari itu.
 * 2. Status administrasi ujian diambil dari gerbang pembayaran apiakademik
 *    (`ujianStatusForStudents`), BUKAN dari `siswa_tahun_ajaran.status_pembayaran`
 *    yang bisa basi — keputusan yang sama dengan halaman Rekom.
 */
class AttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    private const TANGGAL = '2026-10-15';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.exam', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('exam');

        $this->createExamSchema();
        $this->seedExamData();

        // Gerbang pembayaran ujian: siswa S1 lunas menurut gerbang walau kolom DB
        // menyebut "Belum Lunas"; S2 tidak ada di gerbang sehingga jatuh ke DB.
        Http::fake([
            '*/ujian/pembayaran*' => Http::response([
                'data' => [[
                    'idperson' => 'S1',
                    'terpenuhi' => true,
                    'total_kekurangan' => 0,
                    'item_belum_terpenuhi' => 0,
                ]],
            ]),
        ]);
    }

    private function createExamSchema(): void
    {
        $schema = Schema::connection('exam');

        $schema->create('tahun_ajaran', function ($table) {
            $table->bigIncrements('id');
            $table->boolean('is_active')->default(false);
            $table->string('status')->nullable();
        });

        $schema->create('paket_ujian', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->string('status')->nullable();
        });

        $schema->create('siswa', function ($table) {
            $table->bigIncrements('id');
            $table->string('idyayasan');
            $table->string('nama');
            $table->string('nis')->nullable();
            $table->string('nama_kelas')->nullable();
            $table->softDeletes();
        });

        $schema->create('kelas', function ($table) {
            $table->bigIncrements('id');
            $table->string('tingkat')->nullable();
            $table->string('nama_kelas')->nullable();
            $table->string('jurusan')->nullable();
        });

        $schema->create('mapel', function ($table) {
            $table->bigIncrements('id');
            $table->string('nama_mapel')->nullable();
        });

        $schema->create('jadwal_ujian', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('paket_ujian_id')->nullable();
            $table->unsignedBigInteger('mapel_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('judul')->nullable();
        });

        $schema->create('ruangan', function ($table) {
            $table->bigIncrements('id');
            $table->string('nama_ruangan');
        });

        $schema->create('sesi_ruangan', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ruangan_id');
            $table->string('nama_sesi');
            $table->unsignedBigInteger('paket_ujian_id')->nullable();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
        });

        $schema->create('siswa_tahun_ajaran', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('status_pembayaran')->nullable();
            $table->string('rekomendasi')->nullable();
        });

        $schema->create('sesi_ruangan_siswa', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('sesi_ruangan_id');
            $table->string('status_kehadiran')->nullable();
        });

        $schema->create('enrollment_ujian', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('jadwal_ujian_id');
            $table->unsignedBigInteger('sesi_ruangan_id')->nullable();
            $table->string('status_enrollment')->nullable();
            $table->string('waktu_mulai_ujian')->nullable();
            $table->string('waktu_selesai_ujian')->nullable();
        });
    }

    private function seedExamData(): void
    {
        $db = DB::connection('exam');

        $db->table('tahun_ajaran')->insert([['id' => 1, 'is_active' => 1, 'status' => 'aktif']]);
        $db->table('paket_ujian')->insert([['id' => 20, 'tahun_ajaran_id' => 1, 'status' => 'aktif']]);
        $db->table('kelas')->insert([[
            'id' => 1,
            'tingkat' => 'XI',
            'nama_kelas' => 'XI BD 1',
            'jurusan' => 'Bisnis Digital',
        ]]);
        $db->table('mapel')->insert([['id' => 1, 'nama_mapel' => 'Matematika']]);
        $db->table('ruangan')->insert([['id' => 1, 'nama_ruangan' => 'R1']]);

        $db->table('jadwal_ujian')->insert([
            ['id' => 1, 'tahun_ajaran_id' => 1, 'paket_ujian_id' => 20, 'mapel_id' => 1, 'tanggal' => self::TANGGAL, 'judul' => 'Ujian Hari Ini'],
            ['id' => 2, 'tahun_ajaran_id' => 1, 'paket_ujian_id' => 20, 'mapel_id' => 1, 'tanggal' => '2026-10-14', 'judul' => 'Ujian Kemarin'],
        ]);

        $db->table('sesi_ruangan')->insert([
            ['id' => 1, 'ruangan_id' => 1, 'nama_sesi' => 'Sesi 1', 'paket_ujian_id' => 20, 'tahun_ajaran_id' => 1],
        ]);

        $db->table('siswa')->insert([
            ['id' => 1, 'idyayasan' => 'S1', 'nama' => 'SISWA HADIR', 'nis' => '1', 'nama_kelas' => 'XI BD 1'],
            ['id' => 2, 'idyayasan' => 'S2', 'nama' => 'SISWA ALFA', 'nis' => '2', 'nama_kelas' => 'XI BD 1'],
            ['id' => 3, 'idyayasan' => 'S3', 'nama' => 'SISWA KEMARIN', 'nis' => '3', 'nama_kelas' => 'XI BD 1'],
        ]);

        foreach ([1, 2, 3] as $siswaId) {
            $db->table('siswa_tahun_ajaran')->insert([[
                'siswa_id' => $siswaId,
                'tahun_ajaran_id' => 1,
                'kelas_id' => 1,
                // Sengaja "Belum Lunas" untuk S1: gerbang pembayaran yang menang.
                'status_pembayaran' => $siswaId === 1 ? 'Belum Lunas' : 'Lunas',
                'rekomendasi' => $siswaId === 2 ? 'ya' : 'tidak',
            ]]);
        }

        $db->table('sesi_ruangan_siswa')->insert([
            ['siswa_id' => 1, 'sesi_ruangan_id' => 1, 'status_kehadiran' => 'hadir'],
            ['siswa_id' => 2, 'sesi_ruangan_id' => 1, 'status_kehadiran' => 'tidak_hadir'],
            ['siswa_id' => 3, 'sesi_ruangan_id' => 1, 'status_kehadiran' => 'hadir'],
        ]);

        $db->table('enrollment_ujian')->insert([
            ['siswa_id' => 1, 'jadwal_ujian_id' => 1, 'sesi_ruangan_id' => 1, 'status_enrollment' => 'selesai', 'waktu_mulai_ujian' => '08:00', 'waktu_selesai_ujian' => '09:00'],
            ['siswa_id' => 2, 'jadwal_ujian_id' => 1, 'sesi_ruangan_id' => 1, 'status_enrollment' => 'belum', 'waktu_mulai_ujian' => null, 'waktu_selesai_ujian' => null],
            ['siswa_id' => 3, 'jadwal_ujian_id' => 2, 'sesi_ruangan_id' => 1, 'status_enrollment' => 'selesai', 'waktu_mulai_ujian' => '08:00', 'waktu_selesai_ujian' => '09:00'],
        ]);
    }

    /**
     * Membaca kembali berkas .xlsx hasil export menjadi array baris.
     *
     * @return array<int, array<int, string>>
     */
    private function readExport(array $query): array
    {
        $response = $this->get('/kehadiran/download?'.http_build_query($query));
        $response->assertOk();

        $contents = $response->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'kehadiran').'.xlsx';
        file_put_contents($path, $contents);

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        @unlink($path);

        return $rows;
    }

    public function test_export_mengunduh_xlsx_untuk_tanggal_terpilih(): void
    {
        $response = $this->get('/kehadiran/download?tanggal='.self::TANGGAL);

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        $rows = $this->readExport(['tanggal' => self::TANGGAL]);

        // Baris 1 judul, baris 2 keterangan cetak, baris 4 header.
        $this->assertStringContainsString('Kehadiran Ujian', (string) $rows[0][0]);

        $header = $rows[3];
        $this->assertSame('Nama', $header[1]);
        $this->assertSame('Jurusan', $header[5]);
        $this->assertSame('Mapel', $header[7]);
        $this->assertSame('Administrasi Ujian', $header[12]);
        $this->assertSame('Rekom', $header[13]);

        // Hanya siswa yang ujian pada tanggal terpilih.
        $nama = array_map(fn ($row) => (string) $row[1], array_slice($rows, 4));
        $this->assertContains('SISWA HADIR', $nama);
        $this->assertContains('SISWA ALFA', $nama);
        $this->assertNotContains('SISWA KEMARIN', $nama);
    }

    public function test_kolom_jurusan_mapel_dan_status_terisi(): void
    {
        $rows = $this->readExport(['tanggal' => self::TANGGAL]);
        $data = array_slice($rows, 4);

        $hadir = collect($data)->firstWhere(1, 'SISWA HADIR');
        $this->assertNotNull($hadir);
        $this->assertSame('XI', $hadir[3]);
        $this->assertSame('XI BD 1', $hadir[4]);
        $this->assertSame('Bisnis Digital', $hadir[5]);
        $this->assertSame('Matematika', $hadir[7]);
        $this->assertSame('Ujian Hari Ini', $hadir[8]);
        $this->assertSame('Sesi 1', $hadir[9]);
        $this->assertSame('R1', $hadir[10]);
        $this->assertSame('Hadir', $hadir[11]);
        $this->assertSame('tidak', $hadir[13]);
    }

    public function test_status_kehadiran_dipetakan_ke_label(): void
    {
        $rows = $this->readExport(['tanggal' => self::TANGGAL]);
        $alfa = collect(array_slice($rows, 4))->firstWhere(1, 'SISWA ALFA');

        $this->assertNotNull($alfa);
        $this->assertSame('Tidak Hadir', $alfa[11]);
        $this->assertSame('ya', $alfa[13]);
    }

    /**
     * Status administrasi ujian harus ikut gerbang pembayaran apiakademik
     * (S1 "terpenuhi" => Lunas) walau kolom DB-nya "Belum Lunas". S2 tidak ada
     * di gerbang sehingga jatuh ke nilai DB ("Lunas").
     */
    public function test_status_administrasi_diambil_dari_gerbang_pembayaran(): void
    {
        $rows = $this->readExport(['tanggal' => self::TANGGAL]);
        $data = collect(array_slice($rows, 4));

        $this->assertSame('Lunas', $data->firstWhere(1, 'SISWA HADIR')[12]);
        $this->assertSame('Lunas', $data->firstWhere(1, 'SISWA ALFA')[12]);
    }

    /**
     * Filter halaman sengaja diabaikan: mengirim `status_kehadiran=hadir` tidak
     * boleh menghapus baris "tidak_hadir" dari berkas.
     */
    public function test_filter_halaman_diabaikan_saat_export(): void
    {
        $rows = $this->readExport([
            'tanggal' => self::TANGGAL,
            'status_kehadiran' => 'hadir',
            'tingkat' => 'XII',
            'q' => 'tidak-ada-yang-cocok',
        ]);

        $nama = array_map(fn ($row) => (string) $row[1], array_slice($rows, 4));

        $this->assertContains('SISWA HADIR', $nama);
        $this->assertContains('SISWA ALFA', $nama);
    }

    public function test_tanggal_wajib_diisi(): void
    {
        $response = $this->get('/kehadiran/download');

        $response->assertRedirect();
        $response->assertSessionHasErrors('tanggal');
    }

    public function test_halaman_kehadiran_menampilkan_modal_export(): void
    {
        $response = $this->get('/kehadiran')->assertOk();

        $response->assertSee('data-attendance-export-open', false);
        $response->assertSee('id="attendance-export-modal"', false);
        $response->assertSee('name="tanggal"', false);
        // Layout auto-submit semua <select>; modal ini harus opt-out.
        $response->assertSee('data-no-auto-submit', false);
        $response->assertSee(route('attendance.download'), false);
    }
}
