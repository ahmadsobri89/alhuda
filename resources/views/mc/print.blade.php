<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $mc->mc_number }} · Sijil Cuti Sakit · Poliklinik Al-Huda</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12px;
    color: #000;
    background: #fff;
}

/* A5 portrait — same as prescription */
.page {
    width: 148mm;
    min-height: 210mm;
    margin: 0 auto;
    padding: 10mm 12mm 10mm;
    display: flex;
    flex-direction: column;
    position: relative;
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

/* ── Header ── */
.hd {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 6px;
    border-bottom: 3px double #1b8a4a;
    margin-bottom: 8px;
}
.hd-brand { display: flex; align-items: center; gap: 8px; }
.hd-logo  { width: 34px; height: 34px; border-radius: 5px; object-fit: contain; }
.hd-name  { font: 700 15px 'Times New Roman', Times, serif; color: #1b8a4a; }
.hd-sub   { font: 400 9px 'Times New Roman', Times, serif; color: #555; margin-top: 1px; }
.hd-right { text-align: right; font: 400 8.5px 'Times New Roman', Times, serif; color: #555; line-height: 1.6; }
.hd-right strong { font-size: 9px; color: #222; }

/* ── Document title ── */
.doc-title {
    text-align: center;
    margin-bottom: 8px;
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

/* ── MC number badge ── */
.mc-badge {
    text-align: center;
    margin-bottom: 10px;
}
.mc-badge span {
    display: inline-block;
    padding: 3px 14px;
    border: 1.5px solid #1b8a4a;
    border-radius: 3px;
    font: 700 11px 'Courier New', monospace;
    color: #1b8a4a;
    letter-spacing: 1px;
}

/* ── Body text ── */
.mc-body {
    flex: 1;
    font: 400 12px 'Times New Roman', Times, serif;
    color: #000;
    line-height: 2;
}
.mc-body p { margin-bottom: 6px; }

.underline {
    display: inline-block;
    border-bottom: 1px solid #000;
    min-width: 40mm;
    text-align: center;
    font-weight: 700;
    padding: 0 4px;
}
.underline-lg { min-width: 80mm; }
.underline-sm { min-width: 20mm; }

/* ── Details table ── */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0 10px;
    font: 400 11px 'Times New Roman', Times, serif;
}
.detail-table td {
    padding: 4px 6px;
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

/* ── Days highlight ── */
.days-box {
    border: 2px solid #000;
    border-radius: 4px;
    padding: 8px 12px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.days-box__num {
    font: 900 36px 'Times New Roman', Times, serif;
    color: #1b8a4a;
    line-height: 1;
    flex-shrink: 0;
}
.days-box__text {
    font: 400 11px 'Times New Roman', Times, serif;
    line-height: 1.5;
    color: #000;
}
.days-box__text strong { font-size: 12px; }

/* ── Diagnosis ── */
.dx-box {
    border-left: 3px solid #1b8a4a;
    padding: 4px 8px;
    margin: 8px 0;
    font: 400 11px 'Times New Roman', Times, serif;
    color: #000;
}
.dx-box__lbl { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
.dx-box__val { font-weight: 600; }

/* ── Notes ── */
.notes-box {
    border: 1px dashed #ccc;
    border-radius: 3px;
    padding: 5px 8px;
    margin: 6px 0;
    font: 400 10px 'Times New Roman', Times, serif;
    color: #555;
}
.notes-box__lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: .06em; color: #999; margin-bottom: 2px; }

/* ── Sig area ── */
.sig-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: auto;
    padding-top: 8px;
    border-top: 1px solid #ccc;
}
.sig-block { display: flex; flex-direction: column; align-items: center; }
.sig-block__line  { width: 100%; border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px; }
.sig-block__label { font: 400 8.5px 'Times New Roman', Times, serif; color: #555; text-align: center; line-height: 1.5; }
.sig-block__name  { font: 700 9px 'Times New Roman', Times, serif; color: #000; margin-top: 2px; }

/* ── Chop area ── */
.chop-area {
    border: 1px dashed #bbb;
    border-radius: 4px;
    width: 35mm;
    height: 25mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font: 400 8px 'Times New Roman', Times, serif;
    color: #bbb;
    text-align: center;
    margin: 0 auto;
    padding: 4px;
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
    body { background: #d1d5db; padding: 24px 0 40px; }
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
    body { padding-top: 50px; }
}

@media print {
    .print-bar { display: none !important; }
    body { background: #fff; }
    .page { box-shadow: none; padding: 8mm 10mm; width: 100%; min-height: unset; }
    @page { margin: 0; size: A5 portrait; }
}
</style>
</head>
<body>

<div class="print-bar">
    <span class="print-bar__title">{{ $mc->mc_number }} · {{ $mc->patient->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak MC
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="page">
    <div class="watermark">AL-HUDA</div>
    <div class="content">

        {{-- Header --}}
        <div class="hd">
            <div class="hd-brand">
                <img src="{{ url('/logo.png') }}" alt="" class="hd-logo" />
                <div>
                    <div class="hd-name">Poliklinik Al-Huda</div>
                    <div class="hd-sub">Klinik Perubatan Berdaftar</div>
                </div>
            </div>
            <div class="hd-right">
                <strong>{{ config('app.name') }}</strong>
                No. 1, Jalan Al-Huda, Taman Harmoni<br>
                47500 Subang Jaya, Selangor<br>
                Tel: 03-8888 0000
            </div>
        </div>

        {{-- Title --}}
        <div class="doc-title">
            <h1>Sijil Cuti Sakit</h1>
            <div class="bilingual">Medical Certificate / Sick Leave Certificate</div>
        </div>

        {{-- MC number --}}
        <div class="mc-badge">
            <span>{{ $mc->mc_number }}</span>
        </div>

        {{-- Patient details table --}}
        <table class="detail-table">
            <tr>
                <td>Nama Pesakit</td><td>:</td>
                <td>{{ $mc->patient->name }}</td>
            </tr>
            <tr>
                <td>No. Kad Pengenalan</td><td>:</td>
                <td>{{ $mc->patient->ic_number }}</td>
            </tr>
            <tr>
                <td>No. ID Pesakit</td><td>:</td>
                <td>{{ $mc->patient->patient_id }}</td>
            </tr>
            <tr>
                <td>Tarikh Periksa</td><td>:</td>
                <td>{{ $mc->issue_date->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        {{-- Days highlight --}}
        <div class="days-box">
            <div class="days-box__num">{{ $mc->days }}</div>
            <div class="days-box__text">
                <strong>hari / day(s)</strong><br>
                Bermula: {{ $mc->start_date->format('d/m/Y') }}
                &nbsp;→&nbsp;
                Tamat: {{ $mc->end_date->format('d/m/Y') }}
            </div>
        </div>

        {{-- Body text --}}
        <div class="mc-body">
            <p>
                Ini untuk mengesahkan bahawa pesakit di atas telah diperiksa oleh saya
                pada <span class="underline">{{ $mc->issue_date->translatedFormat('d F Y') }}</span>
                dan adalah <strong>TIDAK LAYAK UNTUK BERTUGAS / HADIR KE SEKOLAH</strong>
                selama <span class="underline underline-sm">{{ $mc->days }}</span> hari,
                iaitu dari <span class="underline">{{ $mc->start_date->translatedFormat('d F Y') }}</span>
                hingga <span class="underline">{{ $mc->end_date->translatedFormat('d F Y') }}</span>.
            </p>
        </div>

        {{-- Diagnosis --}}
        @if($mc->diagnosis)
        <div class="dx-box">
            <div class="dx-box__lbl">Diagnosis / Sebab Cuti</div>
            <div class="dx-box__val">{{ $mc->diagnosis }}</div>
        </div>
        @endif

        {{-- Notes --}}
        @if($mc->notes)
        <div class="notes-box">
            <div class="notes-box__lbl">Nota</div>
            <div>{{ $mc->notes }}</div>
        </div>
        @endif

        {{-- Signature + Chop --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Tandatangan Doktor<br>
                    <span class="sig-block__name">{{ $mc->issued_by }}</span>
                </div>
                <div style="margin-top:8px">
                    <div class="chop-area">Cop Rasmi Klinik</div>
                </div>
            </div>
            <div class="sig-block">
                <div style="margin-bottom:8px">
                    <table style="font:400 9px 'Times New Roman',serif;width:100%;border-collapse:collapse">
                        <tr><td style="color:#777;padding:2px 0">Tarikh Dikeluarkan</td></tr>
                        <tr><td style="font-weight:700;font-size:10px;padding:2px 0">{{ $mc->created_at->format('d/m/Y H:i') }}</td></tr>
                        <tr><td style="color:#777;padding:6px 0 2px 0">Dikeluarkan Oleh</td></tr>
                        <tr><td style="font-weight:700;font-size:10px">{{ $mc->issued_by }}</td></tr>
                    </table>
                </div>
                <div style="flex:1"></div>
                <div class="sig-block__label" style="width:100%;text-align:left;border-top:1px solid #000;padding-top:4px;margin-top:auto">
                    Tandatangan / Penerimaan Pesakit
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $mc->mc_number }}</span>
            <span>Dokumen rasmi — Sah dengan cop klinik · Dilarang meniru</span>
        </div>

    </div>
</div>
</body>
</html>
