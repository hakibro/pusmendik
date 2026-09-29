<?php

namespace App\Services;

use App\Http\Controllers\PusmendikController;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien API akademik bersama (`apiakademik`) untuk pusmendik.
 *
 * Menggantikan gateway lama `api.daruttaqwa.or.id/sisda/v1`. Dua jalur yang dipakai:
 * - `pembayaranSiswa()` -> detail tagihan per siswa lintas periode.
 * - `ujianPembayaran()` -> gerbang pembayaran ujian per lembaga (seluruh siswa aktif).
 *
 * URL dasar diambil dari setting aplikasi `payment_api_base_url`, lalu fallback ke
 * `.env` `PAYMENT_API_BASE_URL` / `API_AKADEMIK_BASE_URL`.
 */
class ApiAkademikService
{
    public function baseUrl(): string
    {
        try {
            $setting = app(PusmendikController::class)->paymentApiBaseUrl();
        } catch (\Throwable $exception) {
            // Tabel setting belum ada (mis. saat pengujian) — jatuh ke .env.
            $setting = env('PAYMENT_API_BASE_URL')
                ?: env('API_AKADEMIK_BASE_URL')
                ?: 'https://apiakademik.daruttaqwa.or.id/api';
        }

        return rtrim((string) $setting, '/');
    }

    public function token(): ?string
    {
        $token = env('API_AKADEMIK_TOKEN');

        return $token ? (string) $token : null;
    }

    public function timeout(): int
    {
        return (int) (env('API_AKADEMIK_TIMEOUT') ?: env('PAYMENT_API_TIMEOUT') ?: 30);
    }

    /**
     * Unit/lembaga yang dipakai untuk endpoint gerbang per lembaga (mis. SMK = `02`).
     */
    public function idunit(): string
    {
        return (string) (env('API_AKADEMIK_IDUNIT') ?: '02');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{success: bool, status: int|null, data: array<mixed>|null, error: string|null}
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->when($this->token(), fn ($request, $token) => $request->withToken($token))
                ->timeout($this->timeout())
                ->retry(2, 500, throw: false)
                ->get($url, $query);
        } catch (\Throwable $exception) {
            Log::warning('apiakademik: permintaan gagal', [
                'url' => $url,
                'query' => $query,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'data' => null,
                'error' => 'Tidak dapat menghubungi API akademik: '.$exception->getMessage(),
            ];
        }

        return $this->unwrap($response, $url);
    }

    /**
     * @return array{success: bool, status: int|null, data: array<mixed>|null, error: string|null}
     */
    private function unwrap(Response $response, string $url): array
    {
        if (! $response->successful()) {
            $message = $response->status() === 404
                ? 'Data tidak ditemukan di API akademik.'
                : sprintf('API akademik membalas HTTP %d', $response->status());

            Log::warning('apiakademik: respons tidak sukses', [
                'url' => $url,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'data' => null,
                'error' => $message,
            ];
        }

        $json = $response->json();

        if (! is_array($json)) {
            return [
                'success' => false,
                'status' => $response->status(),
                'data' => null,
                'error' => 'Respons API akademik bukan JSON yang valid.',
            ];
        }

        return [
            'success' => true,
            'status' => $response->status(),
            'data' => $json,
            'error' => null,
        ];
    }

    /**
     * Detail tagihan satu siswa (lintas periode).
     *
     * @param  array<string, mixed>  $query  mis. ['idperiode' => '20262027', 'ujian' => 1]
     * @return array{success: bool, status: int|null, data: array<mixed>|null, error: string|null}
     */
    public function pembayaranSiswa(string $idperson, array $query = []): array
    {
        return $this->get('/siswa/'.$idperson.'/pembayaran', $query);
    }

    /**
     * Gerbang pembayaran ujian per lembaga.
     *
     * @param  array<string, mixed>  $query
     * @return array{success: bool, status: int|null, data: array<mixed>|null, error: string|null}
     */
    public function ujianPembayaran(string $lembaga = '02', array $query = []): array
    {
        return $this->get('/'.$lembaga.'/ujian/pembayaran', $query);
    }
}
