<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

/**
 * `UploadedFile::store()` bertipe `string|false`. Dulu nilai itu langsung
 * diserahkan ke `publicStorageUrl(string $path)` sehingga kegagalan simpan
 * melempar `TypeError` (500) alih-alih pesan error yang rapi.
 *
 * Test ini mengunci penanganan `false` pada kedua endpoint upload panduan.
 */
class GuideImageUploadFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        // Paksa disk mengembalikan `false` (kegagalan simpan) tanpa menyentuh
        // penyimpanan nyata: cukup ganti Factory yang dipakai `UploadedFile::store()`.
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->andReturnFalse();

        $factory = Mockery::mock(FilesystemFactory::class);
        $factory->shouldReceive('disk')->andReturn($disk);

        $this->app->instance(FilesystemFactory::class, $factory);
    }

    private function dataUserSession(): array
    {
        return ['data_user' => ['id' => 1, 'name' => 'Tester']];
    }

    public function test_upload_non_ajax_gagal_menyimpan_memberi_error_bukan_500(): void
    {
        $this->withSession($this->dataUserSession())
            ->from('/settings/panduan')
            ->post('/settings/panduan/upload-image', [
                'alt' => 'Halaman',
                'image' => UploadedFile::fake()->image('x.png'),
            ])
            ->assertRedirect('/settings/panduan')
            ->assertSessionHas('error');

        $this->assertNull(session('uploaded_image_markdown'));
    }

    public function test_upload_ajax_gagal_menyimpan_membalas_json_422(): void
    {
        $this->withSession($this->dataUserSession())
            ->postJson('/settings/panduan/upload-image-ajax', [
                'alt' => 'Halaman',
                'image' => UploadedFile::fake()->image('x.png'),
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
