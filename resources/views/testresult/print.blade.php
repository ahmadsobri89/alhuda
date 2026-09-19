<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $tr->tr_number }} · Keputusan Ujian · {{ $clinic->name }}</title>
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
    padding: 10mm 14mm 10mm;
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
    font: 700 72px 'Times New Roman', Times, serif;
    color: rgba(14,116,144,.06);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    letter-spacing: 6px;
}

.content { position: relative; z-index: 1; display: flex; flex-direction: column; flex: 1; }

/* ── Letterhead ── */
.lh-wrap {
    margin: -10mm -14mm 10px;
    flex-shrink: 0;
}
.lh-wrap img { width: 100%; display: block; }

/* ── Document title ── */
.doc-title { text-align: center; margin-bottom: 10px; }
.doc-title h1 {
    font: 700 17px 'Times New Roman', Times, serif;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-bottom: 1.5px solid #000;
    display: inline-block;
    padding-bottom: 3px;
}
.doc-title .bilingual {
    font: 400 10px 'Times New Roman', Times, serif;
    color: #555;
    margin-top: 3px;
}

/* ── TR number badge ── */
.tr-badge { text-align: center; margin-bottom: 12px; }
.tr-badge span {
    display: inline-block;
    padding: 3px 14px;
    border: 1.5px solid #0e7490;
    border-radius: 3px;
    font: 700 11px 'Courier New', monospace;
    color: #0e7490;
    letter-spacing: 1px;
}

/* ── Section heading ── */
.sec-head {
    font: 700 10px 'Times New Roman', Times, serif;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #0e7490;
    border-bottom: 1px solid #0e7490;
    padding-bottom: 3px;
    margin: 12px 0 6px;
}

/* ── Info grid (2 columns, like the source form) ── */
.info-grid {
    width: 100%;
    border-collapse: collapse;
    font: 400 11px 'Times New Roman', Times, serif;
}
.info-grid td {
    border: 1px solid #999;
    padding: 5px 8px;
    vertical-align: top;
}
.info-grid td.lbl {
    width: 34mm;
    background: #f4f6f7;
    color: #333;
    font-size: 10px;
    white-space: nowrap;
}
.info-grid td.val { font-weight: 700; }
.info-grid td.val-wide { font-weight: 700; }

/* ── Results table ── */
.result-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    font: 400 12px 'Times New Roman', Times, serif;
}
.result-table th {
    border: 1px solid #000;
    background: #e6f2f4;
    padding: 7px 8px;
    font: 700 11px 'Times New Roman', Times, serif;
    text-transform: uppercase;
    letter-spacing: .08em;
    text-align: left;
}
.result-table td {
    border: 1px solid #000;
    padding: 7px 8px;
    vertical-align: middle;
}
.result-table th.col-date, .result-table td.col-date { width: 34mm; }
.result-table th.col-kit,  .result-table td.col-kit  { width: auto; }
.result-table th.col-res,  .result-table td.col-res  { width: 44mm; }
.result-table td.col-kit { font-weight: 700; text-transform: uppercase; }
.result-table td.col-res { font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.res-positive { color: #b91c1c; }
.res-negative { color: #15803d; }

/* ── Notes ── */
.notes-box {
    border: 1px dashed #bbb;
    border-radius: 3px;
    padding: 6px 9px;
    margin-top: 10px;
    font: 400 11px 'Times New Roman', Times, serif;
    color: #444;
}
.notes-box__lbl {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #999;
    margin-bottom: 2px;
}

/* ── Signature area ── */
.sig-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: auto;
    padding-top: 18px;
}
.sig-block__lbl {
    font: 700 10px 'Times New Roman', Times, serif;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 26px;
}
.sig-block__line {
    border-bottom: 1px solid #000;
    height: 1px;
    margin-bottom: 4px;
}
.sig-block__name { font: 700 11px 'Times New Roman', Times, serif; }
.sig-block__sub  { font: 400 9px 'Times New Roman', Times, serif; color: #666; }
.chop-area {
    border: 1px dashed #bbb;
    border-radius: 4px;
    height: 30mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font: 400 9px 'Times New Roman', Times, serif;
    color: #bbb;
    text-align: center;
    padding: 4px;
}

/* ── QR ── */
.verify-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 14px;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
}
.verify-row svg { width: 20mm; height: 20mm; flex-shrink: 0; }
.verify-row__txt { font: 400 8.5px 'Times New Roman', Times, serif; color: #888; line-height: 1.6; }

/* ── Footer ── */
.footer {
    padding-top: 5px;
    border-top: 1px solid #e5e7eb;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
    font: 400 8px 'Times New Roman', Times, serif;
    color: #aaa;
}

/* ── Screen bar ── */
@media screen {
    body { background: #d1d5db; padding: 50px 0 40px; }
    .page { box-shadow: 0 4px 20px rgba(0,0,0,.15); background: #fff; }
    .print-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        background: #0e7490; padding: 9px 20px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .print-bar__title { color: #fff; font-size: 12px; font-weight: 600; }
    .print-bar__actions { display: flex; gap: 8px; }
    .print-bar__btn {
        background: #fff; color: #0e7490; border: none;
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
    .page { box-shadow: none; padding: 0 12mm 8mm; width: 100%; min-height: unset; }
    .lh-wrap { margin: 0 -12mm 10px; }
    @page { margin: 0; size: A4 portrait; }
}
</style>
</head>
<body>

@php
    $p   = $tr->patient;
    $dob = $p->date_of_birth;
    $age = $dob ? $dob->age : null;
@endphp

<div class="print-bar">
    <span class="print-bar__title">{{ $tr->tr_number }} · {{ $p->name }}</span>
    <div class="print-bar__actions">
        <button class="print-bar__btn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Keputusan
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
            <h1>Keputusan Ujian Influenza / COVID-19</h1>
            <div class="bilingual">Influenza / COVID-19 Test Result</div>
        </div>

        <div class="tr-badge">
            <span>{{ $tr->tr_number }}</span>
        </div>

        {{-- Patient --}}
        <div class="sec-head">Maklumat Pesakit / Patient Information</div>
        <table class="info-grid">
            <tr>
                <td class="lbl">Patient Name</td>
                <td class="val">{{ mb_strtoupper($p->name) }}</td>
                <td class="lbl">Gender</td>
                <td class="val">{{ $p->gender === 'male' ? 'MALE' : 'FEMALE' }}</td>
            </tr>
            <tr>
                <td class="lbl">Patient ID / PP</td>
                <td class="val">{{ $p->ic_number }}</td>
                <td class="lbl">Nationality</td>
                <td class="val">{{ $tr->nationality ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Category ID</td>
                <td class="val">{{ $tr->category_id ?: '-' }}</td>
                <td class="lbl">Age</td>
                <td class="val">{{ $age !== null ? $age . ' YEARS' : '-' }}</td>
            </tr>
        </table>

        {{-- Requestor --}}
        <div class="sec-head">Maklumat Pemohon / Requestor Information</div>
        <table class="info-grid">
            <tr>
                <td class="lbl">Facility Requestor</td>
                <td class="val">{{ $tr->facility_requestor ?: mb_strtoupper($clinic->name) }}</td>
                <td class="lbl">State</td>
                <td class="val">{{ $tr->state ?: mb_strtoupper((string) $clinic->state) }}</td>
            </tr>
            <tr>
                <td class="lbl">Location Requestor</td>
                <td class="val-wide" colspan="3">{{ $tr->location_requestor ?: mb_strtoupper($clinic->address_full) }}</td>
            </tr>
            <tr>
                <td class="lbl">Name of the Requestor</td>
                <td class="val">{{ $tr->requestor_name ?: mb_strtoupper($tr->issued_by) }}</td>
                <td class="lbl">Facility Transit</td>
                <td class="val">{{ $tr->facility_transit ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Date Received Specimen</td>
                <td class="val-wide" colspan="3">{{ $tr->specimen_received_date->format('d/m/Y') }}</td>
            </tr>
        </table>

        {{-- Results --}}
        <div class="sec-head">Keputusan Ujian / Test Result</div>
        <table class="result-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-kit">Test Kit</th>
                    <th class="col-res">Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tr->results ?? [] as $row)
                    @php
                        $res = (string) ($row['result'] ?? '');
                        $cls = str_contains(mb_strtolower($res), 'positi') ? 'res-positive'
                             : (str_contains(mb_strtolower($res), 'negati') ? 'res-negative' : '');
                    @endphp
                    <tr>
                        <td class="col-date">{{ \Carbon\Carbon::parse($row['test_date'])->format('d/m/Y') }}</td>
                        <td class="col-kit">{{ $row['test_kit'] ?? '-' }}</td>
                        <td class="col-res {{ $cls }}">{{ $res !== '' ? $res : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Notes --}}
        @if($tr->notes)
        <div class="notes-box">
            <div class="notes-box__lbl">Nota / Remarks</div>
            <div>{{ $tr->notes }}</div>
        </div>
        @endif

        {{-- Signature + Chop --}}
        <div class="sig-area">
            <div class="sig-block">
                <div class="sig-block__lbl">Signature Doctor:</div>
                <div class="sig-block__line"></div>
                <div class="sig-block__name">{{ mb_strtoupper($tr->issued_by) }}</div>
                <div class="sig-block__sub">Tarikh dikeluarkan: {{ $tr->issue_date->format('d/m/Y') }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-block__lbl">Cop Klinik:</div>
                <div class="chop-area">Cop Rasmi Klinik</div>
            </div>
        </div>

        {{-- Verification --}}
        <div class="verify-row">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(76)->margin(0)->generate(
                route('testresult.verify', $tr->verify_token)
            ) !!}
            <div class="verify-row__txt">
                Imbas kod QR untuk pengesahan keputusan ini secara dalam talian.<br>
                Scan the QR code to verify this result online.
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $tr->tr_number }}</span>
            <span>Dokumen rasmi — Sah dengan cop klinik · Dilarang meniru</span>
        </div>

    </div>
</div>
</body>
</html>
