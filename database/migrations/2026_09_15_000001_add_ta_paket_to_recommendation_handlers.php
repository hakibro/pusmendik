<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rekomendasi disimpan per (siswa, tahun ajaran, paket ujian).
 *
 * Sebelumnya `recommendation_handlers` hanya punya `exam_siswa_id`, sehingga
 * rekomendasi tidak bisa dibedakan per tahun ajaran / paket ujian. Kolom baru
 * ini dipakai untuk memfilter rekomendasi sesuai TA aktif + paket ujian aktif.
 *
 * Baris lama (103 baris) tidak punya konteks TA/paket, jadi dibiarkan NULL dan
 * akan tersembunyi saat filter aktif — itu memang perilaku yang diminta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_handlers', function (Blueprint $table) {
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable()->after('exam_siswa_id');
            $table->unsignedBigInteger('paket_ujian_id')->nullable()->after('tahun_ajaran_id');

            $table->index(['tahun_ajaran_id', 'paket_ujian_id'], 'rekom_ta_paket_index');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_handlers', function (Blueprint $table) {
            $table->dropIndex('rekom_ta_paket_index');
            $table->dropColumn(['tahun_ajaran_id', 'paket_ujian_id']);
        });
    }
};
