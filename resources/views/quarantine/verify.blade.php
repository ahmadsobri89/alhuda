<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengesahan Surat Kuarantin · {{ $quarantine->qn_number }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 14px;
    background: #fffbeb;
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
    max-width: 420px;
    overflow: hidden;
}
.card__header {
    background: #b45309;
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
.card__qn-num {
    color: rgba(255,255,255,.9);
    font-size: 13px;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}
.card__lh {
    width: 100%;
    height: 80px;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}
.card__lh img { width: 100%; display: block; margin-top: -4px; }
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
.detail-row__lbl { font-size: 12px; color: #6b7280; min-width: 130px; flex-shrink: 0; }
.detail-row__val { font-size: 13px; font-weight: 600; color: #111; }

.days-strip {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    border-radius: 8px;
    padding: 12px 16px;
    margin: 14px 0;
}
.days-strip__num {
    font: 900 36px 'Times New Roman', Times, serif;
    color: #b45309;
    line-height: 1;
    flex-shrink: 0;
}
.days-strip__info { font-size: 12px; line-height: 1.7; }
.days-strip__info strong { font-size: 13px; display: block; }
.days-strip__info span { color: #6b7280; }

.info-box {
    border-left: 3px solid #b45309;
    padding: 6px 10px;
    background: #fffbeb;
    border-radius: 0 6px 6px 0;
    margin-bottom: 8px;
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
    margin-top: 4px;
    border-top: 1px solid #f3f4f6;
}
.issued-row span { color: #374151; font-weight: 600; }

.card__footer {
    background: #fffbeb;
    border-top: 1px solid #fde68a;
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
        <div class="card__qn-num">{{ $quarantine->qn_number }}</div>
    </div>

    <div class="card__lh">
        <img src="{{ asset('images/letterhead.png') }}" alt="{{ $clinic->name }}">
    </div>

    <div class="card__body">
        <div class="section-title">Maklumat Pesakit</div>
        <div class="detail-row">
            <span class="detail-row__lbl">Nama</span>
            <span class="detail-row__val">{{ $quarantine->patient->name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__lbl">No. Kad Pengenalan</span>
            <span class="detail-row__val">{{ $quarantine->patient->ic_number }}</span>
        </div>
        <div class="detail-row" style="margin-bottom:14px">
            <span class="detail-row__lbl">Tarikh Dikeluarkan</span>
            <span class="detail-row__val">{{ $quarantine->issue_date->translatedFormat('d F Y') }}</span>
        </div>

        <div class="days-strip">
            <div class="days-strip__num">{{ $quarantine->days }}</div>
            <div class="days-strip__info">
                <strong>hari kuarantin / day(s) quarantine</strong>
                <span>{{ $quarantine->quarantine_start->translatedFormat('d F Y') }}</span>
                <span> → {{ $quarantine->quarantine_end->translatedFormat('d F Y') }}</span>
            </div>
        </div>

        @if($quarantine->diagnosis)
        <div class="info-box">
            <div class="info-box__lbl">Diagnosis / Penyakit</div>
            {{ $quarantine->diagnosis }}
        </div>
        @endif

        @if($quarantine->reason)
        <div class="info-box">
            <div class="info-box__lbl">Sebab Kuarantin</div>
            {{ $quarantine->reason }}
        </div>
        @endif

        <div class="issued-row">
            <div>Dikeluarkan oleh: <span>{{ $quarantine->issued_by }}</span></div>
            <div>{{ $quarantine->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="card__footer">
        Dokumen ini dijana secara digital oleh<br>
        <strong>{{ $clinic->name }}</strong><br>
        QR kod sah dan boleh disahkan pada bila-bila masa.
    </div>
</div>

<div class="page-footer">
    Disahkan: {{ now()->format('d/m/Y H:i') }} · {{ $quarantine->qn_number }}
</div>

</body>
</html>
