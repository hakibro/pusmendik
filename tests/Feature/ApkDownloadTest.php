<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Beranda menampilkan tombol Download APK untuk siswa. File APK dikelola dari
 * halaman Setting (di dalam middleware `data.user`); nama aplikasi diambil dari
 * nama file yang diunggah.
 */
class ApkDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsDataUser(): self
    {
        return $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']]);
    }

    public function test_settings_page_requires_data_user(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_upload_apk_stores_file_and_shows_banner_on_homepage(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()
            ->post('/settings/apk', [
                'apk' => UploadedFile::fake()->create('NgalahMobile-1.2.3.apk', 2048, 'application/vnd.android.package-archive'),
            ])
            ->assertRedirect();

        $path = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);

        $this->get('/')
            ->assertOk()
            ->assertSee('Download APK')
            ->assertSee('NgalahMobile-1.2.3')
            ->assertSee('/storage/'.$path);
    }

    public function test_replacing_apk_deletes_the_previous_file(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()->post('/settings/apk', [
            'apk' => UploadedFile::fake()->create('versi-lama.apk', 1024, 'application/vnd.android.package-archive'),
        ]);
        $oldPath = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');

        $this->loginAsDataUser()->post('/settings/apk', [
            'apk' => UploadedFile::fake()->create('versi-baru.apk', 1024, 'application/vnd.android.package-archive'),
        ]);
        $newPath = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->get('/')
            ->assertOk()
            ->assertSee('versi-baru')
            ->assertDontSee('versi-lama');
    }

    public function test_upload_rejects_non_apk_file(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()
            ->from('/settings')
            ->post('/settings/apk', [
                'apk' => UploadedFile::fake()->create('bukan-apk.txt', 10, 'text/plain'),
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('apk');

        $this->assertDatabaseMissing('app_settings', ['key' => 'apk_file_path']);
    }

    public function test_delete_apk_removes_file_and_hides_banner(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()->post('/settings/apk', [
            'apk' => UploadedFile::fake()->create('NgalahMobile.apk', 1024, 'application/vnd.android.package-archive'),
        ]);
        $path = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');
        Storage::disk('public')->assertExists($path);

        $this->loginAsDataUser()
            ->delete('/settings/apk')
            ->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('app_settings', ['key' => 'apk_file_path']);

        $this->get('/')->assertOk()->assertDontSee('Download APK');
    }

    public function test_homepage_hides_banner_when_file_is_missing(): void
    {
        Storage::fake('public');

        DB::table('app_settings')->insert([
            ['key' => 'apk_file_path', 'value' => 'apk/hilang.apk', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'apk_file_name', 'value' => 'hilang.apk', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/')->assertOk()->assertDontSee('Download APK');
    }

    public function test_settings_page_shows_upload_form_and_current_apk(): void
    {
        Storage::fake('public');

        $this->loginAsDataUser()->get('/settings')
            ->assertOk()
            ->assertSee('Aplikasi Android (APK)')
            ->assertSee('Belum ada aplikasi yang diunggah.')
            ->assertSee('name="apk"', false);

        $this->loginAsDataUser()->post('/settings/apk', [
            'apk' => UploadedFile::fake()->create('NgalahMobile-1.2.3.apk', 1024, 'application/vnd.android.package-archive'),
        ]);

        $this->loginAsDataUser()->get('/settings')
            ->assertOk()
            ->assertSee('NgalahMobile-1.2.3')
            ->assertSee('Hapus APK')
            ->assertSee('Simpan APK Baru');
    }
}
