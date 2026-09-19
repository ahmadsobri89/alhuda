<?php

namespace App\Console\Commands;

use App\Services\FaviconGenerator;
use Illuminate\Console\Command;
use Throwable;

/**
 * Jana semula favicon di dalam `public/` daripada logo klinik semasa.
 *
 * Guna selepas logo ditukar terus di dalam pangkalan data, atau untuk
 * memaksa ikon dijana semula tanpa perlu memuat naik semula logo.
 */
class GenerateFavicons extends Command
{
    protected $signature = 'clinic:favicons
                            {--source= : Guna fail imej ini dan bukan logo klinik semasa}';

    protected $description = 'Jana semula favicon laman daripada logo klinik';

    public function handle(FaviconGenerator $generator): int
    {
        $source = $this->option('source') ?: $generator->resolveSourcePath();

        $this->line("Sumber: {$source}");

        try {
            $written = $generator->generate($source);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($written as $path) {
            $this->line('  '.basename($path).' ('.number_format(filesize($path) / 1024, 1).' KB)');
        }

        $this->info(count($written).' ikon dijana.');

        return self::SUCCESS;
    }
}
