<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<title>{{ $rx->rx_number }} · Label Ubat · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Print page size (applies at print time regardless of media query) ── */
@page { size: 80mm 50mm; margin: 0; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7px;
    color: #000;
    background: #fff;
}

/* ════════════════════════════
   Label: exactly 80 × 50 mm
   ════════════════════════════ */
.label {
    width: 80mm;
    height: 50mm;
    border: 1px solid #000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ── HEADER (3 columns) ── */
.lbl-header {
    display: flex;
    border-bottom: 1px solid #000;
    flex-shrink: 0;
}
.lbl-header-logo {
    width: 16mm;
    flex-shrink: 0;
    padding: 1mm 1mm;
    border-right: 1px solid #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.lbl-header-info {
    flex: 1;
    min-width: 0;
    padding: 1mm 1.5mm;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.lbl-header-qr {
    width: 14mm;
    flex-shrink: 0;
    padding: 1mm;
    border-left: 1px solid #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.lbl-logo        { width: 11mm; height: 9mm; object-fit: contain; }
.lbl-ckaps       { font-size: 5px; margin-top: 0.5mm; line-height: 1.2; }

.lbl-clinic-name { font-size: 9px; font-weight: 900; letter-spacing: 1.5px; line-height: 1.1; }
.lbl-company     { font-size: 5.5px; font-weight: 600; margin-top: 0.5mm; line-height: 1.3; }
.lbl-address     { font-size: 5.5px; line-height: 1.3; margin-top: 0.3mm; }
.lbl-contact     { font-size: 5.5px; font-weight: 700; margin-top: 0.5mm; line-height: 1.3; }

.lbl-qr-img      { width: 10mm; height: 10mm; display: block; }
.lbl-scan        { font-size: 5px; margin-top: 0.5mm; }

/* ── PATIENT ── */
.lbl-patient {
    padding: 0.8mm 2mm;
    border-bottom: 1px solid #000;
    flex-shrink: 0;
}
.lbl-pt-row { font-size: 7px; line-height: 1.7; }
.lbl-pt-row b { font-weight: 700; }

/* ── DOSAGE TABLE ── */
.lbl-dose-wrap {
    border-bottom: 1px solid #000;
    padding: 0.5mm 2mm;
    flex-shrink: 0;
}
.dose-tbl { width: 100%; border-collapse: collapse; }
.dose-tbl td {
    padding: 0.4mm 0.5mm;
    font-size: 7px;
    vertical-align: middle;
    white-space: nowrap;
}
.td-lang  { width: 9mm; }
.td-num   { width: 9mm; text-align: center; padding: 0; }
.dose-num-box {
    width: 7mm;
    height: 7mm;
    border: 1.5px solid #000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 900;
    margin: 0 auto;
}
tr.dose-row-bm td { border-top: 0.5px solid #ccc; }

/* ── CHECKBOXES ── */
.lbl-checks {
    display: flex;
    align-items: center;
    gap: 3mm;
    padding: 0.8mm 2mm;
    border-bottom: 1px solid #000;
    flex-shrink: 0;
}
.lbl-check-item {
    display: flex;
    align-items: center;
    gap: 1.2mm;
    font-size: 6.5px;
}
.check-sq {
    width: 5mm;
    height: 5mm;
    border: 1.5px solid #000;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.check-tick { font-size: 10px; font-weight: 900; line-height: 1; }

/* ── FOOTER ── */
.lbl-footer {
    background: #000;
    color: #fff;
    text-align: center;
    font-size: 7px;
    font-weight: 900;
    padding: 1mm 2mm;
    letter-spacing: 1px;
    margin-top: auto;
}

/* ════════════════════════════════════
   Screen preview  (2× scale)
   ════════════════════════════════════ */
@media screen {
    body {
        background: #d1d5db;
        padding: 60px 0 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8mm;
    }
    /* print-bar */
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
    /* Print settings reminder */
    .print-hint {
        background: #fffbe6; border: 1px solid #f0c040;
        border-radius: 6px; padding: 7px 16px;
        font-size: 12px; color: #7a5a00;
        display: flex; align-items: center; gap: 8px;
    }
    .print-hint b { font-weight: 700; }
    /* wrapper provides the 2× footprint so the page scrolls correctly */
    .label-wrap {
        width: 160mm;
        height: 100mm;
        overflow: hidden;
        flex-shrink: 0;
    }
    .label {
        transform: scale(2);
        transform-origin: top left;
    }
}

/* ════════════════════════════════════
   Print
   ════════════════════════════════════ */
@media print {
    .print-bar  { display: none !important; }
    .print-hint { display: none !important; }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Each wrapper = one page. Do NOT use display:contents — it breaks :last-child. */
    .label-wrap {
        display: block;
        width: 80mm;
        height: 50mm;
        overflow: hidden;
        page-break-after: always;
        break-after: page;
    }
    .label-wrap:last-child {
        page-break-after: avoid;
        break-after: avoid;
    }

    .label {
        transform: none !important;
        width: 80mm !important;
        height: 50mm !important;
    }
}
</style>
</head>
<body>

@php
$unitMap = [
    'Tablet'     => ['en' => 'Tablet',  'bm' => 'Biji'],
    'Kapsul'     => ['en' => 'Capsule', 'bm' => 'Kapsul'],
    'Sirup'      => ['en' => 'ML',      'bm' => 'ML'],
    'MDI'        => ['en' => 'Spray',   'bm' => 'Semburan'],
    'Titis'      => ['en' => 'Drop',    'bm' => 'Titik'],
    'Serbuk'     => ['en' => 'Sachet',  'bm' => 'Sachet'],
    'Suntikan'   => ['en' => 'ml',      'bm' => 'ml'],
    'Supositari' => ['en' => 'pc',      'bm' => 'biji'],
    'Krim'       => ['en' => 'g',       'bm' => 'g'],
    'Gel'        => ['en' => 'g',       'bm' => 'g'],
    'Patch'      => ['en' => 'patch',   'bm' => 'patch'],
];

$freqMap = [
    'OD'  => 1, 'OD — 1× sehari'  => 1,
    'BD'  => 2, 'BD — 2× sehari'  => 2,
    'TDS' => 3, 'TDS — 3× sehari' => 3,
    'QID' => 4, 'QID — 4× sehari' => 4,
    'ON'  => 1, 'ON — Malam'      => 1,
    'PRN' => null, 'PRN — Bila perlu' => null,
];

$mealMap = [
    'Selepas makan'          => ['en' => 'After Food',              'bm' => 'Selepas Makan'],
    'Sebelum makan'          => ['en' => 'Before Food',             'bm' => 'Sebelum Makan'],
    'Waktu pagi'             => ['en' => 'Morning',                 'bm' => 'Waktu Pagi'],
    'Sebelum tidur'          => ['en' => 'Before Bed',              'bm' => 'Sebelum Tidur'],
    'Bila perlu'             => ['en' => 'When Necessary',          'bm' => 'Bila Perlu'],
    '30 min sebelum sarapan' => ['en' => '30 min Before Breakfast', 'bm' => '30 min Sblm Sarapan'],
];
@endphp

<div class="print-bar">
    <span class="print-bar__title">
        Label Ubat · {{ $rx->rx_number }} · {{ $rx->patient->name }}
        &nbsp;({{ $rx->items->count() }} label)
    </span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Cetak Label
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="print-hint">
    ⚙️ <span>Tetapan cetak yang betul:
    <b>Saiz kertas → 80 × 50 mm</b> &nbsp;·&nbsp;
    <b>Jidar → Tiada (None)</b> &nbsp;·&nbsp;
    <b>Skala → 100%</b> &nbsp;·&nbsp;
    Nyahpilih "Header and footers"</span>
</div>

@foreach($rx->items as $item)
@php
    /* ── Drug unit ── */
    $dosageLower = strtolower($item->dosage ?? '');
    if ($item->drug_unit && isset($unitMap[$item->drug_unit])) {
        $unit = $unitMap[$item->drug_unit];
    } elseif (str_contains($dosageLower, 'tablet'))  {
        $unit = $unitMap['Tablet'];
    } elseif (str_contains($dosageLower, 'kapsul') || str_contains($dosageLower, 'capsule')) {
        $unit = $unitMap['Kapsul'];
    } elseif (str_contains($dosageLower, 'ml') || str_contains($dosageLower, 'sirup')) {
        $unit = $unitMap['Sirup'];
    } elseif (str_contains($dosageLower, 'titik') || str_contains($dosageLower, 'titis') || str_contains($dosageLower, 'drop')) {
        $unit = $unitMap['Titis'];
    } elseif (str_contains($dosageLower, 'semburan') || str_contains($dosageLower, 'spray') || str_contains($dosageLower, 'mdi')) {
        $unit = $unitMap['MDI'];
    } elseif (str_contains($dosageLower, 'sachet') || str_contains($dosageLower, 'serbuk')) {
        $unit = $unitMap['Serbuk'];
    } else {
        $unit = null;
    }
    $unitEn = $unit['en'] ?? '';
    $unitBm = $unit['bm'] ?? '';

    /* ── Dose number (first number in dosage string) ── */
    preg_match('/^\d+(\.\d+)?/', trim($item->dosage ?? ''), $dm);
    $doseNum = $dm[0] ?? '—';

    /* ── Frequency number ── */
    $freqNum = $freqMap[$item->frequency] ?? null;
    if ($freqNum === null) {
        preg_match('/(\d+)\s*[xX×]\s*sehari/i', $item->frequency ?? '', $fm);
        $freqNum = isset($fm[1]) ? (int)$fm[1] : null;
    }
    $freqDisp = $freqNum !== null ? $freqNum : '—';

    /* ── Meal timing ── */
    $meal   = $mealMap[$item->instructions] ?? null;
    $mealEn = $meal ? $meal['en'] : ($item->instructions ?: '');
    $mealBm = $meal ? $meal['bm'] : ($item->instructions ?: '');

    /* ── Checkboxes ── */
    $isPrn = $item->is_prn
        || str_contains(strtolower($item->frequency ?? ''), 'prn')
        || str_contains(strtolower($item->frequency ?? ''), 'bila perlu')
        || str_contains(strtolower($item->instructions ?? ''), 'bila perlu');

    $completeCourse = $item->complete_course
        || str_contains(strtolower($item->instructions ?? ''), 'habiskan');
@endphp

<div class="label-wrap">
<div class="label">

    {{-- ══ HEADER ══ --}}
    <div class="lbl-header">
        <div class="lbl-header-logo">
            <img src="{{ $clinic->logo_url }}" alt="" class="lbl-logo" />
            @if($clinic->ckaps_number)
            <div class="lbl-ckaps">CKAPS:<br>{{ $clinic->ckaps_number }}</div>
            @endif
        </div>

        <div class="lbl-header-info">
            <div class="lbl-clinic-name">{{ strtoupper($clinic->name) }}</div>
            <div class="lbl-company">
                {{ $clinic->tagline }}@if($clinic->reg_number) (SSM: {{ $clinic->reg_number }})@endif
            </div>
            <div class="lbl-address">
                {{ $clinic->address }}, {{ $clinic->postcode }} {{ $clinic->city }}, {{ $clinic->state }}
            </div>
            <div class="lbl-contact">
                {{ $clinic->phone }}@if($clinic->email) · {{ $clinic->email }}@endif
            </div>
        </div>

        <div class="lbl-header-qr">
            <img class="lbl-qr-img"
                 src="https://api.qrserver.com/v1/create-qr-code/?size=40x40&data={{ urlencode($clinic->email ?? $clinic->phone) }}"
                 alt="QR" />
            <div class="lbl-scan">scan me</div>
        </div>
    </div>

    {{-- ══ PATIENT ══ --}}
    <div class="lbl-patient">
        <div class="lbl-pt-row"><b>Nama:</b> {{ $rx->patient->name }}</div>
        <div class="lbl-pt-row"><b>Tarikh:</b> {{ $rx->created_at->format('d/m/Y') }}</div>
        <div class="lbl-pt-row">
            <b>Nama Ubat / Kegunaan:</b>
            {{ $item->drug_name }}@if($item->kegunaan) / {{ $item->kegunaan }}@endif
        </div>
    </div>

    {{-- ══ DOSAGE ══ --}}
    <div class="lbl-dose-wrap">
        <table class="dose-tbl">
            <tr class="dose-row-en">
                <td class="td-lang">Take</td>
                <td class="td-num" rowspan="2">
                    <div class="dose-num-box">{{ $doseNum }}</div>
                </td>
                <td class="td-unit">{{ $unitEn }}</td>
                <td class="td-num" rowspan="2">
                    <div class="dose-num-box">{{ $freqDisp }}</div>
                </td>
                <td class="td-right">Times Daily{{ $mealEn ? ' · ' . $mealEn : '' }}</td>
            </tr>
            <tr class="dose-row-bm">
                <td class="td-lang">Makan</td>
                <td class="td-unit">{{ $unitBm }}</td>
                <td class="td-right">Kali Sehari{{ $mealBm ? ' · ' . $mealBm : '' }}</td>
            </tr>
        </table>
    </div>

    {{-- ══ CHECKBOXES ══ --}}
    <div class="lbl-checks">
        <div class="lbl-check-item">
            <div class="check-sq">@if($isPrn)<span class="check-tick">✓</span>@endif</div>
            <span>Bila Perlu / When Necessary</span>
        </div>
        <div class="lbl-check-item">
            <div class="check-sq">@if($completeCourse)<span class="check-tick">✓</span>@endif</div>
            <span>Habiskan Ubat / To Complete Medicine</span>
        </div>
    </div>

    {{-- ══ FOOTER ══ --}}
    <div class="lbl-footer">UBAT TERKAWAL / CONTROLLED MEDICINE</div>

</div>{{-- .label --}}
</div>{{-- .label-wrap --}}
@endforeach

</body>
</html>
