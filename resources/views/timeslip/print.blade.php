<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $timeslip->slip_number }} · Slip Masa · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12px;
    color: #000;
    background: #fff;
}

@php
    [$ah, $am] = explode(':', $timeslip->arrival_time);
    [$dh, $dm] = explode(':', $timeslip->departure_time);
    $totalMin  = ($dh * 60 + $dm) - ($ah * 60 + $am);
    $hours     = intdiv($totalMin, 60);
    $mins      = $totalMin % 60;
    $duration  = $hours > 0 ? "{$hours} jam {$mins} minit" : "{$mins} minit";
@endphp

/* A5 portrait — same compact size as MC */
.page {
    width: 148mm;
    height: 210mm;
    margin: 0 auto;
    padding: 0 12mm 8mm;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

/* ── Watermark ── */
.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font: 700 52px 'Times New Roman', Times, serif;
    color: rgba(27,138,74,.07);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    letter-spacing: 4px;
}

.content { position: relative; z-index: 1; display: flex; flex-direction: column; flex: 1; }

/* ── Letterhead header ── */
.lh-wrap {
    margin: 0 -12mm 8px;
    overflow: hidden;
    height: 23mm;
    flex-shrink: 0;
}
.lh-wrap img {
    width: 100%;
    display: block;
}

/* ── Document title ── */
.doc-title {
    text-align: center;
    margin-bottom: 5px;
}
.doc-title h1 {
    font: 700 16px 'Times New Roman', Times, serif;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-bottom: 1.5px solid #000;
    display: inline-block;
    padding-bottom: 3px;
}
.doc-title .bilingual {
    font: 400 9px 'Times New Roman', Times, serif;
    color: #555;
    margin-top: 2px;
}

/* ── Slip number badge ── */
.slip-badge {
    text-align: center;
    margin-bottom: 6px;
}
.slip-badge span {
    display: inline-block;
    padding: 3px 14px;
    border: 1.5px solid #1b8a4a;
    border-radius: 3px;
    font: 700 11px 'Courier New', monospace;
    color: #1b8a4a;
    letter-spacing: 1px;
}

/* ── Patient details table ── */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 6px;
    font: 400 11px 'Times New Roman', Times, serif;
}
.detail-table td {
    padding: 3px 6px;
    vertical-align: top;
}
.detail-table td:first-child {
    width: 42mm;
    color: #444;
    white-space: nowrap;
}
.detail-table td:nth-child(2) { width: 4mm; text-align: center; }
.detail-table td:last-child { font-weight: 600; }
.detail-table tr:nth-child(odd) td { background: #f9f9f9; }

/* ── Time display ── */
.time-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
    margin: 6px 0 8px;
}
.time-box {
    border: 1.5px solid #000;
    border-radius: 4px;
    padding: 5px 4px;
    text-align: center;
}
.time-box__lbl {
    font: 400 8px 'Times New Roman', Times, serif;
    color: #777;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 4px;
}
.time-box__val {
    font: 700 18px 'Courier New', monospace;
    color: #1b8a4a;
    line-height: 1;
}
.time-box__sub {
    font: 400 8.5px 'Times New Roman', Times, serif;
    color: #555;
    margin-top: 3px;
}
.time-box--duration .time-box__val {
    font-size: 18px;
    color: #000;
}

/* ── Body text ── */
.ts-body {
    font: 400 11.5px 'Times New Roman', Times, serif;
    color: #000;
    line-height: 1.6;
    margin-bottom: 6px;
}
.underline {
    display: inline-block;
    border-bottom: 1px solid #000;
    min-width: 30mm;
    text-align: center;
    font-weight: 700;
    padding: 0 4px;
}

/* ── Purpose box ── */
.purpose-box {
    border-left: 3px solid #1b8a4a;
    padding: 3px 8px;
    margin: 0 0 5px;
    font: 400 11px 'Times New Roman', Times, serif;
    color: #000;
}
.purpose-box__lbl { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
.purpose-box__val { font-weight: 600; }

/* ── Notes ── */
.notes-box {
    border: 1px dashed #ccc;
    border-radius: 3px;
    padding: 4px 8px;
    margin: 0 0 5px;
    font: 400 10px 'Times New Roman', Times, serif;
    color: #555;
}
.notes-box__lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: .06em; color: #999; margin-bottom: 2px; }

/* ── Sig area ── */
.sig-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
    padding-top: 5px;
    border-top: 1px solid #ccc;
}
.sig-block { display: flex; flex-direction: column; align-items: center; }
.sig-block__line  { width: 100%; border-bottom: 1px solid #000; height: 22px; margin-bottom: 3px; }
.sig-block__label { font: 400 8.5px 'Times New Roman', Times, serif; color: #555; text-align: center; line-height: 1.5; }
.sig-block__name  { font: 700 9px 'Times New Roman', Times, serif; color: #000; margin-top: 2px; }

.chop-area {
    border: 1px dashed #bbb;
    border-radius: 4px;
    width: 35mm;
    height: 18mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font: 400 8px 'Times New Roman', Times, serif;
    color: #bbb;
    text-align: center;
    margin: 4px auto 0;
    padding: 4px;
}

/* ── QR code ── */
.qr-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}
.qr-wrap svg { width: 22mm; height: 22mm; }
.qr-wrap__lbl {
    font: 400 7px 'Times New Roman', Times, serif;
    color: #888;
    text-align: center;
    line-height: 1.4;
}

/* ── Footer ── */
.footer {
    padding-top: 5px;
    border-top: 1px solid #e5e7eb;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
    font: 400 7.5px 'Times New Roman', Times, serif;
    color: #aaa;
}

/* ── Screen bar ── */
@media screen {
    body { background: #d1d5db; padding: 50px 0 40px; }
    .page { box-shadow: 0 4px 20px rgba(0,0,0,.15); background: #fff; }
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
}

@media print {
    .print-bar { display: none !important; }
    body { background: #fff; }
    .page { box-shadow: none; padding: 0 10mm 8mm; width: 100%; height: 210mm; overflow: hidden; }
    .lh-wrap { margin: 0 -10mm 8px; height: 23mm; }
    @page { margin: 0; size: A5 portrait; }
}
</style>
</head>
<body>

@php
    [$ah2, $am2] = explode(':', $timeslip->arrival_time);
    [$dh2, $dm2] = explode(':', $timeslip->departure_time);
    $totalMin2   = ($dh2 * 60 + $dm2) - ($ah2 * 60 + $am2);
    $hrs         = intdiv($totalMin2, 60);
    $mns         = $totalMin2 % 60;
    $duration    = $hrs > 0 ? "{$hrs} jam {$mns} min" : "{$mns} minit";
    $arrivalFmt  = \Carbon\Carbon::createFromFormat('H:i', $timeslip->arrival_time)->format('h:i A');
    $departureFmt = \Carbon\Carbon::createFromFormat('H:i', $timeslip->departure_time)->format('h:i A');
@endphp

<div class="print-bar">
    <span class="print-bar__title">{{ $timeslip->slip_number }} · {{ $timeslip->patient->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Slip
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="page">
    <div class="watermark">AL-HUDA</div>
    <div class="content">

        {{-- Letterhead --}}
        <div class="lh-wrap">
            <img src="{{ asset('images/letterhead.png') }}" alt="{{ $clinic->name }}" />
        </div>

        {{-- Title --}}
        <div class="doc-title">
            <h1>Slip Masa</h1>
            <div class="bilingual">Time Attendance Slip / Clinic Attendance Record</div>
        </div>

        {{-- Slip number --}}
        <div class="slip-badge">
            <span>{{ $timeslip->slip_number }}</span>
        </div>

        {{-- Patient details --}}
        <table class="detail-table">
            <tr>
                <td>Nama Pesakit</td><td>:</td>
                <td>{{ $timeslip->patient->name }}</td>
            </tr>
            <tr>
                <td>No. Kad Pengenalan</td><td>:</td>
                <td>{{ $timeslip->patient->ic_number }}</td>
            </tr>
            <tr>
                <td>No. ID Pesakit</td><td>:</td>
                <td>{{ $timeslip->patient->patient_id }}</td>
            </tr>
            <tr>
                <td>Tarikh</td><td>:</td>
                <td>{{ $timeslip->slip_date->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        {{-- Time grid --}}
        <div class="time-grid">
            <div class="time-box">
                <div class="time-box__lbl">Masa Tiba</div>
                <div class="time-box__val">{{ substr($timeslip->arrival_time, 0, 5) }}</div>
                <div class="time-box__sub">{{ $arrivalFmt }}</div>
            </div>
            <div class="time-box">
                <div class="time-box__lbl">Masa Keluar</div>
                <div class="time-box__val">{{ substr($timeslip->departure_time, 0, 5) }}</div>
                <div class="time-box__sub">{{ $departureFmt }}</div>
            </div>
            <div class="time-box time-box--duration">
                <div class="time-box__lbl">Jumlah Masa</div>
                <div class="time-box__val">{{ $duration }}</div>
                <div class="time-box__sub">tempoh di klinik</div>
            </div>
        </div>

        {{-- Body text --}}
        <div class="ts-body">
            <p>
                Ini untuk mengesahkan bahawa pesakit di atas telah hadir ke klinik ini
                pada <span class="underline">{{ $timeslip->slip_date->translatedFormat('d F Y') }}</span>,
                dari jam <span class="underline">{{ substr($timeslip->arrival_time, 0, 5) }}</span>
                hingga <span class="underline">{{ substr($timeslip->departure_time, 0, 5) }}</span>
                ({{ $duration }}).
            </p>
        </div>

        {{-- Purpose --}}
        @if($timeslip->purpose)
        <div class="purpose-box">
            <div class="purpose-box__lbl">Tujuan Kunjungan / Purpose of Visit</div>
            <div class="purpose-box__val">{{ $timeslip->purpose }}</div>
        </div>
        @endif

        {{-- Notes --}}
        @if($timeslip->notes)
        <div class="notes-box">
            <div class="notes-box__lbl">Nota</div>
            <div>{{ $timeslip->notes }}</div>
        </div>
        @endif

        {{-- Signature --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Tandatangan Doktor / Kakitangan<br>
                    <span class="sig-block__name">{{ $timeslip->issued_by }}</span>
                </div>
                <div style="margin-top:6px">
                    <div class="chop-area">Cop Rasmi Klinik</div>
                </div>
            </div>
            <div class="sig-block" style="justify-content:space-between">
                <div style="width:100%">
                    <table style="font:400 9px 'Times New Roman',serif;width:100%;border-collapse:collapse">
                        <tr><td style="color:#777;padding:2px 0">Tarikh Dikeluarkan</td></tr>
                        <tr><td style="font-weight:700;font-size:10px;padding:2px 0">{{ $timeslip->created_at->format('d/m/Y H:i') }}</td></tr>
                        <tr><td style="color:#777;padding:4px 0 2px 0">Dikeluarkan Oleh</td></tr>
                        <tr><td style="font-weight:700;font-size:10px">{{ $timeslip->issued_by }}</td></tr>
                    </table>
                </div>
                <div class="qr-wrap">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(84)->margin(0)->generate(
                        route('timeslip.verify', $timeslip->verify_token)
                    ) !!}
                    <div class="qr-wrap__lbl">Imbas untuk<br>pengesahan</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $timeslip->slip_number }}</span>
            <span>Dokumen rasmi — Sah dengan cop klinik · Dilarang meniru</span>
        </div>

    </div>
</div>
</body>
</html>