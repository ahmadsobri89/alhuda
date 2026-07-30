<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Perkhidmatan (Services) sepatutnya berdiri sendiri, berasingan daripada
 * `inventory_items` (stok ubat/inventori). Migration ini:
 *  1. Mencipta jadual `services`.
 *  2. Memindahkan rekod servis yang sedia ada (form='service') keluar
 *     daripada `inventory_items` ke `services` (jika ada — pada pemasangan
 *     baharu, timestamp fail ini mendahului import data asas, jadi tiada
 *     apa untuk dipindah pada ketika ini; import akan terus menulis ke
 *     `services` selepas ini).
 *
 * Ditempatkan sebelum `2026_06_13_090000_import_base_data_from_data_ori.php`
 * (secara sengaja, mengikut nama fail) supaya jadual `services` sedia wujud
 * apabila BaseDataImportService::importServices() dipanggil pada pemasangan
 * baharu (migrate:fresh).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('inventory_items') || Service::count() > 0) {
            return;
        }

        $rows = DB::table('inventory_items')->where('form', 'service')->get();
        if ($rows->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($rows) {
            $linked = DB::table('prescription_items')
                ->whereIn('inventory_item_id', $rows->pluck('id'))
                ->count();
            if ($linked > 0) {
                Log::warning(
                    "create_services_table: memindahkan {$rows->count()} rekod servis keluar daripada " .
                    "inventory_items — {$linked} prescription_items merujuk rekod ini dan inventory_item_id " .
                    'akan menjadi null (nullOnDelete).'
                );
            }

            foreach ($rows as $row) {
                $code = null;
                $notes = $row->notes;
                if ($notes && preg_match('/Kod item:\s*(.+)/i', $notes, $m)) {
                    $code = trim($m[1]);
                    $notes = null;
                }

                Service::create([
                    'code'     => $code,
                    'name'     => $row->name,
                    'category' => $row->category,
                    'price'    => $row->selling_price,
                    'status'   => $row->status,
                    'notes'    => $notes,
                ]);
            }

            DB::table('inventory_items')->where('form', 'service')->delete();
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (Service::all() as $service) {
                DB::table('inventory_items')->insert([
                    'name'           => $service->name,
                    'generic_name'   => null,
                    'form'           => 'service',
                    'category'       => $service->category ?? 'Perkhidmatan',
                    'classification' => 'general',
                    'lot_number'     => null,
                    'expiry_date'    => null,
                    'supplier'       => null,
                    'stock_quantity' => 0,
                    'reorder_level'  => 50,
                    'unit_cost'      => 0,
                    'selling_price'  => $service->price,
                    'unit'           => 'perkhidmatan',
                    'notes'          => $service->code ? "Kod item: {$service->code}" : $service->notes,
                    'status'         => $service->status,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            Schema::dropIfExists('services');
        });
    }
};
