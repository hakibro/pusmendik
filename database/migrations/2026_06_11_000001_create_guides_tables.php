<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('group')->default('panitia')->index();
            $table->longText('content_md')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['slug' => 'siswa', 'title' => 'Panduan Siswa', 'group' => 'siswa', 'sort_order' => 1],
            ['slug' => 'admin', 'title' => 'Panduan Administrator', 'group' => 'panitia', 'sort_order' => 10],
            ['slug' => 'data', 'title' => 'Panduan Petugas Data', 'group' => 'panitia', 'sort_order' => 20],
            ['slug' => 'naskah', 'title' => 'Panduan Petugas Naskah', 'group' => 'panitia', 'sort_order' => 30],
            ['slug' => 'ruangan', 'title' => 'Panduan Petugas Ruangan', 'group' => 'panitia', 'sort_order' => 40],
            ['slug' => 'pengawas', 'title' => 'Panduan Pengawas', 'group' => 'panitia', 'sort_order' => 50],
            ['slug' => 'koordinator', 'title' => 'Panduan Koordinator', 'group' => 'panitia', 'sort_order' => 60],
        ];

        foreach ($defaults as $guide) {
            DB::table('guides')->insert([
                ...$guide,
                'content_md' => $this->defaultContent($guide['title']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_attachments');
        Schema::dropIfExists('guides');
    }

    private function defaultContent(string $title): string
    {
        return <<<MD
# {$title}

Tulis ringkasan singkat tentang tujuan panduan ini.

## 1. Mulai

Jelaskan langkah awal yang harus dilakukan.

## 2. Langkah Utama

| Bagian | Keterangan |
| --- | --- |
| Contoh | Isi keterangan di sini |

## 3. Catatan

> Tambahkan catatan penting, tautan dokumen, atau gambar screenshot bila diperlukan.
MD;
    }
};
