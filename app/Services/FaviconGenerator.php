<?php

namespace App\Services;

use App\Models\ClinicProfile;
use RuntimeException;

/**
 * Menjana set ikon laman (favicon) di dalam folder `public/` daripada logo
 * klinik semasa.
 *
 * Kenapa fail statik dan bukan terus pautkan logo dalam storage: Google
 * menyimpan cache favicon berdasarkan URL. Logo yang dimuat naik melalui
 * Tetapan → Profil Klinik disimpan dengan nama rawak (contoh
 * `storage/clinic/rfX7hLv...png`), jadi URL berubah setiap kali logo ditukar
 * dan Google terus memaparkan ikon lama. Dengan menjana semula fail di URL
 * yang tetap (`/favicon.ico`, `/favicon-192x192.png`), URL kekal sama
 * selama-lamanya dan Google hanya perlu merangkak semula kandungannya.
 *
 * Saiz mengikut syarat Google: ikon mestilah segi empat sama dan gandaan 48px
 * (48, 96, 144, 192) untuk dipaparkan dalam hasil carian.
 *
 * Dipanggil automatik selepas logo dikemaskini (SettingsController) dan boleh
 * dijalankan manual melalui `php artisan clinic:favicons`.
 */
class FaviconGenerator
{
    /** Logo lalai jika klinik belum memuat naik logo sendiri. */
    public const FALLBACK_LOGO = 'images/logo_new_design.png';

    /** Fail PNG yang dijana: nama fail dalam public/ => saiz piksel. */
    protected const PNG_SIZES = [
        'favicon-16x16.png' => 16,
        'favicon-32x32.png' => 32,
        'favicon-48x48.png' => 48,
        'favicon-96x96.png' => 96,
        'favicon-192x192.png' => 192,
        'favicon-512x512.png' => 512,
    ];

    /** Saiz yang dibungkus di dalam favicon.ico. */
    protected const ICO_SIZES = [16, 32, 48];

    /** iOS tidak menyokong lutsinar — ikon ini dirata di atas latar putih. */
    protected const APPLE_TOUCH_SIZE = 180;

    /**
     * Jana semula semua ikon. Mengembalikan senarai fail yang ditulis.
     *
     * @return list<string>
     */
    public function generate(?string $sourcePath = null): array
    {
        $sourcePath ??= $this->resolveSourcePath();

        if (! is_file($sourcePath)) {
            throw new RuntimeException("Fail logo tidak dijumpai: {$sourcePath}");
        }

        $source = @imagecreatefromstring(file_get_contents($sourcePath));

        if ($source === false) {
            throw new RuntimeException("Fail logo bukan imej yang sah: {$sourcePath}");
        }

        $written = [];

        try {
            foreach (self::PNG_SIZES as $file => $size) {
                $written[] = $this->writePng($source, $size, public_path($file));
            }

            $written[] = $this->writePng(
                $source,
                self::APPLE_TOUCH_SIZE,
                public_path('apple-touch-icon.png'),
                background: [255, 255, 255],
            );

            $ico = public_path('favicon.ico');
            file_put_contents($ico, $this->buildIco($source));
            $written[] = $ico;
        } finally {
            imagedestroy($source);
        }

        return $written;
    }

    /**
     * Logo klinik daripada Tetapan jika ada, jika tidak logo lalai projek.
     */
    public function resolveSourcePath(): string
    {
        $logoPath = ClinicProfile::current()->logo_path;

        if ($logoPath) {
            $stored = storage_path('app/public/'.$logoPath);

            if (is_file($stored)) {
                return $stored;
            }
        }

        return public_path(self::FALLBACK_LOGO);
    }

    /**
     * Turunkan skala logo ke dalam kanvas segi empat sama, nisbah dikekalkan
     * dan ruang lebih dibiarkan lutsinar (atau diisi $background).
     *
     * @param  array{int, int, int}|null  $background
     */
    protected function render(\GdImage $source, int $size, ?array $background = null): \GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $fill = $background === null
            ? imagecolorallocatealpha($canvas, 0, 0, 0, 127)
            : imagecolorallocate($canvas, ...$background);

        imagefilledrectangle($canvas, 0, 0, $size, $size, $fill);
        imagealphablending($canvas, true);

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $scale = $size / max($srcW, $srcH);
        $dstW = max(1, (int) round($srcW * $scale));
        $dstH = max(1, (int) round($srcH * $scale));

        imagecopyresampled(
            $canvas,
            $source,
            (int) (($size - $dstW) / 2),
            (int) (($size - $dstH) / 2),
            0,
            0,
            $dstW,
            $dstH,
            $srcW,
            $srcH,
        );

        imagesavealpha($canvas, true);

        return $canvas;
    }

    /** @param array{int, int, int}|null $background */
    protected function writePng(\GdImage $source, int $size, string $path, ?array $background = null): string
    {
        $image = $this->render($source, $size, $background);

        try {
            imagepng($image, $path, 9);
        } finally {
            imagedestroy($image);
        }

        return $path;
    }

    /** @param array{int, int, int}|null $background */
    protected function pngBytes(\GdImage $source, int $size, ?array $background = null): string
    {
        $image = $this->render($source, $size, $background);

        try {
            ob_start();
            imagepng($image, null, 9);

            return (string) ob_get_clean();
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Bina fail .ico yang membungkus beberapa PNG (format ICO moden, disokong
     * oleh semua pelayar semasa dan perangkak Google).
     */
    protected function buildIco(\GdImage $source): string
    {
        $entries = [];
        $body = '';

        foreach (self::ICO_SIZES as $size) {
            $entries[$size] = $this->pngBytes($source, $size);
        }

        // ICONDIR: reserved, type (1 = ikon), bilangan imej.
        $header = pack('vvv', 0, 1, count($entries));
        $directory = '';
        $offset = 6 + 16 * count($entries);

        foreach ($entries as $size => $png) {
            // ICONDIRENTRY: lebar, tinggi, warna, reserved, planes, bit, saiz, offset.
            $directory .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
            $offset += strlen($png);
            $body .= $png;
        }

        return $header.$directory.$body;
    }
}
