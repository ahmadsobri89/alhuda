<?php

use App\Services\BaseDataImportService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Activitylog\Support\ActivityLogStatus;

/**
 * Import data asas (pesakit, ubat/preskripsi, perkhidmatan) daripada fail Excel
 * di folder `data_ori/` melalui BaseDataImportService.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The activity_log table doesn't exist yet at this point in the migration
        // order (it's created later) — disable logging for the duration of the
        // import so model writes here don't fail trying to log to a missing table.
        $activityLog = app(ActivityLogStatus::class);
        $activityLog->disable();

        try {
            $summary = app(BaseDataImportService::class)->importAll();
        } finally {
            $activityLog->enable();
        }

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
