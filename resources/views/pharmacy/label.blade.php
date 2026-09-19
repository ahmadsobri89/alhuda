<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<title>{{ $rx->rx_number }} · Label Ubat · {{ $clinic->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* Vozy U9 thermal label — dua saiz roll boleh dipilih, setiap satu dengan
   artwork tersendiri (susun aturnya memang berbeza, jadi tindanan teks
   untuk setiap saiz diukur berasingan — lihat blok .lt7-* di bawah):
     80 × 50 mm  · label-medicine.png        945×591px  @ 300 DPI
     100 × 70 mm · label-medicine-100x70.png 1181×827px @ 300 DPI
   Saiz @page ada dalam <style id="page-size"> — ditukar oleh setLabelSize(). */
:root {
    --label-w: 80mm;
    --label-h: 50mm;
    --art-ratio: 62.54%;   /* 591 / 945 */
}
html.size-100x70 {
    --label-w: 100mm;
    --label-h: 70mm;
    --art-ratio: 70.02%;   /* 827 / 1181 */
}

body {
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
    color: #000;
    background: #fff;
}

/* ════════════════════════════════════════
   Label container — locked to image ratio
   945 × 591  →  ratio = 591/945 = 62.54%
   All child % positions are always relative
   to this box, never to the viewport.
   ════════════════════════════════════════ */
.label-wrap-inner {
    position: relative;
    width: 100%;
    padding-bottom: var(--art-ratio); /* kekalkan nisbah artwork */
    /* Make font sizes scale with the label width (cqw units below),
       so the screen preview (≈945px) and the 80mm print match exactly. */
    container-type: inline-size;
}

.label-bg {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    display: block;
    object-fit: fill;
}

/* Hanya artwork + tindanan bagi saiz terpilih yang dipaparkan */
.for-100x70 { display: none; }
html.size-100x70 .for-80x50  { display: none; }
html.size-100x70 .for-100x70 { display: block; }

/* All text overlaid on top */
.label-text {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
}

/* ── Patient rows ── */
/* Nama */
.t-nama {
    position: absolute;
    top: 32%;
    left: 12%;
    right: 2%;
    font-size: 2.33cqw;
    font-weight: 700;
    white-space: nowrap;
    line-height: 1;
}
/* Tarikh */
.t-tarikh {
    position: absolute;
    top: 39.6%;
    left: 12%;
    right: 2%;
    font-size: 2.33cqw;
    font-weight: 700;
    white-space: nowrap;
    line-height: 1;
}
/* Nama Ubat */
.t-ubat {
    position: absolute;
    top: 47.5%;
    left: 33.5%;
    right: 2%;
    font-size: 2.50cqw;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1;
}

/* ── Dosage numbers ── */
.t-dose-num {
    position: absolute;
    top: 62.5%;
    left: 9.8%;
    width: 7.5%;
    text-align: center;
    font-size: 3.49cqw;
    font-weight: 900;
    line-height: 1;
}
.t-freq-num {
    position: absolute;
    top: 62.5%;
    left: 47%;
    width: 7.5%;
    text-align: center;
    font-size: 3.49cqw;
    font-weight: 900;
    line-height: 1;
}

/* ── Unit highlight (EN row) ── */
.t-unit-en {
    position: absolute;
    top: 59%;
    left: 20%;
    font-size: 2.43cqw;
    font-weight: bold;
    line-height: 1;
    display: flex;
    gap: 1px;
    align-items: center;
}
/* ── Unit highlight (BM row) ── */
.t-unit-bm {
    position: absolute;
    top: 67%;
    left: 20%;
    font-size: 2.43cqw;
    font-weight: bold;
    line-height: 1;
    display: flex;
    gap: 1px;
    align-items: center;
}

/* ── Meal timing ── */
.t-meal-en {
    position: absolute;
    top: 58%;
    left: 73%;
    right: 2%;
    font-size: 2.75cqw;
    font-weight: bold;
    line-height: 1;
    white-space: nowrap;
}
.t-meal-bm {
    position: absolute;
    top: 67%;
    left: 73%;
    right: 2%;
    font-size: 2.75cqw;
    font-weight: bold;
    line-height: 1;
    white-space: nowrap;
}

/* ── Item note ── */
.t-note {
    position: absolute;
    top: 81.5%;
    left: 3%;
    right: 3%;
    font-size: 2.12cqw;
    font-weight: bold;
    line-height: 1.3;
    color: #000;
    white-space: pre-wrap;
    word-break: break-word;
}

/* ── Checkboxes ticks ── */
.t-prn-tick {
    position: absolute;
    top: 81%;
    left: 3.8%;
    font-size: 4.23cqw;
    font-weight: 900;
    line-height: 1;
}
.t-complete-tick {
    position: absolute;
    top: 81%;
    left: 45.8%;
    font-size: 4.23cqw;
    font-weight: 900;
    line-height: 1;
}

/* ══════════════════════════════════════════════════════════
   Tindanan teks — saiz 100 × 70 mm
   Diukur terus dari label-medicine-100x70.png (1181 × 827 px).
   Susun aturnya berbeza daripada artwork 80×50:
     · "Nama Ubat" dan "Kegunaan" ialah DUA baris berasingan
     · kotak dos menjangkau kedua-dua baris Take/Makan (pusat 65.7%)
     · "Times Daily / Kali Sehari" sudah tercetak pada artwork
   `top` di sini ialah PUSAT baris (berpasangan dengan translateY(-50%)),
   jadi tak perlu teka cap-height seperti blok .t-* 80×50 di atas.
   ══════════════════════════════════════════════════════════ */
.lt7 > div {
    position: absolute;
    transform: translateY(-50%);
    line-height: 1;
    font-weight: 700;
}

/* Tampung teks contoh yang tercetak kekal pada artwork.
   Buang dua blok .lt7-patch ini (dan <div>nya) bila artwork
   diberi semula dengan medan-medan ini kosong. */
.lt7 > .lt7-patch { position: absolute; transform: none; background: #fff; }
.lt7-patch--meal { top: 66.3%; height: 7.2%; left: 54.2%; right: 2.2%; }
.lt7-patch--note { top: 74.5%; height: 9.6%; left: 15.0%; right: 2.2%; }

/* ── Baris pesakit ── */
.lt7-nama     { top: 35.0%; left: 14.0%; right: 2.5%; font-size: 2.55cqw; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lt7-tarikh   { top: 41.0%; left: 14.0%; right: 2.5%; font-size: 2.55cqw; white-space: nowrap; }
.lt7-ubat     { top: 47.0%; left: 21.4%; right: 2.5%; font-size: 2.55cqw; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lt7-kegunaan { top: 52.9%; left: 19.5%; right: 2.5%; font-size: 2.55cqw; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Nombor dos — ditengahkan dalam kotak artwork ── */
.lt7-dose-num { top: 65.7%; left: 11.94%; width: 6.35%; text-align: center; font-size: 3.40cqw; font-weight: 900; }
.lt7-freq-num { top: 65.7%; left: 47.42%; width: 6.35%; text-align: center; font-size: 3.40cqw; font-weight: 900; }

/* ── Unit ubat (ruang kosong antara dua kotak) ── */
.lt7-unit-en  { top: 61.5%; left: 20.5%; width: 26%; font-size: 2.25cqw; white-space: nowrap; overflow: hidden; }
.lt7-unit-bm  { top: 69.1%; left: 20.5%; width: 26%; font-size: 2.25cqw; white-space: nowrap; overflow: hidden; }

/* ── Waktu makan — ganti "Before / Sebelum Makan" artwork ── */
.lt7-meal     { top: 69.1%; left: 54.7%; right: 2.5%; font-size: 2.30cqw; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Nota item ── */
.lt7 > .lt7-note {
    top: 74.8%; left: 15.8%; right: 3%;
    transform: none;
    font-size: 2.20cqw;
    line-height: 1.3;
    white-space: pre-wrap;
    word-break: break-word;
}

/* ════════════════════════════════════
   Screen preview
   ════════════════════════════════════ */
@media screen {
    body {
        background: #d1d5db;
        padding: 60px 0 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }

    .print-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        background: #1b8a4a; padding: 9px 20px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .print-bar__title { color: #fff; font-size: 12px; font-weight: 600; }
    .print-bar__actions { display: flex; gap: 8px; align-items: center; }
    .print-bar__btn {
        background: #fff; color: #1b8a4a; border: none;
        padding: 6px 18px; border-radius: 6px; font-size: 12px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; gap: 7px;
    }
    .print-bar__close {
        background: rgba(255,255,255,.15); color: #fff; border: none;
        padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;
    }

    .nav-bar {
        display: flex; align-items: center; gap: 12px;
        background: rgba(255,255,255,.18); border-radius: 8px;
        padding: 4px 12px;
    }
    .nav-btn {
        background: rgba(255,255,255,.9); color: #1b8a4a; border: none;
        width: 28px; height: 28px; border-radius: 50%; font-size: 16px; font-weight: 900;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        line-height: 1;
    }
    .nav-btn:disabled { opacity: 0.35; cursor: default; }
    .nav-counter { color: #fff; font-size: 12px; font-weight: 600; min-width: 60px; text-align: center; }

    .print-hint {
        background: #fffbe6; border: 1px solid #f0c040;
        border-radius: 6px; padding: 7px 16px; margin-bottom: 16px;
        font-size: 12px; color: #7a5a00;
        display: flex; align-items: center; gap: 8px;
    }
    .print-hint b { font-weight: 700; }

    .label-wrap {
        display: none;
        width: 100%;
        max-width: 945px;
    }
    .label-wrap.active { display: block; }

    /* Pemilih saiz label */
    .size-bar {
        display: flex; gap: 3px;
        background: rgba(255,255,255,.18); border-radius: 8px; padding: 3px;
    }
    .size-btn {
        background: transparent; color: rgba(255,255,255,.85); border: none;
        padding: 5px 11px; border-radius: 6px;
        font-size: 11.5px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .size-btn:hover { background: rgba(255,255,255,.15); color: #fff; }
    .size-btn.is-active { background: #fff; color: #1b8a4a; font-weight: 700; }
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

    .label-wrap {
        /* Lock to the exact roll size — the % aspect ratio rounds to
           50.03mm and spills a blank 2nd page; fixed mm prevents that. */
        display: block !important;
        width: var(--label-w);
        height: var(--label-h);
        overflow: hidden;
        page-break-after: always;
        break-after: page;
    }
    .label-wrap-inner {
        width: var(--label-w);
        height: var(--label-h);
        padding-bottom: 0 !important;
    }
    .label-wrap:last-child {
        page-break-after: avoid;
        break-after: avoid;
    }
}
</style>

{{-- Saiz kertas — ditulis semula oleh setLabelSize() --}}
<style id="page-size">@page { size: 80mm 50mm; margin: 0; }</style>
<script>
/* Pulihkan saiz pilihan terakhir sebelum halaman dilukis (elak kelipan) */
(function () {
    try {
        if (localStorage.getItem('rx-label-size') === '100x70') {
            document.documentElement.className = 'size-100x70';
            document.getElementById('page-size').textContent = '@page { size: 100mm 70mm; margin: 0; }';
        }
    } catch (e) {}
})();
</script>
</head>
<body>

@php
$unitMap = [
    'Tablet'     => ['en' => 'Tablet',  'bm' => 'Biji',     'key' => 'tablet'],
    'Kapsul'     => ['en' => 'Capsule', 'bm' => 'Kapsul',   'key' => 'capsule'],
    'Sirup'      => ['en' => 'ML',      'bm' => 'ML',       'key' => 'ml'],
    'MDI'        => ['en' => 'Spray',   'bm' => 'Semburan', 'key' => 'spray'],
    'Titis'      => ['en' => 'Drop',    'bm' => 'Titik',    'key' => 'drop'],
    'Serbuk'     => ['en' => 'Sachet',  'bm' => 'Sachet',   'key' => 'sachet'],
    'Suntikan'   => ['en' => 'ml',      'bm' => 'ml',       'key' => 'ml'],
    'Supositari' => ['en' => 'pc',      'bm' => 'biji',     'key' => 'tablet'],
    'Krim'       => ['en' => 'g',       'bm' => 'g',        'key' => 'sachet'],
    'Gel'        => ['en' => 'g',       'bm' => 'g',        'key' => 'sachet'],
    'Patch'      => ['en' => 'patch',   'bm' => 'patch',    'key' => 'sachet'],
];

$freqMap = [
    'OD'  => 1, 'OD — 1× sehari'   => 1, 'OD - 1x sehari'  => 1,
    'BD'  => 2, 'BD — 2× sehari'   => 2, 'BD - 2x sehari'  => 2,
    'TDS' => 3, 'TDS — 3× sehari'  => 3, 'TDS - 3x sehari' => 3,
    'QID' => 4, 'QID — 4× sehari'  => 4, 'QID - 4x sehari' => 4,
    'ON'  => 1, 'ON — Malam'       => 1, 'ON - malam'      => 1, 'ON - Malam' => 1,
    'PRN' => null, 'PRN — Bila perlu' => null, 'PRN - bila perlu' => null, 'PRN - Bila perlu' => null,
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
        <div class="size-bar" title="Saiz label">
            <button class="size-btn" data-size="80x50"  onclick="setLabelSize('80x50')">80 × 50</button>
            <button class="size-btn" data-size="100x70" onclick="setLabelSize('100x70')">100 × 70</button>
        </div>
        <div class="nav-bar">
            <button class="nav-btn" id="btn-prev" onclick="navigate(-1)" disabled>&#8592;</button>
            <span class="nav-counter" id="nav-counter">1 / {{ $rx->items->count() }}</span>
            <button class="nav-btn" id="btn-next" onclick="navigate(1)" {{ $rx->items->count() <= 1 ? 'disabled' : '' }}>&#8594;</button>
        </div>
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Cetak Semua
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="print-hint">
    ⚙️ <span>Tetapan cetak (Vozy U9):
    <b>Printer → Vozy U9</b> &nbsp;·&nbsp;
    <b>Saiz kertas → <span id="hint-size">80 × 50 mm</span></b> &nbsp;·&nbsp;
    <b>Jidar → Tiada (None)</b> &nbsp;·&nbsp;
    <b>Skala → 100% (Default)</b> &nbsp;·&nbsp;
    Nyahpilih "Header and footers"</span>
</div>

@foreach($rx->items as $idx => $item)
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
    $unitKey = $unit['key'] ?? null;

    /* ── Dose number ── */
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

<div class="label-wrap{{ $idx === 0 ? ' active' : '' }}" data-label-index="{{ $idx }}">
<div class="label">
<div class="label-wrap-inner">

    {{-- Artwork latar — satu bagi setiap saiz roll --}}
    <img class="label-bg for-80x50" src="{{ asset('images/label-medicine.png') }}?v={{ @filemtime(public_path('images/label-medicine.png')) ?: '1' }}" alt="" />
    <img class="label-bg for-100x70" src="{{ asset('images/label-medicine-100x70.png') }}?v={{ @filemtime(public_path('images/label-medicine-100x70.png')) ?: '1' }}" alt="" />

    {{-- ── Tindanan teks · 80 × 50 mm ── --}}
    <div class="label-text for-80x50">

        {{-- Patient name --}}
        <div class="t-nama">{{ $rx->patient->name }}</div>

        {{-- Date --}}
        <div class="t-tarikh">{{ $rx->created_at->format('d/m/Y') }}</div>

        {{-- Kegunaan sahaja (fallback ke nama ubat jika kegunaan kosong) --}}
        <div class="t-ubat">{{ $item->kegunaan ?: $item->drug_name }}</div>

        {{-- Dose number (left box) --}}
        <div class="t-dose-num">{{ $doseNum }}</div>

        {{-- Frequency number (right box) --}}
        <div class="t-freq-num">{{ $freqDisp }}</div>

        {{-- Unit EN row: show selected only --}}
        <div class="t-unit-en">
            @if($unit){{ $unit['en'] }}@endif
        </div>

        {{-- Unit BM row: show selected only --}}
        <div class="t-unit-bm">
            @if($unit){{ $unit['bm'] }}@endif
        </div>

        {{-- Meal timing EN --}}
        @if($mealEn)
        <div class="t-meal-en">{{ $mealEn }}</div>
        @endif

        {{-- Meal timing BM --}}
        @if($mealBm)
        <div class="t-meal-bm">{{ $mealBm }}</div>
        @endif

        {{-- Item note (strip PRN/Habiskan tags — already shown via tick marks) --}}
        @php
            $printNote = $item->item_note;
        @endphp
        @if($printNote)
        <div class="t-note">{{ $printNote }}</div>
        @endif
    </div>{{-- .label-text.for-80x50 --}}

    {{-- ── Tindanan teks · 100 × 70 mm ──
         Artwork ini ada baris "Nama Ubat" dan "Kegunaan" berasingan,
         jadi nama ubat tak perlu lagi berkongsi satu baris dengan kegunaan. --}}
    <div class="label-text lt7 for-100x70">

        {{-- Tampung teks contoh artwork sebelum apa-apa teks dilukis --}}
        <div class="lt7-patch lt7-patch--meal"></div>
        <div class="lt7-patch lt7-patch--note"></div>

        <div class="lt7-nama">{{ $rx->patient->name }}</div>
        <div class="lt7-tarikh">{{ $rx->created_at->format('d/m/Y') }}</div>
        <div class="lt7-ubat">{{ $item->drug_name }}</div>
        <div class="lt7-kegunaan">{{ $item->kegunaan }}</div>

        <div class="lt7-dose-num">{{ $doseNum }}</div>
        <div class="lt7-freq-num">{{ $freqDisp }}</div>

        <div class="lt7-unit-en">@if($unit){{ $unit['en'] }}@endif</div>
        <div class="lt7-unit-bm">@if($unit){{ $unit['bm'] }}@endif</div>

        {{-- Satu baris "EN / BM", padan gaya teks tercetak artwork --}}
        @if($mealEn || $mealBm)
        <div class="lt7-meal">{{ $mealEn === $mealBm ? $mealEn : trim("{$mealEn} / {$mealBm}", ' /') }}</div>
        @endif

        @if($printNote)
        <div class="lt7-note">{{ $printNote }}</div>
        @endif
    </div>{{-- .label-text.for-100x70 --}}

</div>{{-- .label-wrap-inner --}}
</div>{{-- .label --}}
</div>{{-- .label-wrap --}}
@endforeach

<script>
(function () {
    var total   = {{ $rx->items->count() }};
    var current = 0;

    var wraps   = document.querySelectorAll('.label-wrap');
    var counter = document.getElementById('nav-counter');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');

    function show(idx) {
        wraps.forEach(function (w, i) {
            w.classList.toggle('active', i === idx);
        });
        current = idx;
        counter.textContent = (idx + 1) + ' / ' + total;
        btnPrev.disabled = idx === 0;
        btnNext.disabled = idx === total - 1;
    }

    window.navigate = function (dir) {
        var next = current + dir;
        if (next >= 0 && next < total) show(next);
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') navigate(1);
        if (e.key === 'ArrowLeft')  navigate(-1);
    });

    /* ── Saiz label ── */
    var SIZES = {
        '80x50':  { page: '80mm 50mm',  hint: '80 × 50 mm' },
        '100x70': { page: '100mm 70mm', hint: '100 × 70 mm' }
    };

    window.setLabelSize = function (key) {
        var size = SIZES[key] || SIZES['80x50'];
        document.documentElement.className = key === '100x70' ? 'size-100x70' : '';
        document.getElementById('page-size').textContent =
            '@page { size: ' + size.page + '; margin: 0; }';
        document.getElementById('hint-size').textContent = size.hint;
        document.querySelectorAll('.size-btn').forEach(function (b) {
            b.classList.toggle('is-active', b.dataset.size === key);
        });
        try { localStorage.setItem('rx-label-size', key); } catch (e) {}
    };

    /* Segerakkan butang dengan saiz yang dipulihkan dalam <head> */
    setLabelSize(document.documentElement.classList.contains('size-100x70') ? '100x70' : '80x50');
})();
</script>
</body>
</html>
