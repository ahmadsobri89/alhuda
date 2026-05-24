<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $invoice->invoice_number }} · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a1a;
    background: #fff;
    padding: 0;
}

.page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 16mm 18mm 14mm;
    display: flex;
    flex-direction: column;
    position: relative;
}

/* ── Letterhead header ── */
.lh-wrap {
    margin: -16mm -18mm 14px;
    flex-shrink: 0;
    line-height: 0;
}
.lh-wrap img { width: 100%; display: block; height: auto; }

/* ── Watermark ── */
.watermark {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font: 700 70px 'Segoe UI', Arial, sans-serif;
    color: rgba(27,138,74,.04);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    letter-spacing: 6px;
}
.content { position: relative; z-index: 1; display: flex; flex-direction: column; flex: 1; }

/* ── Invoice meta ── */
.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 20px;
}
.meta-block { display: flex; flex-direction: column; gap: 4px; }
.meta-block__label { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
.meta-block__value { font-size: 13.5px; font-weight: 700; color: #111827; }
.meta-block__sub   { font-size: 11.5px; color: #6b7280; margin-top: 1px; }

.badge {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .03em;
}
.badge-paid      { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-unpaid    { background: #fef9c3; color: #b45309; border: 1px solid #fde68a; }
.badge-draft     { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.badge-cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Divider ── */
.divider { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }

/* ── Items table ── */
.tbl { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.tbl thead tr { background: #f0fdf4; }
.tbl th {
    padding: 8px 10px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #374151;
    border-bottom: 1.5px solid #1b8a4a;
    text-align: left;
}
.tbl th.r, .tbl td.r { text-align: right; }
.tbl td {
    padding: 8px 10px;
    font-size: 12.5px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl td .item-code { font-size: 10.5px; color: #9ca3af; font-family: 'Courier New', monospace; margin-top: 2px; }
.tbl td .item-type {
    display: inline-block; padding: 1px 7px; border-radius: 4px; font-size: 10px; font-weight: 600;
    background: #eff6ff; color: #1d4ed8; margin-bottom: 3px;
}

/* ── Totals ── */
.totals { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; margin-top: 8px; }
.total-row { display: flex; gap: 40px; justify-content: flex-end; }
.total-row .lbl { font-size: 12px; color: #6b7280; width: 120px; text-align: right; }
.total-row .val { font-size: 12.5px; font-weight: 600; color: #374151; width: 80px; text-align: right; font-family: 'Courier New', monospace; }
.total-row.final .lbl { font-size: 14px; font-weight: 800; color: #111827; }
.total-row.final .val { font-size: 16px; font-weight: 800; color: #1b8a4a; }
.total-divider { width: 240px; border: none; border-top: 1.5px solid #e5e7eb; margin: 4px 0; }

/* ── Payment info ── */
.payment-box {
    margin-top: 20px;
    padding: 12px 16px;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 10px;
}
.payment-box__title { font-size: 12px; font-weight: 700; color: #16a34a; margin-bottom: 8px; }
.payment-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
.payment-grid .pg-item { display: flex; flex-direction: column; gap: 2px; }
.payment-grid .pg-label { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; }
.payment-grid .pg-value { font-size: 12.5px; font-weight: 600; color: #374151; }

/* ── Notes ── */
.notes-box {
    margin-top: 16px;
    padding: 10px 14px;
    background: #fffbeb;
    border-left: 3px solid #f59e0b;
    border-radius: 0 8px 8px 0;
}
.notes-box__label { font-size: 10px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.notes-box__text  { font-size: 12px; color: #78350f; white-space: pre-wrap; line-height: 1.6; }

/* ── Footer ── */
.footer {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
}
.footer-left { font-size: 10.5px; color: #9ca3af; line-height: 1.7; }
.footer-sig  { text-align: center; }
.footer-sig__line { width: 140px; border-bottom: 1px solid #9ca3af; margin-bottom: 4px; height: 36px; }
.footer-sig__label { font-size: 10px; color: #9ca3af; }

@media screen {
    body { background: #f3f4f6; padding: 24px 0; }
    .page { box-shadow: 0 4px 24px rgba(0,0,0,.12); }

    .print-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        background: #1b8a4a; padding: 10px 24px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .print-bar__title { color: #fff; font-size: 13px; font-weight: 600; }
    .print-bar__btn {
        background: #fff; color: #1b8a4a; border: none;
        padding: 7px 20px; border-radius: 7px; font-size: 13px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; gap: 8px;
    }
    .print-bar__close {
        background: rgba(255,255,255,.15); color: #fff; border: none;
        padding: 7px 14px; border-radius: 7px; font-size: 13px;
        cursor: pointer; margin-left: 8px;
    }
    body { padding-top: 52px; }
}

@media print {
    .print-bar { display: none !important; }
    body { padding: 0; background: #fff; }
    .page { box-shadow: none; padding: 16mm 18mm 14mm; width: 100%; }
    @page { margin: 0; size: A4; }
}
</style>
</head>
<body>

{{-- Screen-only print bar --}}
<div class="print-bar">
    <span class="print-bar__title">{{ $invoice->invoice_number }} · {{ $invoice->patient->name }}</span>
    <div style="display:flex;gap:0">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="page">
    <div class="watermark">AL-HUDA</div>
    <div class="content">

    {{-- ── Letterhead ── --}}
    <div class="lh-wrap">
        <img src="{{ asset('images/letterheadtop.png') }}" alt="{{ $clinic->name }}" />
    </div>

    {{-- ── Invoice meta ── --}}
    <div class="meta-row">
        <div>
            <div class="meta-block__label" style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Pesakit</div>
            <div style="font:700 15px 'Segoe UI',Arial,sans-serif;color:#111827">{{ $invoice->patient->name }}</div>
            <div style="font:400 12px 'Segoe UI',Arial,sans-serif;color:#6b7280;margin-top:2px">
                No. KP: {{ $invoice->patient->ic_number }}
                &nbsp;·&nbsp;
                ID: {{ $invoice->patient->patient_id }}
            </div>
        </div>

        <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
            <div style="font:800 22px 'Courier New',monospace;color:#111827;letter-spacing:.03em">{{ $invoice->invoice_number }}</div>
            <div>
                <span class="badge badge-{{ $invoice->status }}">
                    @php $labels=['draft'=>'Draf','unpaid'=>'Belum Bayar','paid'=>'Telah Dibayar','cancelled'=>'Dibatalkan']; @endphp
                    {{ $labels[$invoice->status] ?? $invoice->status }}
                </span>
            </div>
            <div style="font:500 11.5px 'Segoe UI',Arial,sans-serif;color:#6b7280">
                Tarikh: {{ $invoice->invoice_date->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ── Items table ── --}}
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:90px">Jenis</th>
                <th>Perkhidmatan / Ubat</th>
                <th class="r" style="width:60px">Kuantiti</th>
                <th class="r" style="width:90px">Harga Unit</th>
                <th class="r" style="width:90px">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
            <tr>
                <td>
                    @php
                        $typeLabels = [
                            'consultation' => 'Konsultasi',
                            'procedure'    => 'Prosedur',
                            'drug'         => 'Ubat',
                            'lab'          => 'Makmal',
                            'other'        => 'Lain-lain',
                        ];
                        $typeColors = [
                            'consultation' => 'background:#eff6ff;color:#1d4ed8',
                            'procedure'    => 'background:#faf5ff;color:#7c3aed',
                            'drug'         => 'background:#f0fdf4;color:#16a34a',
                            'lab'          => 'background:#fefce8;color:#b45309',
                            'other'        => 'background:#f3f4f6;color:#6b7280',
                        ];
                    @endphp
                    <span class="item-type" style="{{ $typeColors[$item->type] ?? '' }}">
                        {{ $typeLabels[$item->type] ?? $item->type }}
                    </span>
                </td>
                <td>
                    <div style="font-weight:600">{{ $item->description }}</div>
                    @if($item->code)
                        <div class="item-code">{{ $item->code }}</div>
                    @endif
                </td>
                <td class="r">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                <td class="r">{{ number_format($item->unit_price, 2) }}</td>
                <td class="r" style="font-weight:700">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#9ca3af;padding:20px">Tiada item.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    <div class="totals">
        <div class="total-row">
            <span class="lbl">Subtotal</span>
            <span class="val">RM {{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        @if($invoice->discount_amount > 0)
        <div class="total-row">
            <span class="lbl">Diskaun</span>
            <span class="val" style="color:#dc2626">- RM {{ number_format($invoice->discount_amount, 2) }}</span>
        </div>
        @endif
        <hr class="total-divider">
        <div class="total-row final">
            <span class="lbl">JUMLAH</span>
            <span class="val">RM {{ number_format($invoice->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- ── Payment info ── --}}
    @if($invoice->status === 'paid')
    <div class="payment-box">
        <div class="payment-box__title">✓ Pembayaran Diterima</div>
        <div class="payment-grid">
            <div class="pg-item">
                <span class="pg-label">Kaedah</span>
                @php
                    $methodLabels = [
                        'cash'      => 'Tunai',
                        'card'      => 'Kad Kredit/Debit',
                        'duitnow'   => 'DuitNow',
                        'panel'     => 'Panel',
                        'insurance' => 'Insurans',
                    ];
                @endphp
                <span class="pg-value">{{ $methodLabels[$invoice->payment_method] ?? $invoice->payment_method }}</span>
            </div>
            <div class="pg-item">
                <span class="pg-label">Tarikh Bayar</span>
                <span class="pg-value">{{ $invoice->paid_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="pg-item">
                <span class="pg-label">Diterima Oleh</span>
                <span class="pg-value">{{ $invoice->paid_by }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Notes ── --}}
    @if($invoice->notes)
    <div class="notes-box">
        <div class="notes-box__label">Nota</div>
        <div class="notes-box__text">{{ $invoice->notes }}</div>
    </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="footer">
        <div class="footer-left">
            Dicetak pada {{ now()->format('d/m/Y H:i') }}<br>
            {{ $clinic->name }}@if($clinic->website) · {{ $clinic->website }}@endif<br>
            Dokumen ini dijana secara automatik oleh sistem.
        </div>
        <div class="footer-sig">
            <div class="footer-sig__line"></div>
            <div class="footer-sig__label">Tandatangan Penerima</div>
        </div>
    </div>

    </div>{{-- .content --}}
</div>
</body>
</html>
