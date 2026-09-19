<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengesahan Keputusan Ujian · {{ $tr->tr_number }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 14px;
    background: #ecfeff;
    color: #111;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
}
.card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,.10);
    width: 100%;
    max-width: 460px;
    overflow: hidden;
}
.card__header {
    background: #0e7490;
    padding: 20px 24px 16px;
    text-align: center;
}
.badge-valid {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.15);
    border: 1.5px solid rgba(255,255,255,.4);
    border-radius: 24px;
    padding: 6px 16px;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .5px;
    margin-bottom: 10px;
}
.badge-valid svg { flex-shrink: 0; }
.card__tr-num {
    color: rgba(255,255,255,.9);
    font-size: 13px;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}
.card__lh { width: 100%; background: #fff; border-bottom: 1px solid #e5e7eb; }
.card__lh img { width: 100%; display: block; }
.card__body { padding: 20px 24px; }
.section-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6b7280;
    margin-bottom: 10px;
}
.detail-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 7px 0;
    border-bottom: 1px solid #f3f4f6;
}
.detail-row:last-child { border-bottom: none; }
.detail-row__lbl { font-size: 12px; color: #6b7280; min-width: 140px; flex-shrink: 0; }
.detail-row__val { font-size: 13px; font-weight: 600; color: #111; }

.result-table {
    width: 100%;
    border-collapse: collapse;
    margin: 6px 0 4px;
}
.result-table th {
    background: #f0fdff;
    border-bottom: 1.5px solid #a5f3fc;
    padding: 7px 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #0e7490;
    text-align: left;
}
.result-table td {
    border-bottom: 1px solid #f3f4f6;
    padding: 8px;
    font-size: 12px;
}
.result-table td.kit { font-weight: 700; text-transform: uppercase; }
.result-table td.res { font-weight: 700; text-transform: uppercase; text-align: right; }
.res-positive { color: #b91c1c; }
.res-negative { color: #15803d; }

.info-box {
    border-left: 3px solid #0e7490;
    padding: 6px 10px;
    background: #ecfeff;
    border-radius: 0 6px 6px 0;
    margin-top: 10px;
    font-size: 13px;
    color: #374151;
}
.info-box__lbl { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }

.issued-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #9ca3af;
    padding-top: 12px;
    margin-top: 6px;
    border-top: 1px solid #f3f4f6;
}
.issued-row span { color: #374151; font-weight: 600; }

.card__footer {
    background: #ecfeff;
    border-top: 1px solid #a5f3fc;
    padding: 12px 24px;
    text-align: center;
    font-size: 11px;
    color: #9ca3af;
    line-height: 1.6;
}
.card__footer strong { color: #374151; }
.page-footer { margin-top: 20px; font-size: 11px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>

<div class="card">
    <div class="card__header">
        <div class="badge-valid">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Dokumen Sah / Verified
        </div>
        <div class="card__tr-num">{{ $tr->tr_number }}</div>
    </div>

    <div class="card__lh">
        <img src="{{ asset('images/letterheadtop.png') }}" alt="{{ $clinic->name }}">
    </div>

    <div class="card__body">
        <div class="section-title">Maklumat Pesakit</div>
        <div class="detail-row">
            <span class="detail-row__lbl">Nama</span>
            <span class="detail-row__val">{{ $tr->patient->name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__lbl">No. Kad Pengenalan</span>
            <span class="detail-row__val">{{ $tr->patient->ic_number }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__lbl">Tarikh Terima Spesimen</span>
            <span class="detail-row__val">{{ $tr->specimen_received_date->translatedFormat('d F Y') }}</span>
        </div>
        <div class="detail-row" style="margin-bottom:12px">
            <span class="detail-row__lbl">Tarikh Dikeluarkan</span>
            <span class="detail-row__val">{{ $tr->issue_date->translatedFormat('d F Y') }}</span>
        </div>

        <div class="section-title">Keputusan Ujian</div>
        <table class="result-table">
            <thead>
                <tr>
                    <th>Tarikh</th>
                    <th>Test Kit</th>
                    <th style="text-align:right">Keputusan</th>
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
                        <td>{{ \Carbon\Carbon::parse($row['test_date'])->format('d/m/Y') }}</td>
                        <td class="kit">{{ $row['test_kit'] ?? '-' }}</td>
                        <td class="res {{ $cls }}">{{ $res !== '' ? $res : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($tr->notes)
        <div class="info-box">
            <div class="info-box__lbl">Nota</div>
            {{ $tr->notes }}
        </div>
        @endif

        <div class="issued-row">
            <div>Dikeluarkan oleh: <span>{{ $tr->issued_by }}</span></div>
            <div>{{ $tr->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="card__footer">
        Dokumen ini dijana secara digital oleh<br>
        <strong>{{ $clinic->name }}</strong><br>
        QR kod sah dan boleh disahkan pada bila-bila masa.
    </div>
</div>

<div class="page-footer">
    Disahkan: {{ now()->format('d/m/Y H:i') }} · {{ $tr->tr_number }}
</div>

</body>
</html>
