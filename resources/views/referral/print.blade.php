<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $referral->ref_number }} · Surat Rujukan · {{ $clinic->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12px;
    color: #000;
    background: #fff;
}

/* A4 portrait */
.page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 16mm 18mm 14mm;
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
    font: 700 70px 'Times New Roman', Times, serif;
    color: rgba(27,138,74,.05);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    letter-spacing: 6px;
}

.content { position: relative; z-index: 1; display: flex; flex-direction: column; flex: 1; }

/* ── Letterhead header ── */
.lh-wrap {
    margin: -16mm -18mm 10px;
    overflow: hidden;
    height: 33mm;
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

/* ── Ref number + urgency row ── */
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
.urgency-routine   { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.urgency-urgent    { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
.urgency-emergency { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

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
    display: flex;
    gap: 4px;
    margin-bottom: 4px;
    line-height: 1.5;
}
.info-lbl { color: #555; min-width: 38mm; flex-shrink: 0; }
.info-lbl::after { content: ':'; margin-right: 2px; }
.info-val { font-weight: 600; color: #000; }

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
    gap: 20px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #ccc;
}
.sig-block { display: flex; flex-direction: column; }
.sig-block__line  { border-bottom: 1px solid #000; height: 32px; margin-bottom: 5px; }
.sig-block__label { font: 400 8.5px 'Times New Roman', Times, serif; color: #555; line-height: 1.5; }
.sig-block__name  { font: 700 9.5px 'Times New Roman', Times, serif; color: #000; margin-top: 2px; }

/* ── Chop area ── */
.chop-area {
    border: 1px dashed #bbb;
    border-radius: 4px;
    width: 40mm;
    height: 28mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font: 400 8px 'Times New Roman', Times, serif;
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
    .page { box-shadow: none; padding: 12mm 16mm; width: 100%; min-height: unset; }
    @page { margin: 0; size: A4 portrait; }
}
</style>
</head>
<body>

<div class="print-bar">
    <span class="print-bar__title">{{ $referral->ref_number }} · {{ $referral->patient->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Surat
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
            <h1>Surat Rujukan</h1>
            <div class="bilingual">Referral Letter / Medical Referral</div>
        </div>

        {{-- Ref number + urgency --}}
        <div class="ref-row">
            <div class="ref-badge"><span>{{ $referral->ref_number }}</span></div>
            <span class="urgency-badge urgency-{{ $referral->urgency }}">
                @if($referral->urgency === 'routine') Rutin / Routine
                @elseif($referral->urgency === 'urgent') Segera / Urgent
                @else KECEMASAN / EMERGENCY
                @endif
            </span>
        </div>

        {{-- Info grid --}}
        <div class="info-grid">
            <div class="info-col">
                <div class="info-col__title">Maklumat Pesakit / Patient</div>
                <div class="info-row">
                    <span class="info-lbl">Nama</span>
                    <span class="info-val">{{ $referral->patient->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">No. Kad Pengenalan</span>
                    <span class="info-val">{{ $referral->patient->ic_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">No. ID Pesakit</span>
                    <span class="info-val">{{ $referral->patient->patient_id }}</span>
                </div>
                @php
                    $dob = $referral->patient->date_of_birth;
                    $age = $dob ? $dob->age : null;
                @endphp
                @if($age)
                <div class="info-row">
                    <span class="info-lbl">Umur / Jantina</span>
                    <span class="info-val">{{ $age }} tahun · {{ $referral->patient->gender === 'male' ? 'Lelaki' : 'Perempuan' }}</span>
                </div>
                @endif
                @if($referral->patient->allergies)
                <div class="info-row" style="margin-top:4px">
                    <span class="info-lbl" style="color:#b45309">⚠ Alahan</span>
                    <span class="info-val" style="color:#b45309">{{ $referral->patient->allergies }}</span>
                </div>
                @endif
            </div>
            <div class="info-col">
                <div class="info-col__title">Maklumat Rujukan / Referral Details</div>
                <div class="info-row">
                    <span class="info-lbl">Dirujuk Kepada</span>
                    <span class="info-val">{{ $referral->referred_to }}</span>
                </div>
                @if($referral->referred_to_dept)
                <div class="info-row">
                    <span class="info-lbl">Jabatan / Unit</span>
                    <span class="info-val">{{ $referral->referred_to_dept }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-lbl">Tarikh Rujukan</span>
                    <span class="info-val">{{ $referral->issue_date->translatedFormat('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Doktor Perujuk</span>
                    <span class="info-val">{{ $referral->issued_by }}</span>
                </div>
            </div>
        </div>

        {{-- Closing salutation --}}
        <div class="closing">
            Yang Hormat,<br>
            <br>
            Dengan hormatnya saya merujuk pesakit di atas kepada pihak
            @if($referral->referred_to_dept)
                {{ $referral->referred_to_dept }},
            @endif
            <strong>{{ $referral->referred_to }}</strong> untuk penilaian dan rawatan lanjut.
        </div>

        {{-- Reason --}}
        <div class="section">
            <div class="section__title">Sebab Rujukan / Reason for Referral</div>
            <div class="section__body">{{ $referral->reason }}</div>
        </div>

        {{-- Clinical summary --}}
        @if($referral->clinical_summary)
        <div class="section">
            <div class="section__title">Ringkasan Klinikal / Clinical Summary</div>
            <div class="section__body section__body--plain">{{ $referral->clinical_summary }}</div>
        </div>
        @endif

        {{-- Relevant history --}}
        @if($referral->relevant_history)
        <div class="section">
            <div class="section__title">Sejarah Perubatan Berkaitan / Relevant History</div>
            <div class="section__body section__body--plain">{{ $referral->relevant_history }}</div>
        </div>
        @endif

        {{-- Signature --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__line"></div>
                <div class="sig-block__label">
                    Tandatangan Doktor Perujuk<br>
                    <span class="sig-block__name">{{ $referral->issued_by }}</span>
                </div>
                <div class="chop-area">Cop Rasmi Klinik</div>
            </div>
            <div class="sig-block">
                <div style="font:400 9px 'Times New Roman',serif;line-height:1.9;color:#555">
                    <div style="font-weight:700;color:#000;font-size:10px;margin-bottom:4px">Maklumat Pengeluaran</div>
                    <div>Tarikh: <strong style="color:#000">{{ $referral->created_at->format('d/m/Y H:i') }}</strong></div>
                    <div>No. Rujukan: <strong style="color:#000">{{ $referral->ref_number }}</strong></div>
                    <div>Dikeluarkan Oleh: <strong style="color:#000">{{ $referral->issued_by }}</strong></div>
                </div>
                <div class="qr-wrap">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(96)->margin(0)->generate(
                        route('referral.verify', $referral->verify_token)
                    ) !!}
                    <div class="qr-wrap__lbl">Imbas untuk<br>pengesahan</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $referral->ref_number }}</span>
            <span>Dokumen rasmi — Sah dengan cop klinik · Dilarang meniru</span>
        </div>

    </div>
</div>
</body>
</html>
