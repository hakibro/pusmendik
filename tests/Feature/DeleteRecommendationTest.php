<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * "Hapus Rekom" menghapus riwayat penanganan pada scope aktif (tahun ajaran +
 * paket ujian) dan mengembalikan status rekomendasi ke 'tidak'.
 *
 * Yang penting dikunci: penghapusan TIDAK menyentuh riwayat pada paket ujian
 * lain, dan status di `siswa_tahun_ajaran` ikut direset supaya tidak ada rekom
 * "menggantung" tanpa handler.
 */
class DeleteRecommendationTest extends TestCase
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
            $table->timestamps();
        });
    }

    private function seedExamData(): void
    {
        $db = DB::connection('exam');

        $db->table('tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2025/2026', 'is_active' => 1, 'status' => 'aktif'],
        ]);

        // Paket terbaru (id 20) = paket aktif yang dipakai controller.
        $db->table('paket_ujian')->insert([
            ['id' => 10, 'nama' => 'Paket Lama', 'tahun_ajaran_id' => 1, 'status' => 'aktif'],
            ['id' => 20, 'nama' => 'Paket Aktif', 'tahun_ajaran_id' => 1, 'status' => 'aktif'],
        ]);

        $db->table('kelas')->insert([
            ['id' => 1, 'tahun_ajaran_id' => 1, 'tingkat' => 'XI', 'nama_kelas' => 'XI BD 1'],
        ]);

        $db->table('siswa')->insert([
            ['id' => 1, 'idyayasan' => 'SISWA-1', 'nama' => 'SISWA SATU', 'nis' => '1', 'nama_kelas' => 'XI BD 1'],
        ]);

        $db->table('siswa_tahun_ajaran')->insert([
            [
                'siswa_id' => 1,
                'tahun_ajaran_id' => 1,
                'kelas_id' => 1,
                'status_pembayaran' => 'Belum Lunas',
                'rekomendasi' => 'ya',
                'catatan' => 'wali membayar Rp. 500.000',
            ],
        ]);

        DB::table('recommendation_handlers')->insert([
            [
                'exam_siswa_id' => 1,
                'tahun_ajaran_id' => 1,
                'paket_ujian_id' => 20,
                'idyayasan' => 'SISWA-1',
                'nama' => 'SISWA SATU',
                'nominal_rekom' => 500000,
                'catatan' => 'wali membayar Rp. 500.000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_siswa_id' => 1,
                'tahun_ajaran_id' => 1,
                'paket_ujian_id' => 10,
                'idyayasan' => 'SISWA-1',
                'nama' => 'SISWA SATU',
                'nominal_rekom' => 250000,
                'catatan' => 'wali membayar Rp. 250.000 (paket lama)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_hapus_rekomendasi_menghapus_scope_aktif_dan_reset_status(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']])
            ->delete('/siswa/1/rekomendasi')
            ->assertRedirect();

        // Handler paket aktif (20) terhapus, handler paket lain (10) tetap ada.
        $this->assertSame(0, DB::table('recommendation_handlers')->where('paket_ujian_id', 20)->count());
        $this->assertSame(1, DB::table('recommendation_handlers')->where('paket_ujian_id', 10)->count());

        $sta = DB::connection('exam')->table('siswa_tahun_ajaran')->where('siswa_id', 1)->first();
        $this->assertSame('tidak', $sta->rekomendasi);
        $this->assertNull($sta->catatan);
    }

    public function test_hapus_rekomendasi_butuh_login(): void
    {
        $this->delete('/siswa/1/rekomendasi')->assertRedirect('/login');

        // Tidak ada yang terhapus saat belum login.
        $this->assertSame(2, DB::table('recommendation_handlers')->count());
    }
}
