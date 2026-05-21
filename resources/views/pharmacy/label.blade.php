<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $rx->rx_number }} · Label Ubat · Poliklinik Al-Huda</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 10px;
    color: #000;
    background: #fff;
}

/* ── Label grid — 2 cols × N rows on A4 ── */
/* Each label: 90mm × 45mm, gap 5mm, margin 10mm */
.label-sheet {
    width: 210mm;
    margin: 0 auto;
    padding: 8mm 10mm;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5mm;
    align-content: start;
}

/* ── Single label ── */
.label {
    width: 90mm;
    height: 45mm;
    border: 1px solid #000;
    border-radius: 3px;
    padding: 3mm 3.5mm;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    page-break-inside: avoid;
}

/* Clinic strip */
.lbl-clinic {
    display: flex;
    align-items: center;
    gap: 3mm;
    padding-bottom: 2mm;
    border-bottom: 1px solid #000;
    margin-bottom: 2mm;
    flex-shrink: 0;
}
.lbl-clinic__logo {
    width: 10mm; height: 10mm;
    object-fit: contain;
    border-radius: 2px;
}
.lbl-clinic__name  { font: 700 8.5px 'Segoe UI', Arial, sans-serif; color: #000; line-height: 1.2; }
.lbl-clinic__addr  { font: 400 7px 'Segoe UI', Arial, sans-serif; color: #555; line-height: 1.4; margin-top: 1px; }

/* Patient row */
.lbl-patient {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 1.5mm;
    flex-shrink: 0;
}
.lbl-patient__name { font: 700 9px 'Segoe UI', Arial, sans-serif; color: #000; }
.lbl-patient__ic   { font: 400 7.5px 'Segoe UI', Arial, sans-serif; color: #555; }

/* Drug name */
.lbl-drug {
    font: 800 10.5px 'Segoe UI', Arial, sans-serif;
    color: #000;
    margin-bottom: 1.5mm;
    flex-shrink: 0;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Dosage grid */
.lbl-dose {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5mm;
    margin-bottom: 1.5mm;
    flex-shrink: 0;
}
.dose-cell__lbl { font: 500 6.5px 'Segoe UI', Arial, sans-serif; color: #777; text-transform: uppercase; letter-spacing: .03em; }
.dose-cell__val { font: 700 8.5px 'Segoe UI', Arial, sans-serif; color: #000; line-height: 1.2; }
.dose-cell__qty { font: 800 11px 'Segoe UI', Arial, sans-serif; color: #1b8a4a; }

/* Instructions */
.lbl-instr {
    font: 600 8px 'Segoe UI', Arial, sans-serif;
    color: #1d4ed8;
    background: #eff6ff;
    border-radius: 2px;
    padding: 1mm 2mm;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Footer */
.lbl-footer {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding-top: 1.5mm;
    border-top: 0.5px solid #ccc;
    flex-shrink: 0;
}
.lbl-footer__rx   { font: 500 7px 'Courier New', monospace; color: #555; }
.lbl-footer__date { font: 400 7px 'Segoe UI', Arial, sans-serif; color: #555; }

/* Allergy warning — shown only if patient has allergies */
.lbl-allergy {
    background: #fff3e0;
    border: 1px solid #f97316;
    border-radius: 2px;
    padding: 1mm 2mm;
    font: 700 7.5px 'Segoe UI', Arial, sans-serif;
    color: #9a3412;
    margin-bottom: 1.5mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 0;
}

/* ── Screen preview bar ── */
@media screen {
    body { background: #d1d5db; padding: 20px 0 40px; }
    .label-sheet { background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,.15); }

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

    /* dashed cut guide on screen */
    .label { border-style: dashed; }
}

@media print {
    .print-bar { display: none !important; }
    body { background: #fff; }
    .label-sheet { box-shadow: none; padding: 8mm 10mm; width: 210mm; }
    .label { border: 1px solid #000; border-style: solid; }
    @page { margin: 0; size: A4 portrait; }
}
</style>
</head>
<body>

<div class="print-bar">
    <span class="print-bar__title">
        Label Ubat · {{ $rx->rx_number }} · {{ $rx->patient->name }}
        &nbsp;({{ $rx->items->count() }} label)
    </span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Label
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="label-sheet">

    @foreach($rx->items as $item)
    <div class="label">

        {{-- Clinic strip --}}
        <div class="lbl-clinic">
            <img src="{{ url('/logo.png') }}" alt="" class="lbl-clinic__logo" />
            <div>
                <div class="lbl-clinic__name">Poliklinik Al-Huda</div>
                <div class="lbl-clinic__addr">No.1, Jalan Al-Huda, 47500 Subang Jaya · 03-8888 0000</div>
            </div>
        </div>

        {{-- Patient --}}
        <div class="lbl-patient">
            <span class="lbl-patient__name">{{ $rx->patient->name }}</span>
            <span class="lbl-patient__ic">{{ $rx->patient->ic_number }}</span>
        </div>

        {{-- Allergy warning --}}
        @if($rx->patient->allergies)
        <div class="lbl-allergy">⚠ ALAHAN: {{ $rx->patient->allergies }}</div>
        @endif

        {{-- Drug name --}}
        <div class="lbl-drug">{{ $item->drug_name }}</div>

        {{-- Dose grid --}}
        <div class="lbl-dose">
            @if($item->dosage)
            <div>
                <div class="dose-cell__lbl">Dos</div>
                <div class="dose-cell__val">{{ $item->dosage }}</div>
            </div>
            @endif
            @if($item->frequency)
            <div>
                <div class="dose-cell__lbl">Kekerapan</div>
                <div class="dose-cell__val">{{ $item->frequency }}</div>
            </div>
            @endif
            @if($item->duration)
            <div>
                <div class="dose-cell__lbl">Tempoh</div>
                <div class="dose-cell__val">{{ $item->duration }}</div>
            </div>
            @endif
            <div>
                <div class="dose-cell__lbl">Kuantiti</div>
                <div class="dose-cell__qty">{{ $item->quantity }}</div>
            </div>
        </div>

        {{-- Instructions --}}
        @if($item->instructions)
        <div class="lbl-instr">⚕ {{ $item->instructions }}</div>
        @endif

        {{-- Footer --}}
        <div class="lbl-footer">
            <span class="lbl-footer__rx">{{ $rx->rx_number }} · Dr. {{ $rx->prescribing_doctor }}</span>
            <span class="lbl-footer__date">{{ $rx->created_at->format('d/m/Y') }}</span>
        </div>

    </div>
    @endforeach

</div>
</body>
</html>
