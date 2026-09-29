<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Request HTTPS dari reverse proxy tepercaya (nginx Lerd / Cloudflare Tunnel)
     * harus menghasilkan URL ber-skema https. Kalau tidak, halaman https memuat
     * aset http -> diblokir browser sebagai mixed content, JS (live search) mati.
     */
    public function test_url_memakai_https_dari_proxy_tepercaya(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'abc-def.trycloudflare.com',
                'X-Forwarded-Port' => '443',
            ])
            ->get('/panduan');

        $response->assertOk();
        $response->assertSee('https://abc-def.trycloudflare.com', false);
        $response->assertDontSee('http://abc-def.trycloudflare.com', false);
    }

    /**
     * IPv6 ULA (fd00::/8) dipakai jaringan podman Lerd sebagai REMOTE_ADDR
     * saat nginx meneruskan ke php-fpm; harus dianggap proxy tepercaya.
     */
    public function test_url_memakai_https_dari_proxy_tepercaya_ipv6(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => 'fd00:1e7d::8'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'abc-def.trycloudflare.com',
                'X-Forwarded-Port' => '443',
            ])
            ->get('/panduan');

        $response->assertOk();
        $response->assertSee('https://abc-def.trycloudflare.com', false);
    }

    /**
     * Header X-Forwarded-Port dari proxy sengaja TIDAK dipercaya: cloudflared/lerd
     * mengirim port internal (80) padahal publik 443. Kalau dipercaya, URL jadi
     * "https://host:80/..." sehingga aset gagal dimuat -> halaman tanpa styling.
     */
    public function test_header_x_forwarded_port_tidak_ikut_dipakai(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'pusmendik.wonocraft.my.id',
                'X-Forwarded-Port' => '80',
            ])
            ->get('/panduan');

        $response->assertOk();
        $response->assertSee('https://pusmendik.wonocraft.my.id', false);
        $response->assertDontSee('https://pusmendik.wonocraft.my.id:80', false);
    }

    /**
     * Header X-Forwarded-* dari alamat TIDAK tepercaya tidak boleh dipercaya
     * (anti host/proto spoofing): URL tetap memakai host/skema asli.
     */
    public function test_header_forwarded_dari_alamat_tidak_tepercaya_diabaikan(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example.com',
            ])
            ->get('/panduan');

        $response->assertOk();
        $response->assertDontSee('evil.example.com', false);
    }
}
