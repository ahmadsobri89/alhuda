<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Mengimport data asas (master data) daripada fail Excel di dalam folder
 * `data_ori/` ke dalam pangkalan data. Dipanggil daripada migration supaya
 * data asas tersedia sebaik sahaja skema dibina.
 *
 * Fail .xlsx dibaca terus (ZipArchive + SimpleXML) tanpa pakej luaran —
 * phpoffice/phpspreadsheet tidak dipasang dalam projek ini.
 *
 * Setiap import bersifat idempotent (updateOrCreate) supaya selamat dijalankan
 * semula.
 */
class BaseDataImportService
{
    /** Folder sumber fail Excel. */
    protected string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? base_path('data_ori');
    }

    /**
     * Jalankan semua import dan kembalikan ringkasan bilangan rekod.
     *
     * @return array<string,int>
     */
    public function importAll(): array
    {
        return [
            'patients'  => $this->importPatients(),
            'drugs'     => $this->importDrugs(),
            'services'  => $this->importServices(),
        ];
    }

    /**
     * Pesakit — ExportPatient1.xlsx
     * Lajur: IC, Name, Phone, Email, DOB (yyyy-MM-dd), Gender, Address, Medical History
     */
    public function importPatients(): int
    {
        $rows  = $this->readSheet('ExportPatient1.xlsx');
        $count = 0;

        foreach ($rows as $row) {
            $ic = $this->clean($row['IC'] ?? null);
            if ($ic === null) {
                continue; // baris tanpa IC dilangkau
            }

            $history = $this->clean($row['Medical History'] ?? null);

            Patient::updateOrCreate(
                ['ic_number' => $ic],
                [
                    'name'          => $this->clean($row['Name'] ?? '') ?? '',
                    'date_of_birth' => $this->excelDate($row['DOB (yyyy-MM-dd)'] ?? null),
                    'gender'        => $this->gender($row['Gender'] ?? null),
                    'phone'         => $this->clean($row['Phone'] ?? null),
                    'email'         => $this->clean($row['Email'] ?? null),
                    'address'       => $this->clean($row['Address'] ?? null),
                    'conditions'    => $history ? [$history] : null,
                    'status'        => 'active',
                ],
            );
            $count++;
        }

        return $count;
    }

    /**
     * Ubat / preskripsi — ExportPrescription1.xlsx → inventory_items
     * Lajur: Item ID, Prescription Code, Description, UOM, Cost Price, Selling Price, Stock Balance
     */
    public function importDrugs(): int
    {
        $rows  = $this->readSheet('ExportPrescription1.xlsx');
        $count = 0;

        foreach ($rows as $row) {
            $name = $this->clean($row['Description'] ?? null);
            if ($name === null) {
                continue;
            }

            $code = $this->clean($row['Prescription Code'] ?? null);

            InventoryItem::updateOrCreate(
                ['name' => $name, 'form' => 'Ubat'],
                [
                    'category'       => 'Preskripsi',
                    'classification' => 'general',
                    'unit'           => $this->clean($row['UOM'] ?? null) ?? 'unit',
                    'unit_cost'      => $this->decimal($row['Cost Price'] ?? null),
                    'selling_price'  => $this->decimal($row['Selling Price'] ?? null),
                    'stock_quantity' => $this->int($row['Stock Balance'] ?? null),
                    'notes'          => $code ? "Kod preskripsi: {$code}" : null,
                    'status'         => 'active',
                ],
            );
            $count++;
        }

        return $count;
    }

    /**
     * Prosedur / perkhidmatan — ExportProcedures.xlsx → inventory_items (form = service)
     * Lajur: Item ID, Item Code, Description, Price, Default Billing Item
     */
    public function importServices(): int
    {
        $rows  = $this->readSheet('ExportProcedures.xlsx');
        $count = 0;

        foreach ($rows as $row) {
            $name = $this->clean($row['Description'] ?? null);
            if ($name === null) {
                continue;
            }

            $code = $this->clean($row['Item Code'] ?? null);

            InventoryItem::updateOrCreate(
                ['name' => $name, 'form' => 'service'],
                [
                    'category'       => 'Perkhidmatan',
                    'classification' => 'general',
                    'unit'           => 'perkhidmatan',
                    'unit_cost'      => 0,
                    'selling_price'  => $this->decimal($row['Price'] ?? $row[' Price'] ?? null),
                    'stock_quantity' => 0,
                    'notes'          => $code ? "Kod item: {$code}" : null,
                    'status'         => 'active',
                ],
            );
            $count++;
        }

        return $count;
    }

    // ── Pembaca .xlsx (tanpa pakej luaran) ─────────────────────────────────

    /**
     * Baca sheet pertama sesuatu fail .xlsx dan kembalikan baris sebagai
     * tatasusunan bersekutu yang dikunci oleh tajuk lajur (baris pertama).
     *
     * @return array<int,array<string,string|null>>
     */
    protected function readSheet(string $filename): array
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . $filename;

        if (! is_file($path)) {
            Log::warning("BaseDataImportService: fail tidak dijumpai — {$path}");
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            Log::warning("BaseDataImportService: gagal buka — {$path}");
            return [];
        }

        $shared = $this->sharedStrings($zip);
        $sheet  = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            return [];
        }

        $xml  = new \SimpleXMLElement($sheet);
        $grid = [];

        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref); // "B3" → "B"
                $cells[$col] = $this->cellValue($c, $shared);
            }
            $grid[] = $cells;
        }

        return $this->assoc($grid);
    }

    /**
     * Senarai shared strings (`xl/sharedStrings.xml`).
     *
     * @return array<int,string>
     */
    protected function sharedStrings(\ZipArchive $zip): array
    {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) {
            return [];
        }

        $xml  = new \SimpleXMLElement($raw);
        $list = [];
        foreach ($xml->si as $si) {
            // Gabungkan semua nod <t> (teks biasa atau berfragmen <r><t>).
            $text = '';
            foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
                $text .= (string) $t;
            }
            $list[] = $text;
        }

        return $list;
    }

    /**
     * Selesaikan nilai sesuatu sel, dengan menyahrujuk shared string bila perlu.
     */
    protected function cellValue(\SimpleXMLElement $c, array $shared): ?string
    {
        $type = (string) $c['t'];

        if ($type === 'inlineStr') {
            $text = '';
            foreach ($c->xpath('.//*[local-name()="t"]') as $t) {
                $text .= (string) $t;
            }
            return $text;
        }

        $v = (string) $c->v;
        if ($v === '') {
            return null;
        }

        return $type === 's' ? ($shared[(int) $v] ?? null) : $v;
    }

    /**
     * Tukar grid (dikunci huruf lajur) kepada baris bersekutu menggunakan
     * baris pertama sebagai tajuk.
     *
     * @param  array<int,array<string,string|null>>  $grid
     * @return array<int,array<string,string|null>>
     */
    protected function assoc(array $grid): array
    {
        if (count($grid) < 2) {
            return [];
        }

        $headerRow = array_shift($grid);
        $map       = [];                // huruf lajur → nama tajuk
        foreach ($headerRow as $col => $title) {
            $title = is_string($title) ? trim($title) : $title;
            if ($title !== null && $title !== '') {
                $map[$col] = $title;
            }
        }

        $rows = [];
        foreach ($grid as $cells) {
            $row = [];
            foreach ($map as $col => $title) {
                $row[$title] = $cells[$col] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    // ── Penormalan nilai ───────────────────────────────────────────────────

    protected function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    protected function gender(?string $value): string
    {
        return strtoupper(trim((string) $value)) === 'FEMALE' ? 'female' : 'male';
    }

    /** Tukar nombor siri Excel (atau rentetan tarikh) kepada tarikh Y-m-d. */
    protected function excelDate(?string $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            // Siri Excel: hari 1 = 1900-01-01 (offset 25569 ke epoch Unix).
            return Carbon::createFromTimestampUTC(((int) $value - 25569) * 86400)->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function decimal(?string $value): float
    {
        $value = $this->clean($value);
        return $value !== null && is_numeric($value) ? (float) $value : 0.0;
    }

    protected function int(?string $value): int
    {
        $value = $this->clean($value);
        return $value !== null && is_numeric($value) ? (int) $value : 0;
    }
}
