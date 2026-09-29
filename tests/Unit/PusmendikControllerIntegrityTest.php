<?php

namespace Tests\Unit;

use App\Http\Controllers\PusmendikController;
use ReflectionClass;
use Tests\TestCase;

class PusmendikControllerIntegrityTest extends TestCase
{
    /**
     * Setiap `$this->method()` di dalam PusmendikController harus punya definisi.
     *
     * Refactor besar (mis. saat memindahkan sumber pembayaran ke apiakademik) rawan
     * menghapus method yang masih dipanggil dari controller lain di file yang sama —
     * dulu menyebabkan `/siswa` error 500: Call to undefined method paymentSummaryByLevel().
     */
    public function test_semua_pemanggilan_method_internal_punya_definisi(): void
    {
        $class = PusmendikController::class;
        $reflection = new ReflectionClass($class);
        $source = file_get_contents($reflection->getFileName());

        preg_match_all('/\$this->([A-Za-z_][A-Za-z0-9_]*)\(/', $source, $matches);
        $called = array_values(array_unique($matches[1]));
        $defined = array_map(fn ($method) => $method->getName(), $reflection->getMethods());

        $missing = array_values(array_diff($called, $defined));

        $this->assertSame(
            [],
            $missing,
            'PusmendikController memanggil method yang tidak terdefinisi: '.implode(', ', $missing)
        );
    }
}
