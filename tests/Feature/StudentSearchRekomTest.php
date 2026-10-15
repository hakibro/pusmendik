<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `/ajax/siswa` mengisi dropdown pencarian di beranda dan di `/cek-pembayaran`.
 * Dropdown itu harus membawa status rekomendasi (`sta.rekomendasi`, scope tahun
 * ajaran aktif) supaya siswa rekom bisa dikenali langsung dari hasil pencarian.
 *
 * Nominal rekom SENGAJA tidak dikirim — halaman ini tidak menampilkan nominal.
 */
class StudentSearchRekomTest extends TestCase
{
    use RefreshDatabase;

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

        // Gerbang pembayaran ujian apiakademik tidak dipanggil di test ini; status
        // administrasi jatuh ke kolom `siswa_tahun_ajaran.status_pembayaran`.
        Http::fake(['*' => Http::response([], 500)]);
    }

    private function createExamSchema(): void
    {
        $schema = Schema::connection('exam');

        $schema->create('tahun_ajaran', function ($table) {
            $table->bigIncrements('id');
            $table->string('nama')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status')->nullable();
        });

        $schema->create('paket_ujian', function ($table) {
            $table->bigIncrements('id');
            $table->string('nama')->nullable();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->string('status')->nullable();
        });

        $schema->create('siswa', function ($table) {
            $table->bigIncrements('id');
            $table->string('idyayasan');
            $table->string('nama');
            $table->string('nis')->nullable();
            $table->string('nama_kelas')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('kelas', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->string('tingkat')->nullable();
            $table->string('nama_kelas')->nullable();
            $table->string('jurusan')->nullable();
        });

        $schema->create('siswa_tahun_ajaran', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('status_pembayaran')->nullable();
            $table->string('rekomendasi')->nullable();
            $table->text('catatan')->nullable();
        });
    }

    private function seedExamData(): void
    {
        $db = DB::connection('exam');

        $db->table('tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2025/2026', 'is_active' => 1, 'status' => 'aktif'],
        ]);

        $db->table('kelas')->insert([
            ['id' => 1, 'tahun_ajaran_id' => 1, 'tingkat' => 'XI', 'nama_kelas' => 'XI BD 1'],
        ]);

        $db->table('siswa')->insert([
            ['id' => 1, 'idyayasan' => 'SISWA-REKOM', 'nama' => 'SISWA REKOM YA', 'nis' => '1', 'nama_kelas' => 'XI BD 1'],
            ['id' => 2, 'idyayasan' => 'SISWA-BIASA', 'nama' => 'SISWA TANPA REKOM', 'nis' => '2', 'nama_kelas' => 'XI BD 1'],
        ]);

        $db->table('siswa_tahun_ajaran')->insert([
            ['siswa_id' => 1, 'tahun_ajaran_id' => 1, 'kelas_id' => 1, 'status_pembayaran' => 'Belum Lunas', 'rekomendasi' => 'ya'],
            ['siswa_id' => 2, 'tahun_ajaran_id' => 1, 'kelas_id' => 1, 'status_pembayaran' => 'Lunas', 'rekomendasi' => 'tidak'],
        ]);
    }

    public function test_pencarian_membawa_status_rekom(): void
    {
        $response = $this->getJson('/ajax/siswa?q=SISWA')->assertOk();

        $response->assertJsonFragment([
            'idyayasan' => 'SISWA-REKOM',
            'rekomendasi' => 'ya',
        ]);
        $response->assertJsonFragment([
            'idyayasan' => 'SISWA-BIASA',
            'rekomendasi' => 'tidak',
        ]);
    }

    public function test_pencarian_tanpa_nominal_rekom(): void
    {
        $response = $this->getJson('/ajax/siswa?q=SISWA')->assertOk();

        $response->assertJsonMissingPath('0.nominal_rekom');
    }

    public function test_kata_kunci_kurang_dari_dua_karakter_mengembalikan_kosong(): void
    {
        $this->getJson('/ajax/siswa?q=S')->assertOk()->assertExactJson([]);
    }
}
