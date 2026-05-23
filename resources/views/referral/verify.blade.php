<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengesahan Surat Rujukan · {{ $referral->ref_number }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 14px;
    background: #f0fdf4;
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
    background: #1b8a4a;
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
.card__ref-num {
    color: rgba(255,255,255,.9);
    font-size: 13px;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
    margin-bottom: 6px;
}
.urgency-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
}
.urgency-routine   { background: rgba(255,255,255,.2); color: #fff; }
.urgency-urgent    { background: #fef3c7; color: #92400e; }
.urgency-emergency { background: #fee2e2; color: #991b1b; }

.card__lh {
    width: 100%;
    height: 56px;
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
    margin-bottom: 8px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}
.info-group__lbl { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.info-group__val { font-size: 13px; font-weight: 600; color: #111; }

.divider { border: none; border-top: 1px solid #f3f4f6; margin: 14px 0; }

.reason-box {
    border-left: 3px solid #1b8a4a;
    padding: 8px 12px;
    background: #f9fafb;
    border-radius: 0 6px 6px 0;
    margin-bottom: 10px;
    font-size: 13px;
    color: #374151;
    line-height: 1.6;
    white-space: pre-wrap;
}
.reason-box__lbl { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }

.referred-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
}
.referred-box__icon { font-size: 22px; flex-shrink: 0; }
.referred-box__lbl { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
.referred-box__val { font-size: 14px; font-weight: 700; color: #1b8a4a; }
.referred-box__dept { font-size: 12px; color: #374151; }

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
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
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
        <div class="card__ref-num">{{ $referral->ref_number }}</div>
        <span class="urgency-badge urgency-{{ $referral->urgency }}">
            @if($referral->urgency === 'routine') Rutin / Routine
            @elseif($referral->urgency === 'urgent') Segera / Urgent
            @else KECEMASAN / EMERGENCY
            @endif
        </span>
    </div>

    <div class="card__lh">
        <img src="{{ asset('images/letterhead.png') }}" alt="{{ $clinic->name }}">
    </div>

    <div class="card__body">

        <div class="section-title">Maklumat Pesakit</div>
        <div class="info-grid">
            <div>
                <div class="info-group__lbl">Nama</div>
                <div class="info-group__val">{{ $referral->patient->name }}</div>
            </div>
            <div>
                <div class="info-group__lbl">No. Kad Pengenalan</div>
                <div class="info-group__val">{{ $referral->patient->ic_number }}</div>
            </div>
            <div>
                <div class="info-group__lbl">Tarikh Rujukan</div>
                <div class="info-group__val">{{ $referral->issue_date->translatedFormat('d F Y') }}</div>
            </div>
            <div>
                <div class="info-group__lbl">Doktor Perujuk</div>
                <div class="info-group__val">{{ $referral->issued_by }}</div>
            </div>
        </div>

        <div class="referred-box">
            <div class="referred-box__icon">🏥</div>
            <div>
                <div class="referred-box__lbl">Dirujuk Kepada / Referred To</div>
                <div class="referred-box__val">{{ $referral->referred_to }}</div>
                @if($referral->referred_to_dept)
                <div class="referred-box__dept">{{ $referral->referred_to_dept }}</div>
                @endif
            </div>
        </div>

        <div class="reason-box">
            <div class="reason-box__lbl">Sebab Rujukan / Reason</div>
            {{ $referral->reason }}
        </div>

        <div class="issued-row">
            <div>Dikeluarkan oleh: <span>{{ $referral->issued_by }}</span></div>
            <div>{{ $referral->created_at->format('d/m/Y H:i') }}</div>
        </div>

    </div>

    <div class="card__footer">
        Dokumen ini dijana secara digital oleh<br>
        <strong>{{ $clinic->name }}</strong><br>
        QR kod sah dan boleh disahkan pada bila-bila masa.
    </div>
</div>

<div class="page-footer">
    Disahkan: {{ now()->format('d/m/Y H:i') }} · {{ $referral->ref_number }}
</div>

</body>
</html>
