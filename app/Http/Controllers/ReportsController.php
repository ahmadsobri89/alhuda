<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use App\Models\VisitDiagnosis;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'month');

        [$dateFrom, $dateTo, $label, $prevFrom, $prevTo] = $this->periodBounds($period);

        /* ── Revenue KPIs ── */
        $revCur  = Invoice::where('status','paid')->whereBetween('paid_at', [$dateFrom, $dateTo])->sum('total_amount');
        $revPrev = Invoice::where('status','paid')->whereBetween('paid_at', [$prevFrom, $prevTo])->sum('total_amount');
        $revDiff = $revPrev > 0 ? round(($revCur - $revPrev) / $revPrev * 100, 1) : null;

        /* ── Patients seen ── */
        $patCur  = Visit::whereBetween('visit_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])->count();
        $patPrev = Visit::whereBetween('visit_date', [$prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d')])->count();

        /* ── Appointments ── */
        $apptCur  = Appointment::whereBetween('appointment_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])->count();
        $apptDone = Appointment::whereBetween('appointment_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])->where('status','done')->count();
        $apptNoShow = Appointment::whereBetween('appointment_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])->where('status','no_show')->count();

        /* ── Revenue by payment method ── */
        $byMethod = Invoice::where('status','paid')
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->selectRaw('payment_method, sum(total_amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['method' => $r->payment_method, 'total' => (float) $r->total]);

        /* ── Monthly revenue trend (12 months) ── */
        $monthlyRev = collect(range(11, 0))->map(function ($ago) {
            $d = now()->subMonths($ago);
            $v = Invoice::where('status','paid')
                ->whereMonth('paid_at', $d->month)->whereYear('paid_at', $d->year)
                ->sum('total_amount');
            return ['label' => $d->isoFormat('MMM'), 'year' => $d->year, 'month' => $d->month, 'value' => (float) $v];
        })->values();

        /* ── Daily revenue within period (max 31 days) ── */
        $dailyRev = Invoice::where('status','paid')
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->selectRaw('date(paid_at) as d, sum(total_amount) as total')
            ->groupBy('d')->orderBy('d')
            ->get()
            ->map(fn ($r) => ['date' => $r->d, 'label' => \Carbon\Carbon::parse($r->d)->format('j'), 'value' => (float) $r->total]);

        /* ── Appointment by type ── */
        $byType = Appointment::whereBetween('appointment_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->selectRaw('type, count(*) as cnt')
            ->groupBy('type')->orderByDesc('cnt')
            ->get()->map(fn ($r) => ['type' => $r->type, 'count' => $r->cnt]);

        /* ── Appointment by status ── */
        $byStatus = Appointment::whereBetween('appointment_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')->orderByDesc('cnt')
            ->get()->map(fn ($r) => ['status' => $r->status, 'count' => $r->cnt]);

        /* ── Top ICD-10 diagnoses ── */
        $topIcd = VisitDiagnosis::whereHas('visit', fn ($q) =>
                $q->whereBetween('visit_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            )
            ->selectRaw('icd_code, description, count(*) as cnt')
            ->groupBy('icd_code','description')
            ->orderByDesc('cnt')->take(10)
            ->get()->map(fn ($r) => ['code' => $r->icd_code, 'description' => $r->description, 'count' => $r->cnt]);

        /* ── Prescription summary ── */
        $rxSummary = Prescription::selectRaw('status, count(*) as cnt')
            ->groupBy('status')->orderByDesc('cnt')
            ->get()->map(fn ($r) => ['status' => $r->status, 'count' => $r->cnt]);

        /* ── Inventory ── */
        $invTotal   = InventoryItem::count();
        $invLow     = InventoryItem::whereColumn('stock_quantity','<=','reorder_level')->get(['name','stock_quantity','reorder_level']);
        $invValue   = (float) InventoryItem::selectRaw('sum(stock_quantity * unit_cost) as val')->value('val');

        /* ── Patient stats ── */
        $totalPatients = Patient::where('status','active')->count();
        $newPatients   = Patient::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        return Inertia::render('Reports', [
            'currentRoute' => 'reports',
            'period'       => $period,
            'periodLabel'  => $label,
            'kpi' => [
                'revenue'      => (float) $revCur,
                'revenue_prev' => (float) $revPrev,
                'revenue_diff' => $revDiff,
                'patients'     => $patCur,
                'patients_prev'=> $patPrev,
                'appt_total'   => $apptCur,
                'appt_done'    => $apptDone,
                'appt_no_show' => $apptNoShow,
                'new_patients' => $newPatients,
                'total_patients'=> $totalPatients,
            ],
            'byMethod'    => $byMethod->values(),
            'monthlyRev'  => $monthlyRev,
            'dailyRev'    => $dailyRev->values(),
            'byType'      => $byType->values(),
            'byStatus'    => $byStatus->values(),
            'topIcd'      => $topIcd->values(),
            'rxSummary'   => $rxSummary->values(),
            'invTotal'    => $invTotal,
            'invLow'      => $invLow->values(),
            'invValue'    => $invValue,
        ]);
    }

    private function periodBounds(string $period): array
    {
        switch ($period) {
            case 'last_month':
                $from  = now()->subMonth()->startOfMonth();
                $to    = now()->subMonth()->endOfMonth();
                $label = $from->isoFormat('MMMM YYYY');
                $pFrom = $from->copy()->subMonth()->startOfMonth();
                $pTo   = $from->copy()->subMonth()->endOfMonth();
                break;
            case 'year':
                $from  = now()->startOfYear();
                $to    = now()->endOfYear();
                $label = 'Tahun ' . now()->year;
                $pFrom = now()->subYear()->startOfYear();
                $pTo   = now()->subYear()->endOfYear();
                break;
            default: // month
                $from  = now()->startOfMonth();
                $to    = now()->endOfMonth();
                $label = $from->isoFormat('MMMM YYYY');
                $pFrom = now()->subMonth()->startOfMonth();
                $pTo   = now()->subMonth()->endOfMonth();
        }
        return [$from, $to, $label, $pFrom, $pTo];
    }
}
