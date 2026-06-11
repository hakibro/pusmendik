<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_guides_show_active_student_and_committee_guides(): void
    {
        DB::table('guides')->where('slug', 'siswa')->update([
            'content_md' => "# Panduan Siswa\n\n## Login Siswa\n\nIsi siswa.",
        ]);
        DB::table('guides')->where('slug', 'naskah')->update([
            'content_md' => "# Panduan Naskah\n\n## Import Soal\n\nIsi naskah.",
        ]);

        $this->get('/panduan?group=siswa')
            ->assertOk()
            ->assertSee('Panduan Siswa')
            ->assertSee('Login Siswa')
            ->assertDontSee('Import Soal');

        $this->get('/panduan?group=panitia&role=naskah')
            ->assertOk()
            ->assertSee('Panduan Petugas Naskah')
            ->assertSee('Import Soal')
            ->assertDontSee('Login Siswa');
    }

    public function test_guide_editor_requires_data_user_session(): void
    {
        $this->get('/settings/panduan')
            ->assertRedirect('/login');
    }

    public function test_data_user_can_create_update_and_delete_guide(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $session = ['data_user' => ['id' => 1, 'name' => 'Tester']];

        $this->withSession($session)->post('/settings/panduan', [
            'title' => 'Panduan Import Nilai',
            'slug' => 'import-nilai',
            'group' => 'panitia',
            'sort_order' => 90,
            'content_md' => "# Panduan Import Nilai\n\n## Upload",
            'is_active' => '1',
        ])->assertRedirect();

        $guide = DB::table('guides')->where('slug', 'import-nilai')->first();
        $this->assertNotNull($guide);

        $this->withSession($session)->put('/settings/panduan/' . $guide->id, [
            'title' => 'Panduan Import Nilai Revisi',
            'slug' => 'import-nilai-revisi',
            'group' => 'panitia',
            'sort_order' => 91,
            'content_md' => "# Revisi\n\n## Validasi",
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('guides', [
            'id' => $guide->id,
            'slug' => 'import-nilai-revisi',
            'title' => 'Panduan Import Nilai Revisi',
        ]);

        $this->withSession($session)->delete('/settings/panduan/' . $guide->id)
            ->assertRedirect('/settings/panduan');

        $this->assertDatabaseMissing('guides', ['id' => $guide->id]);
    }

    public function test_image_upload_accepts_valid_image_and_returns_markdown_snippet(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');

        $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']])
            ->post('/settings/panduan/upload-image', [
                'alt' => 'Halaman Import',
                'image' => UploadedFile::fake()->image('import.png')->size(100),
            ])
            ->assertRedirect()
            ->assertSessionHas('uploaded_image_markdown');

        Storage::disk('public')->assertExists(
            collect(Storage::disk('public')->allFiles('guides/images'))->first()
        );
    }

    public function test_attachment_upload_accepts_documents_and_public_page_shows_download_link(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');
        $guide = DB::table('guides')->where('slug', 'naskah')->first();

        $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']])
            ->post('/settings/panduan/' . $guide->id . '/attachments', [
                'title' => 'Template Import Soal',
                'attachment' => UploadedFile::fake()->create('template-import-soal.xlsx', 20, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertRedirect('/settings/panduan?guide=' . $guide->id);

        $attachment = DB::table('guide_attachments')->where('guide_id', $guide->id)->first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->file_path);

        $this->get('/panduan?group=panitia&role=naskah')
            ->assertOk()
            ->assertSee('Template Import Soal')
            ->assertSee('/storage/' . $attachment->file_path);
    }

    public function test_attachment_upload_rejects_unsupported_files(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');
        $guide = DB::table('guides')->where('slug', 'naskah')->first();

        $this->withSession(['data_user' => ['id' => 1, 'name' => 'Tester']])
            ->from('/settings/panduan?guide=' . $guide->id)
            ->post('/settings/panduan/' . $guide->id . '/attachments', [
                'title' => 'File Script',
                'attachment' => UploadedFile::fake()->create('script.exe', 5, 'application/octet-stream'),
            ])
            ->assertRedirect('/settings/panduan?guide=' . $guide->id)
            ->assertSessionHasErrors('attachment');
    }
}
