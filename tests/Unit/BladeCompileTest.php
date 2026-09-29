<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\BladeCompiler;
use Tests\TestCase;

/**
 * Penjaga kompilasi blade.
 *
 * Kesalahan seperti `wajib@if(...)` (directive yang menempel pada huruf) tidak
 * dikompilasi Blade, sehingga `@endif` pasangannya liar dan halaman error 500.
 * Test ini mengompilasi setiap file .blade.php supaya kesalahan itu ketangkap
 * sebelum sampai ke pengguna.
 */
class BladeCompileTest extends TestCase
{
    public function test_semua_blade_dapat_dikompilasi_tanpa_error(): void
    {
        $compiler = app(BladeCompiler::class);
        $errors = [];
        $checked = 0;

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $checked++;

            try {
                $compiled = $compiler->compileString(File::get($file->getPathname()));
            } catch (\Throwable $exception) {
                $errors[] = $file->getRelativePathname().': '.$exception->getMessage();

                continue;
            }

            $temporary = tempnam(sys_get_temp_dir(), 'blade-').'.php';
            File::put($temporary, $compiled);

            $output = [];
            $exitCode = 0;
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($temporary).' 2>&1', $output, $exitCode);

            File::delete($temporary);

            if ($exitCode !== 0) {
                $errors[] = $file->getRelativePathname().': '.implode(' ', $output);
            }
        }

        $this->assertGreaterThan(0, $checked, 'Tidak ada file blade yang diperiksa.');
        $this->assertSame([], $errors, "Blade gagal dikompilasi:\n".implode("\n", $errors));
    }
}
