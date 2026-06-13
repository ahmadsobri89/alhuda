<?php

use App\Services\BaseDataImportService;
use Illuminate\Database\Migrations\Migration;

/**
 * Import data asas (pesakit, ubat/preskripsi, perkhidmatan) daripada fail Excel
 * di folder `data_ori/` melalui BaseDataImportService.
 */
return new class extends Migration
{
    public function up(): void
    {
        $summary = app(BaseDataImportService::class)->importAll();

        $line = sprintf(
            'Import data asas selesai — pesakit: %d, ubat: %d, perkhidmatan: %d',
            $summary['patients'],
            $summary['drugs'],
            $summary['services'],
        );

        if (app()->runningInConsole()) {
            echo "  {$line}\n";
        }
    }

    public function down(): void
    {
        // Import data asas — tiada pembalikan automatik supaya rekod sedia ada
        // (cth. data seed) tidak terpadam secara tidak sengaja.
    }
};
