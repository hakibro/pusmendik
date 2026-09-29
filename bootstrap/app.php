<?php

use App\Http\Middleware\EnsureDataUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aplikasi berjalan di belakang reverse proxy (nginx Lerd, dan opsional
        // Cloudflare Tunnel). Tanpa ini, request HTTPS dari tunnel dianggap HTTP
        // sehingga URL/aset yang dirender memakai skema http -> browser memblokir
        // sebagai mixed content dan CSS/JS (termasuk live search) tidak termuat.
        // Default: loopback + rentang privat IPv4/IPv6 (nginx/container lokal,
        // termasuk IPv6 ULA fd00::/8 yang dipakai jaringan podman Lerd).
        // Set env TRUSTED_PROXIES (mis. "**" atau IP edge Cloudflare) bila origin publik.
        //
        // PENTING: header X-Forwarded-Port sengaja TIDAK dipercaya. cloudflared/lerd
        // mengirim port internal (80) sementara publiknya 443; bila dipercaya, URL
        // jadi "https://host:80/..." dan aset gagal dimuat (halaman tanpa styling).
        // Tanpa header port, port mengikuti skema (443 utk https) sehingga benar.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,::1,fc00::/7,fe80::/10'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->alias([
            'data.user' => EnsureDataUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
