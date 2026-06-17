<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinanceController extends Controller
{
    /** Label BM untuk jenis pembayaran (dikongsi index/export); kekunci = kaedah. */
    private const METHOD_LABELS = [
        'cash' => 'Tunai', 'card' => 'Kad', 'duitnow' => 'DuitNow',
        'panel' => 'Panel', 'insurance' => 'Insurans',
    ];

    /**
     * Selesaikan tempoh terpilih + closure penapis. Dikongsi oleh index() &
     * export() supaya penapis (dan sekatan admin) sentiasa konsisten.
     */
    private function resolvePeriod(Request $request): array
    {
        $isAdmin = Auth::user()->hasRole('admin');

        // Hanya admin boleh lihat bulanan/tahunan; lain-lain harian sahaja.
        $period = $request->input('period', 'day');
        if (! in_array($period, ['day', 'month', 'year'], true) || (! $isAdmin && $period !== 'day')) {
            $period = 'day';
        }

        $year  = max(2015, min((int) now()->year, (int) $request->input('year', now()->year)));
        $month = max(1, min(12, (int) $request->input('month', now()->month)));
        try {
            $dateC = Carbon::parse($request->input('date', now()->format('Y-m-d')));
        } catch (\Throwable $e) {
            $dateC = now();
        }
        $date = $dateC->format('Y-m-d');

        $scope = function ($q) use ($period, $date, $month, $year) {
            $q->where('status', 'paid');
            if ($period === 'day') {
                $q->whereDate('paid_at', $date);
            } elseif ($period === 'month') {
                $q->whereMonth('paid_at', $month)->whereYear('paid_at', $year);
            } else {
                $q->whereYear('paid_at', $year);
            }
            return $q;
        };

        $label = match ($period) {
            'month' => Carbon::create($year, $month)->isoFormat('MMMM YYYY'),
            'year'  => (string) $year,
            default => $dateC->isoFormat('dddd, D MMMM YYYY'),
        };

        return compact('isAdmin', 'period', 'date', 'month', 'year', 'dateC', 'scope', 'label');
    }

    public function index(Request $request)
    {
        [
            'isAdmin' => $isAdmin, 'period' => $period, 'date' => $date,
            'month'   => $month,   'year'   => $year,   'dateC'  => $dateC,
            'scope'   => $scope,   'label'  => $periodLabel,
        ] = $this->resolvePeriod($request);

        // ── Ringkasan ──
        $total = (float) $scope(Invoice::query())->sum('total_amount');
        $count = (int) $scope(Invoice::query())->count();

        // ── Pecahan ikut jenis pembayaran ──
        $raw = $scope(Invoice::query())
            ->selectRaw('payment_method, SUM(total_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $byMethod = collect(array_keys(self::METHOD_LABELS))->map(fn ($m) => [
            'method' => $m,
            'total'  => (float) ($raw[$m]->total ?? 0),
            'count'  => (int) ($raw[$m]->cnt ?? 0),
            'pct'    => $total > 0 ? round(((float) ($raw[$m]->total ?? 0) / $total) * 100, 1) : 0,
        ])->values();

        // ── Trend (ikut tempoh) ──
        if ($period === 'day') {
            // 7 hari terakhir berakhir pada tarikh terpilih
            $trend = collect(range(6, 0))->map(function ($d) use ($dateC) {
                $dd = $dateC->copy()->subDays($d);
                return [
                    'label' => $dd->isoFormat('ddd'),
                    'sub'   => $dd->format('d/m'),
                    'value' => (float) Invoice::where('status', 'paid')->whereDate('paid_at', $dd->format('Y-m-d'))->sum('total_amount'),
                ];
            })->values();
        } elseif ($period === 'month') {
            $daily = $scope(Invoice::query())
                ->selectRaw('DATE(paid_at) as d, SUM(total_amount) as total')
                ->groupBy('d')->pluck('total', 'd');
            $dim = Carbon::create($year, $month)->daysInMonth;
            $trend = collect(range(1, $dim))->map(fn ($d) => [
                'label' => (string) $d,
                'sub'   => '',
                'value' => (float) ($daily[sprintf('%04d-%02d-%02d', $year, $month, $d)] ?? 0),
            ])->values();
        } else {
            $monthly = $scope(Invoice::query())
                ->selectRaw('MONTH(paid_at) as m, SUM(total_amount) as total')
                ->groupBy('m')->pluck('total', 'm');
            $trend = collect(range(1, 12))->map(fn ($m) => [
                'label' => Carbon::create(null, $m, 1)->isoFormat('MMM'),
                'sub'   => '',
                'value' => (float) ($monthly[$m] ?? 0),
            ])->values();
        }

        // ── Transaksi dalam tempoh ──
        $transactions = $scope(Invoice::with('patient'))
            ->latest('paid_at')
            ->take(60)
            ->get()
            ->map(fn ($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'patient_name'   => $i->patient->name,
                'total_amount'   => (float) $i->total_amount,
                'payment_method' => $i->payment_method,
                'paid_at'        => $i->paid_at->format('d/m/Y H:i'),
                'paid_by'        => $i->paid_by,
            ]);

        return Inertia::render('Finance', [
            'currentRoute'  => 'finance',
            'isAdmin'       => $isAdmin,
            'period'        => $period,
            'periodLabel'   => $periodLabel,
            'summary'       => ['total' => $total, 'count' => $count, 'avg' => $count > 0 ? round($total / $count, 2) : 0],
            'byMethod'      => $byMethod,
            'trend'         => $trend,
            'transactions'  => $transactions,
            'selectedDate'  => $date,
            'selectedMonth' => $month,
            'selectedYear'  => $year,
            'filterYears'   => range((int) now()->year, 2015),
        ]);
    }

    /**
     * Eksport CSV pembayaran untuk tujuan audit (mengikut tempoh terpilih).
     * Termasuk butiran penuh tiap transaksi + pecahan jenis bayaran + jejak audit.
     */
    public function export(Request $request)
    {
        $p = $this->resolvePeriod($request);

        $rows = $p['scope'](Invoice::with('patient:id,name'))
            ->orderBy('paid_at')
            ->get();

        AuditLog::record('finance.export', "Eksport audit kewangan · {$p['label']} · {$rows->count()} transaksi");

        $safeLabel = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $p['label']), '-');
        $filename  = "audit-kewangan-{$p['period']}-{$safeLabel}.csv";
        $generated = now();
        $by        = Auth::user()->name;

        return response()->streamDownload(function () use ($rows, $p, $generated, $by) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM — pastikan aksara UTF-8 betul dalam Excel

            // Tulis satu baris CSV (escape '' = standard CSV, elak deprecation PHP 8.4)
            $put = fn (array $row) => fputcsv($out, $row, ',', '"', '');

            // ── Kepala laporan ──
            $put(['LAPORAN KEWANGAN UNTUK AUDIT']);
            $put(['Tempoh',      $p['label']]);
            $put(['Dijana pada', $generated->format('d/m/Y H:i:s')]);
            $put(['Dijana oleh', $by]);
            $put([]);

            // ── Lajur transaksi ──
            $put([
                'Bil', 'No. Invois', 'Tarikh Invois', 'Pesakit', 'Jenis Bayaran',
                'Subtotal (RM)', 'Diskaun (RM)', 'Jumlah (RM)', 'Masa Bayar', 'Diterima Oleh',
            ]);

            $i = 1;
            $grand = 0.0;
            foreach ($rows as $r) {
                $grand += (float) $r->total_amount;
                $put([
                    $i++,
                    $r->invoice_number,
                    optional($r->invoice_date)->format('d/m/Y'),
                    $r->patient->name ?? '',
                    self::METHOD_LABELS[$r->payment_method] ?? $r->payment_method,
                    number_format((float) $r->subtotal, 2, '.', ''),
                    number_format((float) $r->discount_amount, 2, '.', ''),
                    number_format((float) $r->total_amount, 2, '.', ''),
                    optional($r->paid_at)->format('d/m/Y H:i'),
                    $r->paid_by,
                ]);
            }
            $put([]);
            $put(['', '', '', '', '', '', 'JUMLAH BESAR', number_format($grand, 2, '.', ''), '', '']);

            // ── Pecahan ikut jenis bayaran ──
            $put([]);
            $put(['PECAHAN IKUT JENIS BAYARAN']);
            $put(['Jenis', 'Bilangan', 'Jumlah (RM)']);
            foreach (self::METHOD_LABELS as $key => $label) {
                $sub = $rows->where('payment_method', $key);
                if ($sub->isEmpty()) {
                    continue;
                }
                $put([$label, $sub->count(), number_format((float) $sub->sum('total_amount'), 2, '.', '')]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
