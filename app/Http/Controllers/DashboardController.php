<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today       = now()->format('Y-m-d');
        $filterMonth = max(1, min(12, (int) request('month', now()->month)));
        $filterYear  = max(2015, min((int) now()->year, (int) request('year', now()->year)));
        $user        = Auth::user();

        /* ── KPIs ── */
        $todayAppts = Appointment::whereDate('appointment_date', $today);
        $kpi = [
            'today_total'    => (clone $todayAppts)->count(),
            'today_waiting'  => (clone $todayAppts)->whereIn('status', ['confirmed','waiting'])->count(),
            'today_in_room'  => (clone $todayAppts)->where('status', 'in_room')->count(),
            'today_done'     => (clone $todayAppts)->where('status', 'done')->count(),
            'month_revenue'  => Invoice::where('status','paid')->whereMonth('paid_at',$filterMonth)->whereYear('paid_at',$filterYear)->sum('total_amount'),
            'today_revenue'  => Invoice::where('status','paid')->whereDate('paid_at',$today)->sum('total_amount'),
            'pending_inv'    => Invoice::whereIn('status',['draft','unpaid'])->count(),
            'pending_amount' => Invoice::whereIn('status',['draft','unpaid'])->sum('total_amount'),
            'total_patients' => Patient::where('status','active')->count(),
            'open_visits'    => Visit::where('status','open')->count(),
        ];

        /* ── Upcoming appointments today ── */
        $upcoming = Appointment::with('patient')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['confirmed','waiting','in_room'])
            ->orderBy('appointment_time')
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'patient_name'   => $a->patient->name,
                'appointment_time' => $a->appointment_time,
                'type'           => $a->type,
                'reason'         => $a->reason,
                'status'         => $a->status,
                'doctor_name'    => $a->doctor_name,
            ]);

        /* ── Recent visits (last 5) ── */
        $recentVisits = Visit::with('patient')
            ->latest('visit_date')
            ->take(6)
            ->get()
            ->map(fn ($v) => [
                'id'           => $v->id,
                'patient_name' => $v->patient->name,
                'visit_date'   => $v->visit_date->format('d M'),
                'status'       => $v->status,
                'doctor_name'  => $v->doctor_name,
            ]);

        /* ── Alerts ── */
        $alerts = collect();

        // Low-stock inventory
        $lowStock = InventoryItem::whereColumn('stock_quantity', '<=', 'reorder_level')->count();
        if ($lowStock) {
            $alerts->push(['type'=>'stock','title'=>'Stok Rendah','msg'=>$lowStock.' item perlu ditambah semula','tone'=>'orange']);
        }

        // Pending prescriptions
        $pendRx = Prescription::where('status','pending')->count();
        if ($pendRx) {
            $alerts->push(['type'=>'rx','title'=>'Preskripsi Tertunggak','msg'=>$pendRx.' preskripsi belum disediakan','tone'=>'yellow']);
        }

        // Pending invoices
        if ($kpi['pending_inv']) {
            $alerts->push(['type'=>'inv','title'=>'Invois Belum Bayar','msg'=>$kpi['pending_inv'].' invois · RM '.number_format($kpi['pending_amount'],2),'tone'=>'yellow']);
        }

        // Open visits
        if ($kpi['open_visits']) {
            $alerts->push(['type'=>'visit','title'=>'Rekod Terbuka','msg'=>$kpi['open_visits'].' rekod EMR belum ditutup','tone'=>'blue']);
        }

        /* ── Revenue chart ── */
        $isCurrentPeriod = $filterMonth === (int) now()->month && $filterYear === (int) now()->year;

        if ($isCurrentPeriod) {
            $revChart = collect(range(6, 0))->map(function ($daysAgo) {
                $d = now()->subDays($daysAgo);
                return [
                    'label' => $d->isoFormat('ddd'),
                    'date'  => $d->format('Y-m-d'),
                    'value' => (float) Invoice::where('status','paid')->whereDate('paid_at',$d->format('Y-m-d'))->sum('total_amount'),
                ];
            })->values();
        } else {
            $dailyTotals = Invoice::where('status','paid')
                ->whereMonth('paid_at', $filterMonth)
                ->whereYear('paid_at', $filterYear)
                ->selectRaw('DATE(paid_at) as day, SUM(total_amount) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $daysInMonth = \Carbon\Carbon::create($filterYear, $filterMonth)->daysInMonth;
            $revChart = collect(range(1, $daysInMonth))->map(function ($day) use ($filterMonth, $filterYear, $dailyTotals) {
                $dateStr = sprintf('%04d-%02d-%02d', $filterYear, $filterMonth, $day);
                return [
                    'label' => (string) $day,
                    'date'  => $dateStr,
                    'value' => (float) ($dailyTotals[$dateStr] ?? 0),
                ];
            })->values();
        }

        /* ── Recent paid invoices (filtered by period) ── */
        $recentInvoices = Invoice::with('patient')
            ->where('status','paid')
            ->whereMonth('paid_at', $filterMonth)
            ->whereYear('paid_at', $filterYear)
            ->latest('paid_at')
            ->take(5)
            ->get()
            ->map(fn ($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'patient_name'   => $i->patient->name,
                'total_amount'   => $i->total_amount,
                'paid_at'        => $i->paid_at->format('d M, H:i'),
                'payment_method' => $i->payment_method,
            ]);

        $filterYears = range(2015, (int) now()->year);

        return Inertia::render('Dashboard', [
            'currentRoute'    => 'dashboard',
            'kpi'             => $kpi,
            'upcoming'        => $upcoming,
            'recentVisits'    => $recentVisits,
            'alerts'          => $alerts->values(),
            'revChart'        => $revChart,
            'recentInvoices'  => $recentInvoices,
            'userName'        => $user?->name ?? 'Pengguna',
            'today'           => now()->isoFormat('dddd, D MMMM YYYY'),
            'selectedMonth'   => $filterMonth,
            'selectedYear'    => $filterYear,
            'filterYears'     => $filterYears,
            'isCurrentPeriod' => $isCurrentPeriod,
        ]);
    }
}
