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

@page { margin: 0; }

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
    padding-bottom: 62.54%; /* maintains 945:591 ratio */
}

.label-bg {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    display: block;
    object-fit: fill;
}

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
    font-size: 29px;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1;
}
/* Tarikh */
.t-tarikh {
    position: absolute;
    top: 39.6%;
    left: 12%;
    right: 2%;
    font-size: 29px;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1;
}
/* Nama Ubat */
.t-ubat {
    position: absolute;
    top: 47.5%;
    left: 33.5%;
    right: 2%;
    font-size: 29px;
    font-weight: 600;
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
    font-size: 33px;
    font-weight: 900;
    line-height: 1;
}
.t-freq-num {
    position: absolute;
    top: 62.5%;
    left: 47%;
    width: 7.5%;
    text-align: center;
    font-size: 33px;
    font-weight: 900;
    line-height: 1;
}

/* ── Unit highlight (EN row) ── */
.t-unit-en {
    position: absolute;
    top: 58%;
    left: 17.5%;
    font-size: 23px;
    line-height: 1;
    display: flex;
    gap: 1px;
    align-items: center;
}
/* ── Unit highlight (BM row) ── */
.t-unit-bm {
    position: absolute;
    top: 67%;
    left: 17.5%;
    font-size: 23px;
    line-height: 1;
    display: flex;
    gap: 1px;
    align-items: center;
}
.unit-part        { color: #aaa; }
.unit-part.active { color: #000; font-weight: 700; }

/* ── Meal timing ── */
.t-meal-en {
    position: absolute;
    top: 58%;
    left: 73%;
    right: 2%;
    font-size: 26px;
    line-height: 1;
    white-space: nowrap;
}
.t-meal-bm {
    position: absolute;
    top: 67%;
    left: 73%;
    right: 2%;
    font-size: 26px;
    line-height: 1;
    white-space: nowrap;
}

/* ── Checkboxes ticks ── */
.t-prn-tick {
    position: absolute;
    top: 81%;
    left: 3.8%;
    font-size: 40px;
    font-weight: 900;
    line-height: 1;
}
.t-complete-tick {
    position: absolute;
    top: 81%;
    left: 45.8%;
    font-size: 40px;
    font-weight: 900;
    line-height: 1;
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
        display: block !important;
        page-break-after: always;
        break-after: page;
    }
    .label-wrap:last-child {
        page-break-after: avoid;
        break-after: avoid;
    }
}
</style>
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
    'OD'  => 1, 'OD — 1× sehari'   => 1,
    'BD'  => 2, 'BD — 2× sehari'   => 2,
    'TDS' => 3, 'TDS — 3× sehari'  => 3,
    'QID' => 4, 'QID — 4× sehari'  => 4,
    'ON'  => 1, 'ON — Malam'       => 1,
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
    ⚙️ <span>Tetapan cetak:
    <b>Saiz kertas → 70 × 40 mm</b> &nbsp;·&nbsp;
    <b>Jidar → Tiada (None)</b> &nbsp;·&nbsp;
    <b>Skala → 100%</b> &nbsp;·&nbsp;
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

    {{-- Background image (the full label design) --}}
    <img class="label-bg" src="{{ asset('images/label-medicine.png') }}" alt="" />

    {{-- Dynamic text overlaid on top --}}
    <div class="label-text">

        {{-- Patient name --}}
        <div class="t-nama">{{ $rx->patient->name }}</div>

        {{-- Date --}}
        <div class="t-tarikh">{{ $rx->created_at->format('d/m/Y') }}</div>

        {{-- Drug name / Kegunaan --}}
        <div class="t-ubat">{{ $item->drug_name }}@if($item->kegunaan) / {{ $item->kegunaan }}@endif</div>

        {{-- Dose number (left box) --}}
        <div class="t-dose-num">{{ $doseNum }}</div>

        {{-- Frequency number (right box) --}}
        <div class="t-freq-num">{{ $freqDisp }}</div>

        {{-- Unit EN row: Tablet / ML / Drop / Spray --}}
        <div class="t-unit-en">
            <span class="unit-part {{ $unitKey === 'tablet'  ? 'active' : '' }}">Tablet</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'ml'      ? 'active' : '' }}">ML</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'drop'    ? 'active' : '' }}">Drop</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'spray'   ? 'active' : '' }}">Spray</span>
        </div>

        {{-- Unit BM row: Biji / ML / Titik / Semburan --}}
        <div class="t-unit-bm">
            <span class="unit-part {{ $unitKey === 'tablet'  ? 'active' : '' }}">Biji</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'ml'      ? 'active' : '' }}">ML</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'drop'    ? 'active' : '' }}">Titik</span>&nbsp;/&nbsp;
            <span class="unit-part {{ $unitKey === 'spray'   ? 'active' : '' }}">Semburan</span>
        </div>

        {{-- Meal timing EN --}}
        @if($mealEn)
        <div class="t-meal-en">{{ $mealEn }}</div>
        @endif

        {{-- Meal timing BM --}}
        @if($mealBm)
        <div class="t-meal-bm">{{ $mealBm }}</div>
        @endif

        {{-- Bila Perlu tick --}}
        @if($isPrn)
        <div class="t-prn-tick">✓</div>
        @endif

        {{-- Habiskan Ubat tick --}}
        @if($completeCourse)
        <div class="t-complete-tick">✓</div>
        @endif

    </div>{{-- .label-text --}}

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
})();
</script>
</body>
</html>
