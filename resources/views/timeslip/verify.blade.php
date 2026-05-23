<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengesahan Slip Masa · {{ $timeslip->slip_number }}</title>
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
    max-width: 420px;
    overflow: hidden;
}

/* ── Top badge ── */
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
.card__slip-num {
    color: rgba(255,255,255,.9);
    font-size: 13px;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* ── Clinic logo strip ── */
.card__lh {
    width: 100%;
    height: 80px;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}
.card__lh img {
    width: 100%;
    display: block;
    margin-top: -4px;
}

/* ── Body ── */
.card__body {
    padding: 20px 24px;
}

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
.detail-row__lbl {
    font-size: 12px;
    color: #6b7280;
    min-width: 130px;
    flex-shrink: 0;
}
.detail-row__val {
    font-size: 13px;
    font-weight: 600;
    color: #111;
}

/* ── Time strip ── */
.time-strip {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 8px;
    align-items: center;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 8px;
    padding: 12px 16px;
    margin: 14px 0;
    text-align: center;
}
.time-strip__time {
    font: 700 22px 'Courier New', monospace;
    color: #1b8a4a;
    line-height: 1;
}
.time-strip__lbl {
    font-size: 10px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: 4px;
}
.time-strip__arrow {
    font-size: 18px;
    color: #1b8a4a;
}
.time-strip__duration {
    grid-column: 1 / -1;
    font-size: 11px;
    color: #1b8a4a;
    font-weight: 600;
    margin-top: 2px;
    padding-top: 8px;
    border-top: 1px dashed #bbf7d0;
}

/* ── Purpose ── */
.purpose-box {
    border-left: 3px solid #1b8a4a;
    padding: 6px 10px;
    background: #f9fafb;
    border-radius: 0 6px 6px 0;
    margin-bottom: 12px;
    font-size: 13px;
    color: #374151;
}
.purpose-box__lbl {
    font-size: 10px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 2px;
}

/* ── Issued by ── */
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

/* ── Footer ── */
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

.page-footer {
    margin-top: 20px;
    font-size: 11px;
    color: #9ca3af;
    text-align: center;
}
</style>
</head>
<body>

@php
    [$ah, $am] = explode(':', $timeslip->arrival_time);
    [$dh, $dm] = explode(':', $timeslip->departure_time);
    $totalMin  = ($dh * 60 + $dm) - ($ah * 60 + $am);
    $hrs       = intdiv($totalMin, 60);
    $mns       = $totalMin % 60;
    $duration  = $hrs > 0 ? "{$hrs} jam {$mns} min" : "{$mns} minit";
    $arrFmt    = \Carbon\Carbon::createFromFormat('H:i:s', trim($timeslip->arrival_time))->format('h:i A');
    $depFmt    = \Carbon\Carbon::createFromFormat('H:i:s', trim($timeslip->departure_time))->format('h:i A');
@endphp

<div class="card">

    {{-- Green header --}}
    <div class="card__header">
        <div class="badge-valid">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Dokumen Sah / Verified
        </div>
        <div class="card__slip-num">{{ $timeslip->slip_number }}</div>
    </div>

    {{-- Letterhead strip --}}
    <div class="card__lh">
        <img src="{{ asset('images/letterhead.png') }}" alt="{{ $clinic->name }}">
    </div>

    <div class="card__body">

        {{-- Patient info --}}
        <div class="section-title">Maklumat Pesakit</div>

        <div class="detail-row">
            <span class="detail-row__lbl">Nama</span>
            <span class="detail-row__val">{{ $timeslip->patient->name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__lbl">No. Kad Pengenalan</span>
            <span class="detail-row__val">{{ $timeslip->patient->ic_number }}</span>
        </div>
        <div class="detail-row" style="margin-bottom:14px">
            <span class="detail-row__lbl">Tarikh Kunjungan</span>
            <span class="detail-row__val">{{ $timeslip->slip_date->translatedFormat('d F Y') }}</span>
        </div>

        {{-- Time strip --}}
        <div class="time-strip">
            <div>
                <div class="time-strip__time">{{ substr($timeslip->arrival_time, 0, 5) }}</div>
                <div class="time-strip__lbl">Masa Tiba<br><small>{{ $arrFmt }}</small></div>
            </div>
            <div class="time-strip__arrow">→</div>
            <div>
                <div class="time-strip__time">{{ substr($timeslip->departure_time, 0, 5) }}</div>
                <div class="time-strip__lbl">Masa Keluar<br><small>{{ $depFmt }}</small></div>
            </div>
            <div class="time-strip__duration">
                Jumlah masa di klinik: <strong>{{ $duration }}</strong>
            </div>
        </div>

        {{-- Purpose --}}
        @if($timeslip->purpose)
        <div class="purpose-box">
            <div class="purpose-box__lbl">Tujuan Kunjungan</div>
            {{ $timeslip->purpose }}
        </div>
        @endif

        {{-- Issued by / date --}}
        <div class="issued-row">
            <div>Dikeluarkan oleh: <span>{{ $timeslip->issued_by }}</span></div>
            <div>{{ $timeslip->created_at->format('d/m/Y H:i') }}</div>
        </div>

    </div>

    <div class="card__footer">
        Dokumen ini dijana secara digital oleh<br>
        <strong>{{ $clinic->name }}</strong><br>
        QR kod sah dan boleh disahkan pada bila-bila masa.
    </div>

</div>

<div class="page-footer">
    Disahkan: {{ now()->format('d/m/Y H:i') }} · {{ $timeslip->slip_number }}
</div>

</body>
</html>
