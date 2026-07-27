<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Templat Memo Kosong · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12px;
    color: #000;
    background: #fff;
}

/* A5 portrait */
.page {
    width: 148mm;
    min-height: 210mm;
    margin: 0 auto;
    padding: 11mm 13mm 10mm;
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
    font: 700 50px 'Times New Roman', Times, serif;
    color: rgba(27,138,74,.05);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    letter-spacing: 4px;
}

.content { position: relative; z-index: 1; display: flex; flex-direction: column; flex: 1; }

/* ── Letterhead header ── */
.lh-wrap {
    margin: -11mm -13mm 8px;
    overflow: hidden;
    height: 31mm;
    flex-shrink: 0;
}
.lh-wrap img { width: 100%; display: block; }

/* ── Document title ── */
.doc-title {
    text-align: center;
    margin-bottom: 8px;
}
.doc-title h1 {
    font: 700 17px 'Times New Roman', Times, serif;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 2.5px;
    border-bottom: 1.5px solid #000;
    display: inline-block;
    padding-bottom: 4px;
}
.doc-title .bilingual {
    font: 400 9.5px 'Times New Roman', Times, serif;
    color: #555;
    margin-top: 3px;
}

/* ── Patient reference ── */
.patient-ref {
    font: 400 10px 'Times New Roman', Times, serif;
    color: #555;
    border: 1px dashed #ccc;
    border-radius: 4px;
    padding: 6px 10px;
    margin-bottom: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px 18px;
}
.patient-ref b { color: #000; font-weight: 700; }
.patient-ref .blank-line {
    display: inline-block; width: 32mm;
    border-bottom: 1px solid #000; height: 13px; vertical-align: bottom;
}

/* ── Closing paragraph ── */
.closing {
    font: 400 11.5px 'Times New Roman', Times, serif;
    color: #000;
    line-height: 1.7;
    margin-bottom: 10px;
}

/* ── Ruled writing area ── */
.section__title {
    font: 700 9px 'Times New Roman', Times, serif;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #777;
    margin-bottom: 5px;
}
.write-area {
    flex: 1;
    min-height: 40mm;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 10px;
    background-image: repeating-linear-gradient(to bottom, transparent, transparent 20px, #e0e0e0 21px);
}

/* ── Sig area ── */
.sig-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding-top: 10px;
    border-top: 1px solid #ccc;
}
.sig-block { display: flex; flex-direction: column; }
.sig-block__line  { border-bottom: 1px solid #000; height: 26px; margin-bottom: 5px; }
.sig-block__label { font: 400 8.5px 'Times New Roman', Times, serif; color: #555; line-height: 1.5; }

/* ── Chop area ── */
.chop-area {
    border: 1px dashed #bbb;
    border-radius: 4px;
    width: 28mm;
    height: 20mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font: 400 7px 'Times New Roman', Times, serif;
    color: #bbb;
    text-align: center;
    margin-top: 6px;
}

/* ── Footer ── */
.footer {
    padding-top: 6px;
    border-top: 1px solid #e5e7eb;
    margin-top: 8px;
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
    .page { box-shadow: none; padding: 9mm 11mm; width: 100%; min-height: unset; }
    .lh-wrap { margin: -9mm -11mm 8px; height: 31mm; }
    @page { margin: 0; size: A5 portrait; }
}
</style>
</head>
<body>

<div class="print-bar">
    <span class="print-bar__title">Templat Memo Kosong</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Templat
        </button>
        <button class="print-bar__close" onclick="window.close()">✕</button>
    </div>
</div>

<div class="page">
    <div class="watermark">AL-HUDA</div>
    <div class="content">

        {{-- Letterhead --}}
        <div class="lh-wrap">
            <img src="{{ asset('images/letterheadtop.png') }}" alt="{{ $clinic->name }}" />
        </div>

        {{-- Title --}}
        <div class="doc-title">
            <h1>Memo</h1>
            <div class="bilingual">Memo Dalaman / Internal Memorandum — Templat Kosong</div>
        </div>

        {{-- Optional patient reference --}}
        <div class="patient-ref">
            <span><b>Rujukan Pesakit</b> (jika berkaitan)</span>
            <span>Nama: <span class="blank-line"></span></span>
            <span>No. K/P: <span class="blank-line"></span></span>
        </div>

        {{-- Opening line --}}
        <div class="closing">
            Dengan ini dimaklumkan perkara berkaitan seperti berikut:
        </div>

        {{-- Ruled writing area --}}
        <div class="section__title">Isi Memo / Memo Content</div>
        <div class="write-area"></div>

        {{-- Signature --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Tandatangan Pegawai Mengeluarkan
                </div>
                <div class="chop-area">Cop Rasmi Klinik</div>
            </div>
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Nama &amp; Jawatan Pegawai
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · Templat Kosong</span>
            <span>Bukan dokumen sah sehingga diisi &amp; dicop</span>
        </div>

    </div>
</div>
</body>
</html>
