<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin: { type: Boolean, default: true },
    clinic: { type: Object, required: true },
})

const services = [
    {
        title: 'Konsultasi & Rawatan',
        desc: 'Pemeriksaan kesihatan, diagnosis dan rawatan oleh doktor berdaftar.',
        icon: 'M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z',
    },
    {
        title: 'Farmasi & Preskripsi',
        desc: 'Pendispensan ubat dengan label preskripsi digital yang jelas.',
        icon: 'M4.5 8.5 19 23M9 11l4.5-4.5a4.95 4.95 0 0 0-7-7L2 4a4.95 4.95 0 0 0 7 7Z',
    },
    {
        title: 'Temujanji & Giliran',
        desc: 'Tempahan temujanji dan sistem giliran masa nyata tanpa menunggu lama.',
        icon: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z',
    },
    {
        title: 'Sijil & Dokumen',
        desc: 'Sijil cuti sakit (MC), surat rujukan dan slip masa yang sah.',
        icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8',
    },
    {
        title: 'Rekod Perubatan Elektronik',
        desc: 'Sejarah rawatan tersimpan selamat dengan akses mudah & teratur.',
        icon: 'M22 12h-4l-3 9L9 3l-3 9H2',
    },
    {
        title: 'Bil & Bayaran',
        desc: 'Pengeluaran invois telus serta resit bayaran untuk setiap rawatan.',
        icon: 'M2 7h20v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zM2 11h20M6 15h4',
    },
]

const hours = [
    { day: 'Isnin – Jumaat', time: '8:00 pagi – 9:00 malam' },
    { day: 'Sabtu – Ahad', time: '9:00 pagi – 6:00 petang' },
    { day: 'Cuti Umum', time: '9:00 pagi – 1:00 tengah hari' },
]
</script>

<template>
    <Head :title="`${clinic.name} · Klinik Perubatan`" />

    <div class="lp">
        <!-- ── Header ── -->
        <header class="lp-header">
            <div class="lp-container lp-header__inner">
                <div class="lp-brand">
                    <img :src="clinic.logo_url" alt="" class="lp-brand__logo" />
                    <div class="lp-brand__text">
                        <span class="lp-brand__name">{{ clinic.name }}</span>
                        <span v-if="clinic.tagline" class="lp-brand__sub">{{ clinic.tagline }}</span>
                    </div>
                </div>

                <nav class="lp-nav">
                    <a href="#perkhidmatan" class="lp-nav__link">Perkhidmatan</a>
                    <a href="#waktu" class="lp-nav__link">Waktu Operasi</a>
                    <a href="#hubungi" class="lp-nav__link">Hubungi</a>
                    <Link v-if="canLogin" :href="route('login')" class="lp-btn lp-btn--primary">
                        Log Masuk Staf →
                    </Link>
                </nav>
            </div>
        </header>

        <!-- ── Hero ── -->
        <section class="lp-hero">
            <div class="lp-container lp-hero__inner">
                <div class="lp-hero__text">
                    <span class="lp-pill">
                        <span class="lp-pill__dot"></span>
                        Klinik Perubatan Berdaftar
                    </span>
                    <h1 class="lp-hero__h">
                        Penjagaan Kesihatan<br />
                        <span class="lp-hero__accent">Mesra & Profesional</span>
                    </h1>
                    <p class="lp-hero__p">
                        {{ clinic.name }} menyediakan perkhidmatan perubatan menyeluruh —
                        daripada konsultasi, farmasi hingga rawatan susulan — dengan
                        sokongan sistem klinikal moden untuk keselesaan anda.
                    </p>
                    <div class="lp-hero__cta">
                        <Link v-if="canLogin" :href="route('login')" class="lp-btn lp-btn--primary lp-btn--lg">
                            Log Masuk Sistem →
                        </Link>
                        <a href="#hubungi" class="lp-btn lp-btn--ghost lp-btn--lg">
                            Hubungi Kami
                        </a>
                    </div>

                    <dl class="lp-stats">
                        <div class="lp-stat">
                            <dt>15+</dt>
                            <dd>Tahun Pengalaman</dd>
                        </div>
                        <div class="lp-stat">
                            <dt>20K+</dt>
                            <dd>Pesakit Dirawat</dd>
                        </div>
                        <div class="lp-stat">
                            <dt>6</dt>
                            <dd>Perkhidmatan Utama</dd>
                        </div>
                    </dl>
                </div>

                <div class="lp-hero__card">
                    <div class="lp-card-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/></svg>
                    </div>
                    <h3 class="lp-hero__card-h">Lawati Kami Hari Ini</h3>
                    <p class="lp-hero__card-p">Tiada temujanji? Tidak mengapa — pesakit walk-in dialu-alukan.</p>
                    <ul class="lp-hero__card-list">
                        <li v-if="clinic.address_full">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ clinic.address_full }}</span>
                        </li>
                        <li v-if="clinic.phone">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                            <span>{{ clinic.phone }}</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span>Buka 7 hari seminggu</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ── Services ── -->
        <section id="perkhidmatan" class="lp-section">
            <div class="lp-container">
                <div class="lp-section__head">
                    <span class="lp-eyebrow">Perkhidmatan Kami</span>
                    <h2 class="lp-section__h">Penjagaan menyeluruh di bawah satu bumbung</h2>
                    <p class="lp-section__p">Perkhidmatan perubatan lengkap yang disokong sistem digital moden.</p>
                </div>

                <div class="lp-grid">
                    <article v-for="s in services" :key="s.title" class="lp-service">
                        <div class="lp-service__icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="s.icon"/></svg>
                        </div>
                        <h3 class="lp-service__h">{{ s.title }}</h3>
                        <p class="lp-service__p">{{ s.desc }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ── Hours ── -->
        <section id="waktu" class="lp-section lp-section--alt">
            <div class="lp-container lp-hours">
                <div class="lp-hours__text">
                    <span class="lp-eyebrow">Waktu Operasi</span>
                    <h2 class="lp-section__h">Kami sentiasa bersedia untuk anda</h2>
                    <p class="lp-section__p">
                        Datang pada bila-bila masa dalam waktu operasi kami. Untuk kecemasan
                        di luar waktu, sila hubungi talian klinik.
                    </p>
                </div>
                <ul class="lp-hours__list">
                    <li v-for="h in hours" :key="h.day" class="lp-hours__row">
                        <span class="lp-hours__day">{{ h.day }}</span>
                        <span class="lp-hours__time">{{ h.time }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- ── Contact ── -->
        <section id="hubungi" class="lp-section">
            <div class="lp-container">
                <div class="lp-section__head">
                    <span class="lp-eyebrow">Hubungi Kami</span>
                    <h2 class="lp-section__h">Maklumat & lokasi klinik</h2>
                </div>

                <div class="lp-contact">
                    <div v-if="clinic.address_full" class="lp-contact__item">
                        <div class="lp-contact__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="lp-contact__label">Alamat</div>
                        <div class="lp-contact__value">{{ clinic.address_full }}</div>
                    </div>
                    <div v-if="clinic.phone" class="lp-contact__item">
                        <div class="lp-contact__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                        </div>
                        <div class="lp-contact__label">Telefon</div>
                        <div class="lp-contact__value">{{ clinic.phone }}</div>
                    </div>
                    <div v-if="clinic.email" class="lp-contact__item">
                        <div class="lp-contact__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        </div>
                        <div class="lp-contact__label">Emel</div>
                        <div class="lp-contact__value">{{ clinic.email }}</div>
                    </div>
                    <div v-if="clinic.reg_number || clinic.ckaps_number" class="lp-contact__item">
                        <div class="lp-contact__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="lp-contact__label">Pendaftaran</div>
                        <div class="lp-contact__value">
                            <template v-if="clinic.reg_number">No. Daftar: {{ clinic.reg_number }}<br /></template>
                            <template v-if="clinic.ckaps_number">CKAPS: {{ clinic.ckaps_number }}</template>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA strip ── -->
        <section class="lp-cta-strip">
            <div class="lp-container lp-cta-strip__inner">
                <div>
                    <h2 class="lp-cta-strip__h">Staf klinik?</h2>
                    <p class="lp-cta-strip__p">Akses sistem pengurusan klinik bersepadu di sini.</p>
                </div>
                <Link v-if="canLogin" :href="route('login')" class="lp-btn lp-btn--white lp-btn--lg">
                    Log Masuk Sistem →
                </Link>
            </div>
        </section>

        <!-- ── Footer ── -->
        <footer class="lp-footer">
            <div class="lp-container lp-footer__inner">
                <div class="lp-brand">
                    <img :src="clinic.logo_url" alt="" class="lp-brand__logo lp-brand__logo--sm" />
                    <span class="lp-footer__name">{{ clinic.name }}</span>
                </div>
                <p class="lp-footer__copy">© 2026 {{ clinic.name }}. Hak cipta terpelihara.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.lp {
    flex: 1;
    min-width: 0;
    height: 100vh;
    overflow-y: auto;
    font-family: var(--font-sans);
    color: var(--fg1);
    background: #fff;
}
.lp-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── Buttons ── */
.lp-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 9px;
    font: 600 13.5px var(--font-sans);
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    white-space: nowrap;
}
.lp-btn--lg { padding: 12px 22px; font-size: 14.5px; border-radius: 10px; }
.lp-btn--primary { background: var(--brand-green); color: #fff; }
.lp-btn--primary:hover { background: var(--brand-green-dark); }
.lp-btn--ghost { background: #fff; color: var(--fg1); border-color: var(--border); }
.lp-btn--ghost:hover { border-color: var(--brand-green); color: var(--brand-green-dark); }
.lp-btn--white { background: #fff; color: var(--brand-green-dark); }
.lp-btn--white:hover { background: var(--brand-green-light); }

/* ── Header ── */
.lp-header {
    position: sticky;
    top: 0;
    z-index: 20;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
}
.lp-header__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}
.lp-brand { display: flex; align-items: center; gap: 11px; }
.lp-brand__logo {
    width: 40px; height: 40px;
    border-radius: 10px;
    object-fit: contain;
    background: var(--brand-green-light);
    padding: 4px;
}
.lp-brand__logo--sm { width: 32px; height: 32px; }
.lp-brand__text { display: flex; flex-direction: column; line-height: 1.2; }
.lp-brand__name { font: 800 15px var(--font-sans); color: var(--brand-green-dark); }
.lp-brand__sub { font: 500 11.5px var(--font-sans); color: var(--fg3); }
.lp-nav { display: flex; align-items: center; gap: 24px; }
.lp-nav__link {
    font: 600 13.5px var(--font-sans);
    color: var(--fg2);
    text-decoration: none;
    transition: color .15s;
}
.lp-nav__link:hover { color: var(--brand-green); }

/* ── Hero ── */
.lp-hero {
    background:
        radial-gradient(900px 400px at 85% -10%, rgba(27,138,74,.10), transparent),
        var(--bg-soft);
    padding: 72px 0 80px;
    border-bottom: 1px solid var(--border);
}
.lp-hero__inner {
    display: grid;
    grid-template-columns: 1.3fr .9fr;
    gap: 48px;
    align-items: center;
}
.lp-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 13px;
    border-radius: 999px;
    background: var(--brand-green-light);
    color: var(--brand-green-dark);
    font: 600 12px var(--font-sans);
    margin-bottom: 20px;
}
.lp-pill__dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--brand-green);
}
.lp-hero__h {
    font: 800 44px/1.12 var(--font-sans);
    color: var(--fg1);
    margin: 0 0 18px;
    letter-spacing: -.02em;
}
.lp-hero__accent { color: var(--brand-green); }
.lp-hero__p {
    font: 400 16px/1.7 var(--font-sans);
    color: var(--fg3);
    margin: 0 0 28px;
    max-width: 520px;
}
.lp-hero__cta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 40px; }
.lp-stats {
    display: flex;
    gap: 40px;
    margin: 0;
    padding-top: 28px;
    border-top: 1px solid var(--border);
}
.lp-stat dt { font: 800 28px var(--font-sans); color: var(--brand-green-dark); }
.lp-stat dd { margin: 2px 0 0; font: 500 12.5px var(--font-sans); color: var(--fg3); }

/* hero card */
.lp-hero__card {
    background: var(--brand-green-dark);
    border-radius: 18px;
    padding: 30px;
    color: #fff;
    box-shadow: var(--shadow-md);
}
.lp-card-icon {
    width: 52px; height: 52px;
    border-radius: 13px;
    background: rgba(255,255,255,.14);
    display: grid; place-items: center;
    color: #fff;
    margin-bottom: 18px;
}
.lp-hero__card-h { font: 800 21px var(--font-sans); margin: 0 0 8px; }
.lp-hero__card-p { font: 400 13.5px/1.6 var(--font-sans); color: rgba(255,255,255,.7); margin: 0 0 22px; }
.lp-hero__card-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
.lp-hero__card-list li { display: flex; align-items: flex-start; gap: 11px; font: 500 13.5px/1.5 var(--font-sans); }
.lp-hero__card-list svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; color: rgba(255,255,255,.85); }

/* ── Sections ── */
.lp-section { padding: 80px 0; }
.lp-section--alt { background: var(--bg-soft); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.lp-section__head { text-align: center; max-width: 600px; margin: 0 auto 48px; }
.lp-eyebrow {
    display: inline-block;
    font: 700 12px var(--font-sans);
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--brand-green);
    margin-bottom: 12px;
}
.lp-section__h {
    font: 800 32px/1.2 var(--font-sans);
    color: var(--fg1);
    margin: 0 0 12px;
    letter-spacing: -.01em;
}
.lp-section__p { font: 400 15px/1.65 var(--font-sans); color: var(--fg3); margin: 0; }

/* services grid */
.lp-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.lp-service {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 26px;
    transition: all .18s;
}
.lp-service:hover { border-color: var(--brand-green); box-shadow: var(--shadow-md); transform: translateY(-3px); }
.lp-service__icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    background: var(--brand-green-light);
    color: var(--brand-green-dark);
    display: grid; place-items: center;
    margin-bottom: 16px;
}
.lp-service__h { font: 700 16.5px var(--font-sans); color: var(--fg1); margin: 0 0 7px; }
.lp-service__p { font: 400 13.5px/1.6 var(--font-sans); color: var(--fg3); margin: 0; }

/* hours */
.lp-hours {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: center;
}
.lp-hours__text { text-align: left; }
.lp-hours__text .lp-section__h { font-size: 28px; }
.lp-hours__list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0; }
.lp-hours__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 12px;
}
.lp-hours__row:last-child { margin-bottom: 0; }
.lp-hours__day { font: 600 14px var(--font-sans); color: var(--fg1); }
.lp-hours__time { font: 600 13.5px var(--font-mono); color: var(--brand-green-dark); }

/* contact */
.lp-contact {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.lp-contact__item {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px;
}
.lp-contact__icon {
    width: 42px; height: 42px;
    border-radius: 11px;
    background: var(--brand-green-light);
    color: var(--brand-green-dark);
    display: grid; place-items: center;
    margin-bottom: 14px;
}
.lp-contact__icon svg { width: 20px; height: 20px; }
.lp-contact__label { font: 700 11.5px var(--font-sans); text-transform: uppercase; letter-spacing: .05em; color: var(--fg3); margin-bottom: 6px; }
.lp-contact__value { font: 500 14px/1.55 var(--font-sans); color: var(--fg1); word-break: break-word; }

/* cta strip */
.lp-cta-strip { background: var(--brand-green); }
.lp-cta-strip__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 44px 24px;
    flex-wrap: wrap;
}
.lp-cta-strip__h { font: 800 26px var(--font-sans); color: #fff; margin: 0 0 6px; }
.lp-cta-strip__p { font: 400 14.5px var(--font-sans); color: rgba(255,255,255,.85); margin: 0; }

/* footer */
.lp-footer { background: #fff; border-top: 1px solid var(--border); }
.lp-footer__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 28px 24px;
    flex-wrap: wrap;
}
.lp-footer__name { font: 800 14px var(--font-sans); color: var(--brand-green-dark); }
.lp-footer__copy { font: 500 12.5px var(--font-sans); color: var(--fg3); margin: 0; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .lp-hero__inner { grid-template-columns: 1fr; gap: 36px; }
    .lp-hero__h { font-size: 36px; }
    .lp-grid { grid-template-columns: repeat(2, 1fr); }
    .lp-hours { grid-template-columns: 1fr; gap: 28px; }
    .lp-contact { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .lp-nav { gap: 14px; }
    .lp-nav__link { display: none; }
    .lp-hero { padding: 48px 0 56px; }
    .lp-hero__h { font-size: 30px; }
    .lp-section { padding: 56px 0; }
    .lp-grid { grid-template-columns: 1fr; }
    .lp-contact { grid-template-columns: 1fr; }
    .lp-stats { gap: 24px; }
    .lp-stat dt { font-size: 24px; }
}
</style>
