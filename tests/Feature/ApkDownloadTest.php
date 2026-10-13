<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Beranda menampilkan tombol Download APK untuk siswa, dengan opsi sekunder
 * (kecil) untuk mengunduh installer Windows (.exe) bagi petugas/pengawas.
 * File dikelola dari halaman Setting (di dalam middleware `data.user`); nama
 * aplikasi diambil dari nama file yang diunggah.
 */
class ApkDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsDataUser(): self
    {
        return $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']]);
    }

    private function apk(): UploadedFile
    {
        return UploadedFile::fake()->create('NgalahMobile-1.2.3.apk', 2048, 'application/vnd.android.package-archive');
    }

    private function exe(): UploadedFile
    {
        return UploadedFile::fake()->create('NgalahMobile-Setup-1.2.3.exe', 4096, 'application/x-msdownload');
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
            ->post('/settings/apps/apk', ['apk' => $this->apk()])
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

    public function test_upload_exe_without_apk_shows_nothing(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()
            ->post('/settings/apps/exe', ['exe' => $this->exe()])
            ->assertRedirect();

        $apkPath = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');
        $exePath = DB::table('app_settings')->where('key', 'exe_file_path')->value('value');
        $this->assertNull($apkPath);
        Storage::disk('public')->assertExists($exePath);

        // .exe hanyalah opsi sekunder di dalam banner APK; tanpa APK, beranda bersih.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Download APK')
            ->assertDontSee('/storage/'.$exePath);
    }

    public function test_exe_link_is_secondary_and_hides_without_apk(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()->post('/settings/apps/apk', ['apk' => $this->apk()]);
        $this->loginAsDataUser()->post('/settings/apps/exe', ['exe' => $this->exe()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Download APK')
            // Opsi .exe diberi konteks agar siswa tidak salah unduh.
            ->assertSee('Untuk petugas')
            ->assertSee('Siswa tidak perlu file ini')
            ->assertSee('NgalahMobile-Setup-1.2.3');
    }

    public function test_replacing_apk_deletes_the_previous_file(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()->post('/settings/apps/apk', [
            'apk' => UploadedFile::fake()->create('versi-lama.apk', 1024, 'application/vnd.android.package-archive'),
        ]);
        $oldPath = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');

        $this->loginAsDataUser()->post('/settings/apps/apk', [
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

    public function test_upload_rejects_wrong_extension(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        // .exe tidak boleh diterima di slot APK, dan sebaliknya.
        $this->loginAsDataUser()
            ->from('/settings')
            ->post('/settings/apps/apk', ['apk' => $this->exe()])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('apk');

        $this->loginAsDataUser()
            ->from('/settings')
            ->post('/settings/apps/exe', ['exe' => $this->apk()])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('exe');

        $this->assertDatabaseMissing('app_settings', ['key' => 'apk_file_path']);
        $this->assertDatabaseMissing('app_settings', ['key' => 'exe_file_path']);
    }

    public function test_unknown_installer_type_is_not_found(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->loginAsDataUser()->post('/settings/apps/msi')->assertNotFound();
        $this->loginAsDataUser()->delete('/settings/apps/msi')->assertNotFound();
    }

    public function test_delete_removes_file_and_hides_download(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->loginAsDataUser()->post('/settings/apps/apk', ['apk' => $this->apk()]);
        $path = DB::table('app_settings')->where('key', 'apk_file_path')->value('value');
        Storage::disk('public')->assertExists($path);

        $this->loginAsDataUser()->delete('/settings/apps/apk')->assertRedirect();

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

    public function test_settings_page_shows_forms_for_both_installers(): void
    {
        Storage::fake('public');

        $this->loginAsDataUser()->get('/settings')
            ->assertOk()
            ->assertSee('Aplikasi Android (APK)')
            ->assertSee('Aplikasi Windows (EXE)')
            ->assertSee('Belum ada aplikasi Android yang diunggah.')
            ->assertSee('Belum ada aplikasi Windows yang diunggah.')
            ->assertSee('name="apk"', false)
            ->assertSee('name="exe"', false);

        $this->loginAsDataUser()->post('/settings/apps/apk', ['apk' => $this->apk()]);
        $this->loginAsDataUser()->post('/settings/apps/exe', ['exe' => $this->exe()]);

        $this->loginAsDataUser()->get('/settings')
            ->assertOk()
            ->assertSee('NgalahMobile-1.2.3')
            ->assertSee('NgalahMobile-Setup-1.2.3')
            ->assertSee('Hapus File')
            ->assertSee('Simpan File Baru');
    }
}
