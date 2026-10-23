<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Drosstech (OVRS) compound enquiry proxy.
 *
 * Standalone — tidak terikat dengan flow klinik. Akses melalui URL /drosstech.
 *
 * Nota:
 * - Request ke Drosstech dibina fresh dari server; header browser user tidak diforward.
 * - Credential dibaca dari .env (config/services.php) sahaja — jangan hardcode.
 * - HTTPS verification kekal aktif.
 *
 * Routes (routes/web.php, di luar group modul klinik):
 *   Route::get('/drosstech', [DrosstechController::class, 'page'])->name('drosstech');
 *   Route::get('/drosstech/enquiry', [DrosstechController::class, 'enquiry'])->name('drosstech.enquiry');
 *
 * .env:
 *   DROSSTECH_BASE_URL=https://ovrs.com.my:8888
 *   DROSSTECH_USERNAME=mbjb
 *   DROSSTECH_PASSWORD=...
 *   DROSSTECH_AGENCY_CODE=MBJB
 *   DROSSTECH_COMPOUND_REF=XX
 *   # DROSSTECH_PROXY=http://user:pass@proxy-host:port
 */
class DrosstechController extends Controller
{
    private const GENERIC_UA = 'PHP';

    // Credential Drosstech (OVRS) — hardcoded atas permintaan.
    private const BASE_URL = 'https://ovrs.com.my:8888';
    private const USERNAME = 'mbjb';
    private const PASSWORD = 'mbjb@01082021';
    private const AGENCY_CODE = 'MBJB';
    private const COMPOUND_REF = 'XX';
    private const PROXY = null; // cth: 'http://user:pass@proxy-host:port'

    // CA bundle khusus (Sectigo Root R46 + intermediate DV R36) supaya verify SSL
    // tidak bergantung pada ca-certificates server production yang mungkin lapuk.
    private const CA_BUNDLE = 'certs/drosstech-ca.pem';

    public function page(): InertiaResponse
    {
        return Inertia::render('Drosstech', [
            'configured' => true,
            'agencyCode' => self::AGENCY_CODE,
        ]);
    }

    public function enquiry(Request $request): JsonResponse
    {
        $plate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('plate', '')));

        if ($plate === '') {
            return response()->json([
                'success' => false,
                'message' => 'No Plat kosong',
                'data' => null,
            ], 422);
        }

        $tokenResult = $this->getAccessToken();
        if (! $tokenResult['success']) {
            return response()->json($tokenResult, 502);
        }

        $query = [
            'AgencyCode' => self::AGENCY_CODE,
            'RegistrationNo' => $plate,
            'OffenceDate' => now()->format('Y-m-d'),
            'CompoundReferenceNo' => self::COMPOUND_REF,
        ];

        try {
            $res = $this->http()
                ->withToken($tokenResult['access_token'])
                ->get($this->url('/api/enquiry'), $query);

            return response()->json([
                'success' => $res->successful(),
                'status' => $res->status(),
                'message' => $res->successful() ? 'Drosstech OK' : 'Drosstech enquiry gagal',
                'request' => $query,
                'data' => $res->json(),
            ], $res->successful() ? 200 : 502);
        } catch (\Throwable $e) {
            Log::warning('Drosstech enquiry exception', ['error' => $e->getMessage()]);
            $hint = $this->hint($e);

            return response()->json([
                'success' => false,
                'message' => 'Exception semasa call Drosstech',
                'error' => $e->getMessage(),
                'hint' => $hint,
                'data' => null,
            ], 502);
        }
    }

    private function getAccessToken(): array
    {
        $cached = Cache::get('drosstech_token');
        if ($cached) {
            return ['success' => true, 'access_token' => $cached];
        }

        try {
            $res = $this->http()
                ->asForm()
                ->post($this->url('/token'), [
                    'grant_type' => 'password',
                    'username' => self::USERNAME,
                    'password' => self::PASSWORD,
                ]);

            $data = $res->json();

            if (! $res->successful() || empty($data['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'Gagal login token Drosstech',
                    'status' => $res->status(),
                    'data' => $data,
                ];
            }

            Cache::put('drosstech_token', $data['access_token'], now()->addMinutes(50));

            return ['success' => true, 'access_token' => $data['access_token']];
        } catch (\Throwable $e) {
            Log::warning('Drosstech token exception', ['error' => $e->getMessage()]);
            $hint = $this->hint($e);

            return [
                'success' => false,
                'message' => 'Exception semasa call Drosstech token',
                'error' => $e->getMessage(),
                'hint' => $hint,
                'data' => null,
            ];
        }
    }

    private function http()
    {
        $client = Http::withHeaders([
            'User-Agent' => self::GENERIC_UA,
            'Accept' => 'application/json',
        ])
            ->connectTimeout(10)
            ->timeout(20)
            ->withoutRedirecting()
            ->withOptions([
                'verify' => is_file($ca = resource_path(self::CA_BUNDLE)) ? $ca : true,
                // Paksa IPv4 — server yang ada IPv6 separuh-jalan selalu hang/timeout.
                'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            ]);

        if (self::PROXY) {
            $client = $client->withOptions(['proxy' => self::PROXY]);
        }

        return $client;
    }

    private function hint(\Throwable $e): string
    {
        $msg = $e->getMessage();

        return match (true) {
            str_contains($msg, 'cURL error 60'), str_contains($msg, 'cURL error 77'), str_contains($msg, 'SSL') => 'Masalah sijil SSL di server. Semak resources/certs/drosstech-ca.pem wujud & ca-certificates server dikemas kini.',
            str_contains($msg, 'cURL error 6') => 'DNS server gagal resolve ovrs.com.my.',
            str_contains($msg, 'cURL error 28'), str_contains($msg, 'cURL error 7') => 'Server tidak dapat sambung ke ovrs.com.my:8888. Kemungkinan outbound firewall menyekat port 8888 atau IP server belum di-whitelist oleh Drosstech.',
            default => 'Semak log Laravel untuk butiran.',
        };
    }

    private function url(string $path): string
    {
        return rtrim(self::BASE_URL, '/') . $path;
    }
}
