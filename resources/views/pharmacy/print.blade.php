<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $rx->rx_number }} · Kertas Ubat · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #fff;
}

/* ── A5 page ── */
.page {
    width: 148mm;
    min-height: 210mm;
    margin: 0 auto;
    padding: 8mm 10mm 8mm;
    display: flex;
    flex-direction: column;
}

/* ── Header ── */
.hd {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 7px;
    border-bottom: 2px solid #1b8a4a;
    margin-bottom: 8px;
}
.hd-brand { display: flex; align-items: center; gap: 8px; }
.hd-logo  { width: 36px; height: 36px; border-radius: 6px; object-fit: contain; border: 1px solid #e5e7eb; }
.hd-name  { font-size: 14px; font-weight: 800; color: #1b8a4a; line-height: 1.2; }
.hd-sub   { font-size: 9px; color: #6b7280; margin-top: 1px; }
.hd-right { text-align: right; font-size: 9px; color: #6b7280; line-height: 1.6; }
.hd-right strong { display: block; font-size: 9.5px; color: #374151; }

/* ── Rx number row ── */
.rx-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 6px 10px;
    margin-bottom: 8px;
}
.rx-num  { font: 800 16px 'Courier New', monospace; color: #1b8a4a; letter-spacing: .02em; }
.rx-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.rx-date { font-size: 9px; color: #6b7280; }

.badge {
    display: inline-block; padding: 2px 8px; border-radius: 999px;
    font-size: 9px; font-weight: 700;
}
.badge-dispensed { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.badge-ready     { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-verifying { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-pending   { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.badge-cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Patient + Doctor 2-col ── */
.info-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-bottom: 7px;
}
.info-box {
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    padding: 6px 8px;
}
.info-box__title {
    font: 700 8px 'Segoe UI', Arial, sans-serif;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #9ca3af;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 4px;
    margin-bottom: 4px;
}
.info-row { display: flex; gap: 5px; align-items: baseline; margin-bottom: 2px; }
.info-row:last-child { margin-bottom: 0; }
.info-lbl { font: 500 9px 'Segoe UI', Arial, sans-serif; color: #9ca3af; width: 70px; flex-shrink: 0; }
.info-val { font: 600 10px 'Segoe UI', Arial, sans-serif; color: #111827; }

/* ── Allergy alert ── */
.allergy-alert {
    background: #fff7ed;
    border-left: 3px solid #f97316;
    border-radius: 0 5px 5px 0;
    padding: 5px 8px;
    font: 700 9.5px 'Segoe UI', Arial, sans-serif;
    color: #9a3412;
    margin-bottom: 7px;
}

/* ── Section title ── */
.section-title {
    font: 700 8.5px 'Segoe UI', Arial, sans-serif;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #6b7280;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

/* ── Drug list ── */
.drug-list { display: flex; flex-direction: column; gap: 5px; margin-bottom: 7px; }
.drug-card {
    border: 1px solid #e5e7eb;
    border-left: 3px solid #1b8a4a;
    border-radius: 0 5px 5px 0;
    padding: 6px 8px;
    background: #fafafa;
}
.drug-card__name {
    font: 700 11px 'Segoe UI', Arial, sans-serif;
    color: #111827;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.drug-num {
    display: inline-flex;
    align-items: center; justify-content: center;
    width: 16px; height: 16px;
    background: #1b8a4a; color: #fff;
    border-radius: 50%;
    font: 700 8px 'Segoe UI', Arial, sans-serif;
    flex-shrink: 0;
}
.drug-detail-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    margin-bottom: 3px;
}
.drug-detail__lbl { font: 500 8px 'Segoe UI', Arial, sans-serif; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
.drug-detail__val { font: 600 10px 'Segoe UI', Arial, sans-serif; color: #374151; }
.drug-qty         { font: 700 12px 'Segoe UI', Arial, sans-serif; color: #1b8a4a; }
.drug-instr {
    font: 500 9.5px 'Segoe UI', Arial, sans-serif;
    color: #1d4ed8;
    background: #eff6ff;
    border-radius: 3px;
    padding: 2px 6px;
    display: inline-block;
    margin-top: 3px;
}

/* ── Notes ── */
.notes-box {
    background: #fffbeb;
    border-left: 3px solid #f59e0b;
    border-radius: 0 5px 5px 0;
    padding: 5px 8px;
    margin-bottom: 7px;
}
.notes-box__lbl { font: 700 8px 'Segoe UI', Arial, sans-serif; color: #92400e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
.notes-box__txt { font: 400 9.5px 'Segoe UI', Arial, sans-serif; color: #78350f; white-space: pre-wrap; line-height: 1.5; }

/* ── Dispensed info ── */
.dispensed-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 5px;
    padding: 6px 10px;
    margin-bottom: 7px;
    display: flex;
    gap: 16px;
}
.dispensed-box .ditem { display: flex; flex-direction: column; gap: 1px; }
.dispensed-box .dlbl  { font: 500 8px 'Segoe UI', Arial, sans-serif; color: #16a34a; text-transform: uppercase; letter-spacing: .05em; }
.dispensed-box .dval  { font: 600 10px 'Segoe UI', Arial, sans-serif; color: #14532d; }

/* ── Sig area ── */
.sig-area {
    margin-top: auto;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}
.sig-block { display: flex; flex-direction: column; align-items: center; }
.sig-block__line  { width: 100%; border-bottom: 1px solid #9ca3af; height: 28px; margin-bottom: 4px; }
.sig-block__label { font: 600 8px 'Segoe UI', Arial, sans-serif; color: #9ca3af; text-align: center; line-height: 1.4; }

/* ── Footer ── */
.footer {
    padding-top: 6px;
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #e5e7eb;
    margin-top: 6px;
}
.footer__left  { font: 400 8px 'Segoe UI', Arial, sans-serif; color: #9ca3af; line-height: 1.6; }
.footer__right { font: 500 8px 'Courier New', monospace; color: #d1d5db; }

/* ── Screen print bar ── */
@media screen {
    body { background: #e5e7eb; padding: 24px 0 40px; }
    .page {
        box-shadow: 0 4px 20px rgba(0,0,0,.15);
        background: #fff;
    }
    .print-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        background: #1b8a4a; padding: 9px 20px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .print-bar__title { color: #fff; font-size: 12px; font-weight: 600; }
    .print-bar__actions { display: flex; gap: 8px; }
    .print-bar__btn {
        background: #fff; color: #1b8a4a; border: none;
        padding: 6px 18px; border-radius: 6px; font-size: 12px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; gap: 7px;
    }
    .print-bar__close {
        background: rgba(255,255,255,.15); color: #fff; border: none;
        padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;
    }
    body { padding-top: 50px; }
}

@media print {
    .print-bar { display: none !important; }
    body { padding: 0; background: #fff; }
    .page { box-shadow: none; padding: 7mm 9mm; width: 100%; min-height: unset; }
    @page { margin: 0; size: A5 portrait; }
}
</style>
</head>
<body>

<div class="print-bar">
    <span class="print-bar__title">{{ $rx->rx_number }} · {{ $rx->patient->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="page">

    {{-- Header --}}
    <div class="hd">
        <div class="hd-brand">
            <img src="{{ $clinic->logo_url }}" alt="" class="hd-logo" />
            <div>
                <div class="hd-name">{{ $clinic->name }}</div>
                <div class="hd-sub">Kertas Ubat / Prescription</div>
            </div>
        </div>
        <div class="hd-right">
            <strong>{{ $clinic->name }}</strong>
            {{ $clinic->address }}<br>
            {{ $clinic->postcode }} {{ $clinic->city }}, {{ $clinic->state }}<br>
            Tel: {{ $clinic->phone }}@if($clinic->fax) · Faks: {{ $clinic->fax }}@endif
        </div>
    </div>

    {{-- Rx number row --}}
    <div class="rx-row">
        <div class="rx-num">{{ $rx->rx_number }}</div>
        <div class="rx-meta">
            @php
                $statusLabels = [
                    'pending'   => 'Menunggu',
                    'verifying' => 'Disemak',
                    'ready'     => 'Sedia',
                    'dispensed' => 'Dikeluarkan',
                    'cancelled' => 'Dibatalkan',
                ];
            @endphp
            <span class="badge badge-{{ $rx->status }}">{{ $statusLabels[$rx->status] ?? $rx->status }}</span>
            <span class="rx-date">{{ $rx->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- Patient + Doctor info --}}
    <div class="info-cols">
        <div class="info-box">
            <div class="info-box__title">Pesakit</div>
            <div class="info-row">
                <span class="info-lbl">Nama</span>
                <span class="info-val">{{ $rx->patient->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">No. KP</span>
                <span class="info-val">{{ $rx->patient->ic_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">ID</span>
                <span class="info-val">{{ $rx->patient->patient_id }}</span>
            </div>
            @if($rx->patient->date_of_birth)
            <div class="info-row">
                <span class="info-lbl">Tarikh Lahir</span>
                <span class="info-val">{{ \Carbon\Carbon::parse($rx->patient->date_of_birth)->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>
        <div class="info-box">
            <div class="info-box__title">Preskripsi</div>
            <div class="info-row">
                <span class="info-lbl">Doktor</span>
                <span class="info-val">{{ $rx->prescribing_doctor }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Tarikh</span>
                <span class="info-val">{{ $rx->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Jumlah Ubat</span>
                <span class="info-val">{{ $rx->items->count() }} jenis</span>
            </div>
            @if($rx->dispensed_by)
            <div class="info-row">
                <span class="info-lbl">Farmasi</span>
                <span class="info-val">{{ $rx->dispensed_by }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Allergy alert --}}
    @if($rx->patient->allergies)
    <div class="allergy-alert">⚠ ALAHAN: {{ $rx->patient->allergies }}</div>
    @endif

    {{-- Drug list --}}
    <div class="section-title">Senarai Ubat ({{ $rx->items->count() }} jenis)</div>
    <div class="drug-list">
        @foreach($rx->items as $i => $item)
        <div class="drug-card">
            <div class="drug-card__name">
                <span class="drug-num">{{ $i + 1 }}</span>
                {{ $item->drug_name }}
            </div>
            <div class="drug-detail-grid">
                @if($item->dosage)
                <div>
                    <div class="drug-detail__lbl">Dos</div>
                    <div class="drug-detail__val">{{ $item->dosage }}</div>
                </div>
                @endif
                @if($item->frequency)
                <div>
                    <div class="drug-detail__lbl">Kekerapan</div>
                    <div class="drug-detail__val">{{ $item->frequency }}</div>
                </div>
                @endif
                @if($item->duration)
                <div>
                    <div class="drug-detail__lbl">Tempoh</div>
                    <div class="drug-detail__val">{{ $item->duration }}</div>
                </div>
                @endif
                <div>
                    <div class="drug-detail__lbl">Kuantiti</div>
                    <div class="drug-qty">{{ $item->quantity }}</div>
                </div>
            </div>
            @if($item->instructions)
            <div class="drug-instr">⚕ {{ $item->instructions }}</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Notes --}}
    @if($rx->notes)
    <div class="notes-box">
        <div class="notes-box__lbl">Nota Doktor</div>
        <div class="notes-box__txt">{{ $rx->notes }}</div>
    </div>
    @endif

    {{-- Dispensed info --}}
    @if($rx->status === 'dispensed' && $rx->dispensed_at)
    <div class="dispensed-box">
        <div class="ditem">
            <span class="dlbl">Dikeluarkan Pada</span>
            <span class="dval">{{ $rx->dispensed_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="ditem">
            <span class="dlbl">Dikeluarkan Oleh</span>
            <span class="dval">{{ $rx->dispensed_by }}</span>
        </div>
        <div class="ditem">
            <span class="dlbl">Semakan AI</span>
            <span class="dval" style="color:{{ $rx->drug_check_passed ? '#16a34a' : '#dc2626' }}">
                {{ $rx->drug_check_passed ? '✓ Lulus' : '✗ Gagal' }}
            </span>
        </div>
    </div>
    @endif

    {{-- Signature area --}}
    <div class="sig-area">
        <div class="sig-block">
            <div class="sig-block__line"></div>
            <div class="sig-block__label">Tandatangan Doktor<br>{{ $rx->prescribing_doctor }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-block__line"></div>
            <div class="sig-block__label">Tandatangan<br>Ahli Farmasi</div>
        </div>
        <div class="sig-block">
            <div class="sig-block__line"></div>
            <div class="sig-block__label">Tandatangan<br>Penerima / Waris</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer__left">
            Dicetak: {{ now()->format('d/m/Y H:i') }} · Simpan untuk rujukan.
        </div>
        <div class="footer__right">{{ $rx->rx_number }}</div>
    </div>

</div>
</body>
</html>
