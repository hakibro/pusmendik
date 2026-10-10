<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `/ajax/ruangan-siswa` mengisi daftar di halaman Ruangan & Sesi. Halaman itu
 * (`rooms()`) membatasi pada paket ujian aktif (`sr.paket_ujian_id`), maka
 * endpoint AJAX-nya WAJIB memakai batas yang sama — kalau tidak, pencarian
 * menampilkan siswa dari paket ujian lama yang tidak muncul di halaman.
 *
 * Dulu `$activeExamPackageId` dihitung tapi tidak dipakai (unused), sehingga
 * endpoint ini bocor lintas paket. Test ini mengunci perilaku tersebut.
 */
class RoomStudentSearchScopeTest extends TestCase
{
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
        });

        $schema->create('ruangan', function ($table) {
            $table->bigIncrements('id');
            $table->string('nama_ruangan');
        });

        $schema->create('sesi_ruangan', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ruangan_id');
            $table->string('nama_sesi');
            $table->string('waktu_mulai')->nullable();
            $table->string('waktu_selesai')->nullable();
            $table->string('sumber')->nullable();
            $table->unsignedBigInteger('paket_ujian_id')->nullable();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
        });

        $schema->create('siswa_tahun_ajaran', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
        });

        $schema->create('sesi_ruangan_siswa', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('sesi_ruangan_id');
            $table->string('status_kehadiran')->nullable();
        });
    }

    private function seedExamData(): void
    {
        $db = DB::connection('exam');

        $db->table('tahun_ajaran')->insert([['id' => 1, 'is_active' => 1, 'status' => 'aktif']]);

        // Dua paket aktif; yang terbaru (id 20) adalah paket yang dipakai halaman.
        $db->table('paket_ujian')->insert([
            ['id' => 10, 'tahun_ajaran_id' => 1, 'status' => 'aktif'],
            ['id' => 20, 'tahun_ajaran_id' => 1, 'status' => 'aktif'],
        ]);

        $db->table('kelas')->insert([['id' => 1, 'tingkat' => 'XI', 'nama_kelas' => 'XI BD 1']]);
        $db->table('ruangan')->insert([['id' => 1, 'nama_ruangan' => 'R1']]);

        $db->table('siswa')->insert([
            ['id' => 1, 'idyayasan' => 'SISWA-PAKET-AKTIF', 'nama' => 'SISWA-PAKET-AKTIF', 'nis' => '1', 'nama_kelas' => 'XI BD 1'],
            ['id' => 2, 'idyayasan' => 'SISWA-PAKET-LAMA', 'nama' => 'SISWA-PAKET-LAMA', 'nis' => '2', 'nama_kelas' => 'XI BD 1'],
        ]);

        foreach ([1, 2] as $siswaId) {
            $db->table('siswa_tahun_ajaran')->insert([
                ['siswa_id' => $siswaId, 'tahun_ajaran_id' => 1, 'kelas_id' => 1],
            ]);
        }

        $db->table('sesi_ruangan')->insert([
            ['id' => 1, 'ruangan_id' => 1, 'nama_sesi' => 'Sesi 1', 'waktu_mulai' => '08:00', 'waktu_selesai' => '09:00', 'sumber' => 'sumber', 'paket_ujian_id' => 20, 'tahun_ajaran_id' => 1],
            ['id' => 2, 'ruangan_id' => 1, 'nama_sesi' => 'Sesi 2', 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00', 'sumber' => 'sumber', 'paket_ujian_id' => 10, 'tahun_ajaran_id' => 1],
        ]);

        $db->table('sesi_ruangan_siswa')->insert([
            ['siswa_id' => 1, 'sesi_ruangan_id' => 1, 'status_kehadiran' => 'hadir'],
            ['siswa_id' => 2, 'sesi_ruangan_id' => 2, 'status_kehadiran' => 'hadir'],
        ]);
    }

    public function test_pencarian_hanya_menampilkan_siswa_paket_ujian_aktif(): void
    {
        $response = $this->getJson('/ajax/ruangan-siswa')->assertOk();

        $response->assertJsonFragment(['nama' => 'SISWA-PAKET-AKTIF']);
        $response->assertJsonMissing(['nama' => 'SISWA-PAKET-LAMA']);
    }
}
