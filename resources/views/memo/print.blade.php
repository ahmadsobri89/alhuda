<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $memo->memo_number }} · Memo · {{ $clinic->name }}</title>
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

/* ── QR code ── */
.qr-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    margin-top: 8px;
}
.qr-wrap svg { width: 26mm; height: 26mm; }
.qr-wrap__lbl {
    font: 400 7.5px 'Times New Roman', Times, serif;
    color: #888;
    text-align: center;
    line-height: 1.4;
}

/* ── Document title ── */
.doc-title {
    text-align: center;
    margin-bottom: 10px;
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

/* ── Ref number + nature row ── */
.ref-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.ref-badge span {
    display: inline-block;
    padding: 3px 14px;
    border: 1.5px solid #1b8a4a;
    border-radius: 3px;
    font: 700 11px 'Courier New', monospace;
    color: #1b8a4a;
    letter-spacing: 1px;
}
.urgency-badge {
    padding: 3px 12px;
    border-radius: 3px;
    font: 700 10px 'Times New Roman', Times, serif;
    letter-spacing: .5px;
    text-transform: uppercase;
}
.urgency-normal       { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.urgency-urgent       { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
.urgency-confidential { background: #1f2937; border: 1px solid #1f2937; color: #fff; }

/* ── 2-col info section ── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 12px;
    font: 400 11px 'Times New Roman', Times, serif;
}
.info-col {
    padding: 10px 14px;
}
.info-col + .info-col {
    border-left: 1px solid #ccc;
}
.info-col__title {
    font: 700 9px 'Times New Roman', Times, serif;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #777;
    margin-bottom: 7px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
}
.info-row {
    margin-bottom: 6px;
    line-height: 1.4;
}
.info-lbl {
    display: block;
    color: #777;
    font-size: 8.5px;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 1px;
}
.info-val { display: block; font-weight: 600; color: #000; font-size: 11px; }

/* ── Body sections ── */
.section {
    margin-bottom: 11px;
}
.section__title {
    font: 700 9px 'Times New Roman', Times, serif;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #777;
    margin-bottom: 5px;
}
.section__body {
    font: 400 12px 'Times New Roman', Times, serif;
    color: #000;
    line-height: 1.75;
    border-left: 3px solid #1b8a4a;
    padding: 5px 10px;
    background: #f9fafb;
    min-height: 20px;
    white-space: pre-wrap;
}
.section__body--plain {
    border-left: 3px solid #d1d5db;
    background: #fff;
}

/* ── Closing paragraph ── */
.closing {
    font: 400 12px 'Times New Roman', Times, serif;
    color: #000;
    line-height: 1.8;
    margin-bottom: 14px;
}

/* ── Sig area ── */
.sig-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #ccc;
}
.sig-block { display: flex; flex-direction: column; }
.sig-block__line  { border-bottom: 1px solid #000; height: 26px; margin-bottom: 5px; }
.sig-block__label { font: 400 8.5px 'Times New Roman', Times, serif; color: #555; line-height: 1.5; }
.sig-block__name  { font: 700 9.5px 'Times New Roman', Times, serif; color: #000; margin-top: 2px; }

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
    <span class="print-bar__title">{{ $memo->memo_number }} · {{ $memo->patient->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Memo
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
            <div class="bilingual">Memo Dalaman / Internal Memorandum</div>
        </div>

        {{-- Ref number + nature --}}
        <div class="ref-row">
            <div class="ref-badge"><span>{{ $memo->memo_number }}</span></div>
            <span class="urgency-badge urgency-{{ $memo->nature }}">
                @if($memo->nature === 'normal') Biasa / Normal
                @elseif($memo->nature === 'urgent') Segera / Urgent
                @else SULIT / CONFIDENTIAL
                @endif
            </span>
        </div>

        {{-- Info grid --}}
        <div class="info-grid">
            <div class="info-col">
                <div class="info-col__title">Maklumat Pesakit / Patient</div>
                <div class="info-row">
                    <span class="info-lbl">Nama</span>
                    <span class="info-val">{{ $memo->patient->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">No. Kad Pengenalan</span>
                    <span class="info-val">{{ $memo->patient->ic_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">No. ID Pesakit</span>
                    <span class="info-val">{{ $memo->patient->patient_id }}</span>
                </div>
                @php
                    $dob = $memo->patient->date_of_birth;
                    $age = $dob ? $dob->age : null;
                @endphp
                @if($age)
                <div class="info-row">
                    <span class="info-lbl">Umur / Jantina</span>
                    <span class="info-val">{{ $age }} tahun · {{ $memo->patient->gender === 'male' ? 'Lelaki' : 'Perempuan' }}</span>
                </div>
                @endif
                @if($memo->patient->allergies)
                <div class="info-row" style="margin-top:4px">
                    <span class="info-lbl" style="color:#b45309">⚠ Alahan</span>
                    <span class="info-val" style="color:#b45309">{{ $memo->patient->allergies }}</span>
                </div>
                @endif
            </div>
            <div class="info-col">
                <div class="info-col__title">Maklumat Memo / Memo Details</div>
                <div class="info-row">
                    <span class="info-lbl">Ditujukan Kepada</span>
                    <span class="info-val">{{ $memo->addressed_to }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Perkara</span>
                    <span class="info-val">{{ $memo->subject }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Tarikh Memo</span>
                    <span class="info-val">{{ $memo->issue_date->translatedFormat('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Dikeluarkan Oleh</span>
                    <span class="info-val">{{ $memo->issued_by }}</span>
                </div>
            </div>
        </div>

        {{-- Opening line --}}
        <div class="closing">
            Dengan ini dimaklumkan perkara berkaitan pesakit di atas seperti berikut:
        </div>

        {{-- Content --}}
        <div class="section">
            <div class="section__title">Isi Memo / Memo Content</div>
            <div class="section__body">{{ $memo->content }}</div>
        </div>

        {{-- Additional notes --}}
        @if($memo->notes)
        <div class="section">
            <div class="section__title">Catatan Tambahan / Additional Notes</div>
            <div class="section__body section__body--plain">{{ $memo->notes }}</div>
        </div>
        @endif

        {{-- Closing --}}
        <div class="closing" style="margin-bottom:0">
            Sekian, harap maklum.<br>
            Terima kasih.
        </div>

        {{-- Signature --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Tandatangan Pegawai Mengeluarkan<br>
                    <span class="sig-block__name">{{ $memo->issued_by }}</span>
                </div>
                <div class="chop-area">Cop Rasmi Klinik</div>
            </div>
            <div class="sig-block">
                <div style="font:400 9px 'Times New Roman',serif;line-height:1.9;color:#555">
                    <div style="font-weight:700;color:#000;font-size:10px;margin-bottom:4px">Maklumat Pengeluaran</div>
                    <div>Tarikh: <strong style="color:#000">{{ $memo->created_at->format('d/m/Y H:i') }}</strong></div>
                    <div>No. Rujukan: <strong style="color:#000">{{ $memo->memo_number }}</strong></div>
                    <div>Dikeluarkan Oleh: <strong style="color:#000">{{ $memo->issued_by }}</strong></div>
                </div>
                <div class="qr-wrap">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(96)->margin(0)->generate(
                        route('memo.verify', $memo->verify_token)
                    ) !!}
                    <div class="qr-wrap__lbl">Imbas untuk<br>pengesahan</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $memo->memo_number }}</span>
            <span>Dokumen rasmi — Sah dengan cop klinik · Dilarang meniru</span>
        </div>

    </div>
</div>
</body>
</html>
