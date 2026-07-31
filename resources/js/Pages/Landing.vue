<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    clinic: { type: Object, required: true },
    tips: { type: Array, default: () => [] },
    testimonials: { type: Array, default: () => [] },
})

const telHref = computed(() => 'tel:' + (props.clinic.phone ?? '').replace(/[^\d+]/g, ''))

const testiPalette = ['#1A3423', '#8A6E3A', '#5C6E4F', '#3A5A6E', '#6E3A4F']

function testiInitial(name) {
    if (!name) return '?'
    const parts = name.trim().split(/\s+/)
    const honorifics = /^(pn\.?|en\.?|cik|dr\.?|tuan|puan)$/i
    const target = parts.length > 1 && honorifics.test(parts[0]) ? parts[1] : parts[0]
    return target.charAt(0).toUpperCase()
}

function testiColor(i) {
    return testiPalette[i % testiPalette.length]
}

// ─── Mobile nav ──────────────────────────────────────────────────────────
const mobileNavOpen = ref(false)

// ─── Lightbox ────────────────────────────────────────────────────────────
const lightboxTip = ref(null)
function openLightbox(tip) { lightboxTip.value = tip }
function closeLightbox() { lightboxTip.value = null }

function onKeydown(e) {
    if (e.key === 'Escape') closeLightbox()
}
onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
    <Head :title="`${clinic.name} · Klinik Perubatan`" />

    <div class="lp" id="top">
        <!-- ── Header ── -->
        <header class="lp-header">
            <div class="nav-inner">
                <a class="brand" href="#top">
                    <img :src="clinic.logo_url" :alt="`Logo ${clinic.name}`" />
                    <span class="name">{{ clinic.name }}<small>Jitra, Kedah</small></span>
                </a>
                <nav :class="['links', mobileNavOpen ? 'open' : '']">
                    <a href="#perkhidmatan" @click="mobileNavOpen = false">Perkhidmatan</a>
                    <a href="#waktu-operasi" @click="mobileNavOpen = false">Waktu Operasi</a>
                    <a v-if="tips.length" href="#tips" @click="mobileNavOpen = false">Tips Kesihatan</a>
                    <a v-if="testimonials.length" href="#testimoni" @click="mobileNavOpen = false">Testimoni</a>
                    <a href="#hubungi" @click="mobileNavOpen = false">Hubungi</a>
                </nav>
                <div class="header-actions">
                    <Link v-if="canLogin" :href="route('login')" class="staff-btn">Log Masuk Staf</Link>
                    <button class="burger" aria-label="Menu" @click="mobileNavOpen = !mobileNavOpen">
                        <span></span><span></span><span></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- ── Hero ── -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-copy">
                    <span v-if="clinic.tagline" class="script">{{ clinic.tagline }} ♡</span>
                    <h1>Rawatan mesra untuk<br /><em>seisi keluarga</em> anda</h1>
                    <p>{{ clinic.name }} menyediakan penjagaan kesihatan yang mesra, sabar dan boleh dipercayai — dari kanak-kanak sehingga warga emas, kami sentiasa ada untuk keluarga anda di Jitra.</p>
                    <div class="hero-ctas">
                        <a class="btn btn-primary" href="#hubungi">Buat Temujanji</a>
                        <a v-if="clinic.phone" class="tel-link" :href="telHref">
                            <span class="ico">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6.6 10.2c1.2 2.4 3.1 4.3 5.5 5.5l1.8-1.8c.2-.2.6-.3.9-.2 1 .3 2 .5 3.1.5.5 0 .9.4.9.9V19c0 .5-.4.9-.9.9C9.9 19.9 4.1 14.1 4.1 6.9c0-.5.4-.9.9-.9h3.1c.5 0 .9.4.9.9 0 1.1.2 2.1.5 3.1.1.3 0 .7-.2.9l-1.7 1.8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /></svg>
                            </span>
                            Hubungi Kami<br /><span style="font-weight:400;color:var(--fg3);font-size:13px;">{{ clinic.phone }}</span>
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat"><b>6</b><span>Perkhidmatan Klinikal</span></div>
                        <div class="stat"><b>13 jam</b><span>Waktu Operasi Harian</span></div>
                        <div class="stat"><b>100%</b><span>Layanan Mesra Keluarga</span></div>
                    </div>
                </div>
                <div class="hero-photo">
                    <img class="hero-leaf-tr" src="/images/landing/leaf-left.png" alt="" style="transform:scaleX(-1);" />
                    <div class="hero-photo-frame">
                        <img src="/images/landing/doctor.jpg" :alt="`Doktor ${clinic.name} sedia membantu di klinik`" />
                    </div>
                    <div class="hero-badge">
                        <span class="dot">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7.5-4.6-10-9.3C.5 8 2 4.5 5.5 4c2-.3 3.9.7 5 2.3C11.6 4.7 13.5 3.7 15.5 4c3.5.5 5 4 3.5 7.7C16.5 16.4 12 21 12 21Z" stroke="currentColor" stroke-width="1.6" /></svg>
                        </span>
                        <span class="txt"><b>Dipercayai keluarga</b><span>di Jitra sejak sekian lama</span></span>
                    </div>
                    <img class="hero-leaf-bl" src="/images/landing/leaf-left.png" alt="" />
                </div>
            </div>
        </section>

        <!-- ── Services ── -->
        <section class="services" id="perkhidmatan">
            <div class="wrap">
                <div class="services-head">
                    <span class="eyebrow">Perkhidmatan Kami</span>
                    <h2 class="section-title">Penjagaan menyeluruh, satu bumbung</h2>
                    <p class="section-sub center">Daripada rawatan harian sehingga prosedur ringkas, pasukan kami mengendalikan setiap keperluan kesihatan keluarga anda dengan teliti dan penuh mesra.</p>
                </div>
                <div class="services-grid">
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M8 3v4M16 3v4M4 8h16M6 8v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /><path d="M9 13c1 1 2 1 3 0s2-1 3 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span>
                        <h3>Perubatan Keluarga</h3>
                        <p>Konsultasi dan rawatan menyeluruh untuk seisi keluarga.</p>
                    </div>
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M8 5a5 5 0 0 1 8 4c0 3-3 3-3 6a3 3 0 0 1-6 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /><circle cx="8" cy="19" r="1.6" stroke="currentColor" stroke-width="1.4" /></svg></span>
                        <h3>Rawatan ENT</h3>
                        <p>Pemeriksaan dan rawatan telinga, hidung, tekak.</p>
                    </div>
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M9 3h6M10 3v6l-4.5 8A2 2 0 0 0 7.2 20h9.6a2 2 0 0 0 1.7-3L14 9V3" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /><path d="M8 15h8" stroke="currentColor" stroke-width="1.6" /></svg></span>
                        <h3>Saringan Kesihatan</h3>
                        <p>Pemeriksaan kesihatan menyeluruh.</p>
                    </div>
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="m18 6-3-3-9 9v3h3l9-9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /><path d="m13 5 3 3M5 19h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span>
                        <h3>Vaksinasi</h3>
                        <p>Vaksin kanak-kanak dan dewasa.</p>
                    </div>
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><rect x="4" y="9" width="16" height="6" rx="3" transform="rotate(-45 12 12)" stroke="currentColor" stroke-width="1.6" /><path d="m9.5 9.5 5 5" stroke="currentColor" stroke-width="1.6" /></svg></span>
                        <h3>Prosedur Kecil</h3>
                        <p>Prosedur ringkas dijalankan dengan selamat.</p>
                    </div>
                    <div class="service-card">
                        <span class="service-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M6 12c2-3 4-4 6-4s4 1 6 4c-2 3-4 4-6 4s-4-1-6-4Z" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="12" r="1.8" stroke="currentColor" stroke-width="1.4" /></svg></span>
                        <h3>Pembedahan Minor</h3>
                        <p>Jahitan luka, pengeluaran benjolan.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Hours ── -->
        <section class="hours" id="waktu-operasi">
            <div class="wrap">
                <div class="hours-grid">
                    <div class="hours-copy">
                        <span class="eyebrow">Waktu Operasi</span>
                        <h2 class="section-title">Waktu panjang, supaya kami<br />sentiasa ada untuk anda</h2>
                        <p class="section-sub">Kami buka lebih lama pada hari biasa supaya keluarga anda boleh berjumpa doktor pada waktu yang sesuai — selepas kerja atau sekolah sekalipun.</p>
                    </div>
                    <div class="hours-card">
                        <div class="hours-row">
                            <span class="day"><span class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6" /><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg></span>Sabtu – Khamis</span>
                            <span class="time">9.00 pagi – 10.00 malam</span>
                        </div>
                        <div class="hours-row closed">
                            <span class="day"><span class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>Jumaat</span>
                            <span class="time">Tutup</span>
                        </div>
                    </div>
                </div>
                <div class="hours-note">
                    <span class="i">♡</span>
                    <p>Jom datang lebih awal pada waktu puncak (petang &amp; malam) untuk mengelakkan menunggu lama.</p>
                </div>
            </div>
        </section>

        <!-- ── Tips Kesihatan ── -->
        <section v-if="tips.length" class="tips" id="tips">
            <div class="wrap">
                <div class="tips-head">
                    <span class="eyebrow">Tips Kesihatan</span>
                    <h2 class="section-title">Cerita &amp; tip dari klinik kami</h2>
                    <p class="section-sub center">Kongsian ringkas yang kami kongsikan di Instagram, TikTok dan Facebook — imbas untuk baca infografik penuh.</p>
                </div>

                <div v-for="(tip, i) in tips" :key="tip.id" :class="['tip-row', i % 2 === 1 ? 'reverse' : '']">
                    <div class="tip-media" @click="openLightbox(tip)">
                        <img :src="tip.image_url" :alt="`Infografik: ${tip.title}`" />
                        <span class="zoom-hint"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.7" /><path d="m20 20-4.3-4.3M8 10.5h5M10.5 8v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg></span>
                    </div>
                    <div class="tip-text">
                        <span class="tip-label">Tips Kesihatan</span>
                        <h3>{{ tip.title }}</h3>
                        <p>Kongsian ringkas daripada klinik kami — imbas untuk baca infografik penuh dan ketahui lebih lanjut.</p>
                        <span class="tip-cta" @click="openLightbox(tip)">
                            Lihat Infografik Penuh
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Testimoni Pesakit ── -->
        <section v-if="testimonials.length" class="testi" id="testimoni">
            <div class="wrap">
                <div class="testi-head">
                    <span class="eyebrow">Testimoni Pesakit</span>
                    <h2 class="section-title">Kata mereka yang pernah datang</h2>
                    <p class="section-sub center">Sedikit luahan hati daripada keluarga yang pernah mendapatkan rawatan di {{ clinic.name }}.</p>
                </div>
                <div class="testi-grid">
                    <div v-for="(item, i) in testimonials" :key="item.id" class="testi-card">
                        <span class="testi-quote-mark">&ldquo;</span>
                        <p class="quote">{{ item.quote }}</p>
                        <div class="testi-person">
                            <span class="testi-avatar" :style="{ background: testiColor(i) }">{{ testiInitial(item.patient_name) }}</span>
                            <span class="info"><b>{{ item.patient_name }}</b><span v-if="item.patient_area">{{ item.patient_area }}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Contact ── -->
        <section class="contact" id="hubungi">
            <div class="wrap">
                <div class="contact-grid">
                    <div class="contact-info">
                        <span class="eyebrow">Hubungi Kami</span>
                        <h2 class="section-title">Kami sedia membantu<br />keluarga anda</h2>
                        <p class="section-sub">Datang terus ke klinik kami, atau hubungi kami dahulu untuk sebarang pertanyaan.</p>
                        <div class="contact-cards">
                            <div v-if="clinic.address_full" class="contact-card">
                                <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.1-7-11a7 7 0 0 1 14 0c0 5.9-7 11-7 11Z" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6" /></svg></span>
                                <div><h4>Alamat</h4><p>{{ clinic.address_full }}</p></div>
                            </div>
                            <div v-if="clinic.phone" class="contact-card">
                                <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6.6 10.2c1.2 2.4 3.1 4.3 5.5 5.5l1.8-1.8c.2-.2.6-.3.9-.2 1 .3 2 .5 3.1.5.5 0 .9.4.9.9V19c0 .5-.4.9-.9.9C9.9 19.9 4.1 14.1 4.1 6.9c0-.5.4-.9.9-.9h3.1c.5 0 .9.4.9.9 0 1.1.2 2.1.5 3.1.1.3 0 .7-.2.9l-1.7 1.8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /></svg></span>
                                <div><h4>Telefon / WhatsApp</h4><a :href="telHref">{{ clinic.phone }}</a></div>
                            </div>
                        </div>
                    </div>
                    <div class="contact-map">
                        <img class="leaf-deco" src="/images/landing/leaf-left.png" alt="" />
                        <span class="pin"><svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.1-7-11a7 7 0 0 1 14 0c0 5.9-7 11-7 11Z" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6" /></svg></span>
                        <h3>{{ clinic.name }}</h3>
                        <p>Mudah diakses dan mempunyai ruang letak kereta.</p>
                        <div class="map-actions">
                            <a v-if="clinic.google_maps_url" class="btn btn-primary" :href="clinic.google_maps_url" target="_blank" rel="noopener">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.1-7-11a7 7 0 0 1 14 0c0 5.9-7 11-7 11Z" stroke="currentColor" stroke-width="1.8" /><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.8" /></svg>
                                Google Maps
                            </a>
                            <a v-if="clinic.waze_url" class="btn btn-outline btn-on-map" :href="clinic.waze_url" target="_blank" rel="noopener">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 11 21 3l-8 18-2-7-8-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /></svg>
                                Waze
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA strip ── -->
        <section class="cta-strip">
            <div class="wrap cta-strip-inner">
                <div>
                    <span v-if="clinic.tagline" class="script" style="color:var(--brand-gold);">{{ clinic.tagline }} ♡</span>
                    <h2>Sedia bertemu doktor keluarga anda hari ini?</h2>
                </div>
                <div class="cta-strip-actions">
                    <a class="btn btn-cream" href="#hubungi">Buat Temujanji</a>
                    <a v-if="clinic.phone" class="btn btn-gold" :href="telHref">Hubungi {{ clinic.phone }}</a>
                </div>
            </div>
        </section>

        <!-- ── Footer ── -->
        <footer>
            <div class="wrap">
                <div class="footer-grid">
                    <div class="footer-col">
                        <div class="footer-brand">
                            <img :src="clinic.logo_url" :alt="`Logo ${clinic.name}`" />
                            <b>{{ clinic.name }}</b>
                        </div>
                        <p>Klinik keluarga pilihan anda di Jitra, Kedah — penjagaan mesra, sabar dan boleh dipercayai untuk seisi keluarga.</p>
                        <div class="footer-social" style="margin-top:20px;">
                            <a href="#" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14 9h3V5h-3c-2.2 0-4 1.8-4 4v2H7v4h3v6h4v-6h3l1-4h-4V9c0-.6.4-1 1-1Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" /></svg></a>
                            <a href="#" aria-label="Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="1.3" /><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.3" /><circle cx="16.6" cy="7.4" r="1" fill="currentColor" /></svg></a>
                            <a href="#" aria-label="TikTok"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14 4v10.5a3 3 0 1 1-2-2.8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" /><path d="M14 4c.3 2.5 2 4.3 4.5 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" /></svg></a>
                        </div>
                    </div>
                    <div class="footer-col">
                        <h4>Pautan Pantas</h4>
                        <ul>
                            <li><a href="#perkhidmatan">Perkhidmatan</a></li>
                            <li><a href="#waktu-operasi">Waktu Operasi</a></li>
                            <li v-if="tips.length"><a href="#tips">Tips Kesihatan</a></li>
                            <li v-if="testimonials.length"><a href="#testimoni">Testimoni</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4>Perkhidmatan</h4>
                        <ul>
                            <li><a href="#perkhidmatan">Perubatan Keluarga</a></li>
                            <li><a href="#perkhidmatan">Rawatan ENT</a></li>
                            <li><a href="#perkhidmatan">Vaksinasi</a></li>
                            <li><a href="#perkhidmatan">Pembedahan Minor</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4>Hubungi</h4>
                        <p v-if="clinic.address_full" style="margin-bottom:10px;">{{ clinic.address_full }}</p>
                        <p v-if="clinic.phone"><a :href="telHref">{{ clinic.phone }}</a></p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© 2026 {{ clinic.name }}. Hak cipta terpelihara.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- ── Lightbox ── -->
    <Teleport to="body">
        <div class="lightbox" :class="{ open: lightboxTip }" @click="e => { if (e.target.classList.contains('lightbox')) closeLightbox() }">
            <span class="lightbox-close" @click="closeLightbox">✕</span>
            <img v-if="lightboxTip" :src="lightboxTip.image_url" :alt="`Infografik: ${lightboxTip.title}`" />
        </div>
    </Teleport>
</template>

<style scoped>
.lp {
    --brand-forest: #1A3423;
    --brand-forest-dark: #12281A;
    --brand-forest-light: #E7EEE8;
    --brand-gold: #C9A768;
    --brand-gold-light: #F0E4C8;
    --bg-cream: #F5EEE4;
    --bg-cream-soft: #FAF6F0;
    --lp-fg1: #16241A;
    --lp-fg2: #42513F;
    --lp-fg3: #6E7A6A;
    --lp-border: #E3DCC8;
    --lp-font-sans: var(--font-sans);
    --lp-font-script: 'Segoe Script', 'Brush Script MT', cursive;
    --lp-shadow-sm: 0 1px 2px rgba(20,30,20,.08);
    --lp-shadow-md: 0 8px 24px rgba(20,30,20,.12);

    flex: 1;
    min-width: 0;
    height: 100vh;
    overflow-y: auto;
    font-family: var(--lp-font-sans);
    background: var(--bg-cream);
    color: var(--lp-fg2);
    line-height: 1.6;
}
.lp :deep(img) { max-width: 100%; display: block; }
.lp :deep(a) { color: inherit; text-decoration: none; }
.lp :deep(ul) { margin: 0; padding: 0; list-style: none; }
.lp section { position: relative; }
.wrap { max-width: 1180px; margin: 0 auto; padding: 0 32px; }
.eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: var(--brand-forest); background: var(--brand-gold-light);
    padding: 6px 16px; border-radius: 999px; margin-bottom: 18px;
}
.eyebrow::before { content: "♡"; color: var(--brand-gold); font-size: 12px; }
.lp h1, .lp h2, .lp h3 { color: var(--lp-fg1); margin: 0; font-weight: 700; }
.script { font-family: var(--lp-font-script); color: var(--brand-gold); font-size: 28px; font-weight: 400; display: inline-block; }
.section-title { font-size: 36px; line-height: 1.2; letter-spacing: -.01em; }
.section-sub { color: var(--lp-fg3); font-size: 16px; margin-top: 12px; max-width: 560px; }
.center { text-align: center; margin-left: auto; margin-right: auto; }
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 15px 30px; border-radius: 999px; font-weight: 700; font-size: 15px;
    cursor: pointer; border: none; transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    white-space: nowrap;
}
.btn-primary { background: var(--brand-forest); color: var(--bg-cream-soft); box-shadow: var(--lp-shadow-md); }
.btn-primary:hover { background: var(--brand-forest-dark); transform: translateY(-2px); }
.btn-outline { background: transparent; color: var(--brand-forest); border: 1.5px solid var(--brand-forest); }
.btn-outline:hover { background: var(--brand-forest-light); transform: translateY(-2px); }
.btn-gold { background: var(--brand-gold); color: var(--brand-forest-dark); }
.btn-gold:hover { background: #b89354; transform: translateY(-2px); }
.btn-cream { background: var(--bg-cream-soft); color: var(--brand-forest); }
.btn-cream:hover { background: #fff; transform: translateY(-2px); }

/* ── Header ── */
.lp-header { position: sticky; top: 0; z-index: 100; background: rgba(245,238,228,.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--lp-border); }
.nav-inner { max-width: 1180px; margin: 0 auto; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
.brand { display: flex; align-items: center; gap: 12px; }
.brand img { height: 46px; width: 46px; border-radius: 50%; object-fit: cover; }
.brand .name { font-weight: 800; color: var(--brand-forest); font-size: 19px; letter-spacing: -.01em; }
.brand .name small { display: block; font-weight: 500; font-size: 11px; color: var(--lp-fg3); letter-spacing: .08em; text-transform: uppercase; }
nav.links { display: flex; gap: 34px; align-items: center; }
nav.links a { font-size: 14.5px; font-weight: 600; color: var(--lp-fg2); position: relative; padding: 4px 0; }
nav.links a::after { content: ""; position: absolute; left: 0; right: 0; bottom: -3px; height: 2px; background: var(--brand-gold); transform: scaleX(0); transform-origin: left; transition: transform .2s ease; }
nav.links a:hover { color: var(--brand-forest); }
nav.links a:hover::after { transform: scaleX(1); }
.header-actions { display: flex; align-items: center; gap: 14px; }
.staff-btn { font-size: 13.5px; font-weight: 700; color: var(--brand-forest); border: 1.5px solid var(--brand-forest); padding: 9px 20px; border-radius: 999px; }
.staff-btn:hover { background: var(--brand-forest); color: var(--bg-cream-soft); }
.burger { display: none; background: none; border: none; cursor: pointer; padding: 6px; }
.burger span { display: block; width: 24px; height: 2px; background: var(--brand-forest); margin: 5px 0; border-radius: 2px; }

/* ── Hero ── */
.hero { background: var(--bg-cream); padding: 64px 0 0; overflow: hidden; }
.hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 24px; align-items: center; max-width: 1180px; margin: 0 auto; padding: 0 32px; position: relative; }
.hero-copy { position: relative; z-index: 2; padding-bottom: 64px; }
.hero-copy .script { font-size: 32px; margin-bottom: 10px; }
.hero-copy h1 { font-size: 52px; line-height: 1.12; letter-spacing: -.02em; margin-bottom: 22px; }
.hero-copy h1 em { font-style: normal; color: var(--brand-forest); background: linear-gradient(180deg, transparent 62%, var(--brand-gold-light) 62%); }
.hero-copy p { font-size: 17px; color: var(--lp-fg2); max-width: 460px; margin-bottom: 34px; }
.hero-ctas { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
.hero-ctas .tel-link { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--brand-forest); font-size: 15px; }
.hero-ctas .tel-link .ico { width: 44px; height: 44px; border-radius: 50%; background: var(--brand-forest-light); display: flex; align-items: center; justify-content: center; color: var(--brand-forest); flex-shrink: 0; }
.hero-stats { display: flex; gap: 36px; margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--lp-border); max-width: 480px; }
.hero-stats .stat b { display: block; font-size: 26px; color: var(--brand-forest); font-weight: 800; }
.hero-stats .stat span { font-size: 13px; color: var(--lp-fg3); }
.hero-photo { position: relative; z-index: 1; }
.hero-photo-frame { position: relative; border-radius: 36px 120px 36px 36px; overflow: hidden; box-shadow: var(--lp-shadow-md); aspect-ratio: 520/560; }
.hero-photo-frame img { width: 100%; height: 100%; object-fit: cover; object-position: center 18%; }
.hero-badge { position: absolute; bottom: 22px; left: -22px; background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 20px; padding: 16px 20px; box-shadow: var(--lp-shadow-md); display: flex; align-items: center; gap: 12px; max-width: 230px; z-index: 3; }
.hero-badge .dot { width: 40px; height: 40px; border-radius: 50%; background: var(--brand-forest); color: var(--brand-gold-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.hero-badge .txt b { display: block; font-size: 13.5px; color: var(--lp-fg1); }
.hero-badge .txt span { font-size: 12px; color: var(--lp-fg3); }
.hero-leaf-tr { position: absolute; top: -10px; right: -6px; width: 150px; opacity: .85; pointer-events: none; z-index: 0; }
.hero-leaf-bl { position: absolute; bottom: 10px; left: -40px; width: 130px; opacity: .55; pointer-events: none; z-index: 0; transform: scaleX(-1) rotate(8deg); }

/* ── Services ── */
.services { padding: 100px 0 90px; background: var(--bg-cream); }
.services-head { max-width: 640px; margin: 0 auto 56px; text-align: center; }
.services-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; }
.service-card { background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 26px; padding: 34px 28px; box-shadow: var(--lp-shadow-sm); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
.service-card:hover { transform: translateY(-6px); box-shadow: var(--lp-shadow-md); border-color: var(--brand-gold); }
.service-icon { width: 58px; height: 58px; border-radius: 50%; background: var(--brand-forest); display: flex; align-items: center; justify-content: center; color: var(--brand-gold-light); margin-bottom: 22px; }
.service-card h3 { font-size: 19px; margin-bottom: 10px; }
.service-card p { font-size: 14.5px; color: var(--lp-fg3); margin: 0; }

/* ── Hours ── */
.hours { padding: 90px 0; background: var(--brand-forest-light); position: relative; }
.hours-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
.hours-copy .section-title { margin-bottom: 16px; }
.hours-card { background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 28px; padding: 12px; box-shadow: var(--lp-shadow-md); }
.hours-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 24px 26px; border-radius: 20px; }
.hours-row + .hours-row { border-top: 1px solid var(--lp-border); }
.hours-row .day { display: flex; align-items: center; gap: 14px; font-weight: 700; color: var(--lp-fg1); font-size: 16.5px; }
.hours-row .day .ico { width: 40px; height: 40px; border-radius: 50%; background: var(--brand-forest); color: var(--brand-gold-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.hours-row .time { font-weight: 700; color: var(--brand-forest); font-size: 16.5px; text-align: right; }
.hours-row.closed { background: var(--bg-cream); }
.hours-row.closed .day .ico { background: var(--lp-fg3); }
.hours-row.closed .time { color: var(--lp-fg3); }
.hours-note { display: flex; gap: 12px; align-items: flex-start; margin-top: 22px; background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 18px; padding: 18px 20px; }
.hours-note span.i { color: var(--brand-gold); font-size: 18px; }
.hours-note p { margin: 0; font-size: 13.5px; color: var(--lp-fg2); }

/* ── Tips (editorial rows) ── */
.tips { padding: 100px 0 90px; background: var(--bg-cream); }
.tips-head { max-width: 640px; margin: 0 auto 60px; text-align: center; position: relative; }
.tip-row { display: grid; grid-template-columns: .85fr 1.15fr; gap: 56px; align-items: center; margin-bottom: 76px; }
.tip-row:last-child { margin-bottom: 0; }
.tip-row.reverse .tip-media { order: 2; }
.tip-row.reverse .tip-text { order: 1; }
.tip-media { position: relative; cursor: pointer; border-radius: 28px; overflow: hidden; box-shadow: var(--lp-shadow-md); aspect-ratio: 4/5; }
.tip-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.tip-media:hover img { transform: scale(1.04); }
.tip-media .zoom-hint { position: absolute; bottom: 16px; right: 16px; width: 46px; height: 46px; border-radius: 50%; background: rgba(245,238,228,.92); color: var(--brand-forest); display: flex; align-items: center; justify-content: center; box-shadow: var(--lp-shadow-sm); }
.tip-label { font-size: 12.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--brand-gold); margin-bottom: 12px; display: block; }
.tip-text h3 { font-size: 27px; line-height: 1.3; margin-bottom: 16px; }
.tip-text p { font-size: 15px; color: var(--lp-fg2); margin-bottom: 24px; max-width: 460px; }
.tip-cta { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; color: var(--brand-forest); font-size: 14.5px; cursor: pointer; border-bottom: 1.5px solid var(--brand-gold); padding-bottom: 3px; }

/* ── Testimonials ── */
.testi { padding: 100px 0 100px; background: var(--brand-forest-light); }
.testi-head { max-width: 640px; margin: 0 auto 56px; text-align: center; }
.testi-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; }
.testi-card { background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 26px; padding: 34px 30px; box-shadow: var(--lp-shadow-sm); display: flex; flex-direction: column; }
.testi-quote-mark { font-family: Georgia, serif; font-size: 52px; color: var(--brand-gold); line-height: 1; height: 30px; margin-bottom: 6px; }
.testi-card p.quote { font-size: 15px; color: var(--lp-fg2); margin: 0 0 26px; flex-grow: 1; }
.testi-person { display: flex; align-items: center; gap: 14px; padding-top: 18px; border-top: 1px solid var(--lp-border); }
.testi-avatar { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; color: var(--bg-cream-soft); flex-shrink: 0; }
.testi-person .info { display: flex; flex-direction: column; }
.testi-person .info b { font-size: 14.5px; color: var(--lp-fg1); }
.testi-person .info span { font-size: 12.5px; color: var(--lp-fg3); }

/* ── Contact ── */
.contact { padding: 100px 0; background: var(--bg-cream); }
.contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: stretch; }
.contact-info .section-title { margin-bottom: 16px; }
.contact-cards { display: flex; flex-direction: column; gap: 16px; margin-top: 32px; }
.contact-card { display: flex; gap: 18px; align-items: flex-start; background: var(--bg-cream-soft); border: 1px solid var(--lp-border); border-radius: 22px; padding: 22px 24px; }
.contact-card .ico { width: 48px; height: 48px; border-radius: 50%; background: var(--brand-forest); color: var(--brand-gold-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.contact-card h4 { font-size: 15.5px; color: var(--lp-fg1); margin: 0 0 4px; }
.contact-card p, .contact-card a { font-size: 14.5px; color: var(--lp-fg2); margin: 0; }
.contact-card a:hover { color: var(--brand-forest); text-decoration: underline; }
.contact-map { border-radius: 28px; overflow: hidden; border: 1px solid var(--lp-border); background: linear-gradient(135deg, var(--brand-forest-light), var(--bg-cream-soft)); box-shadow: var(--lp-shadow-md); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 48px 40px; position: relative; }
.contact-map .pin { width: 74px; height: 74px; border-radius: 50%; background: var(--brand-forest); color: var(--brand-gold-light); display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: var(--lp-shadow-md); }
.contact-map h3 { font-size: 20px; margin-bottom: 10px; }
.contact-map p { font-size: 14.5px; color: var(--lp-fg2); max-width: 280px; margin: 0 0 26px; }
.contact-map .leaf-deco { position: absolute; top: -8px; left: -8px; width: 110px; opacity: .5; }
.map-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
.btn-on-map { background: var(--bg-cream-soft); }

/* ── CTA strip ── */
.cta-strip { background: var(--brand-forest); padding: 70px 0; position: relative; overflow: hidden; }
.cta-strip-inner { display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap; position: relative; z-index: 1; }
.cta-strip .script { font-size: 24px; margin-bottom: 8px; }
.cta-strip h2 { color: var(--bg-cream-soft); font-size: 32px; line-height: 1.25; max-width: 520px; }
.cta-strip-actions { display: flex; gap: 16px; flex-wrap: wrap; }

/* ── Footer ── */
footer { background: var(--brand-forest); color: #cfd9cf; padding: 60px 0 28px; }
.footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 40px; padding-bottom: 44px; border-bottom: 1px solid rgba(255,255,255,.12); }
.footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.footer-brand img { height: 44px; width: 44px; border-radius: 50%; }
.footer-brand b { color: var(--bg-cream-soft); font-size: 18px; }
.footer-col p { margin: 0; font-size: 14px; color: #b9c4b8; max-width: 280px; }
.footer-col h4 { color: var(--bg-cream-soft); font-size: 14px; letter-spacing: .06em; text-transform: uppercase; margin: 0 0 18px; }
.footer-col ul li { margin-bottom: 12px; }
.footer-col ul a { font-size: 14.5px; color: #cfd9cf; }
.footer-col ul a:hover { color: var(--brand-gold); }
.footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 26px; flex-wrap: wrap; gap: 12px; }
.footer-bottom p { margin: 0; font-size: 13px; color: #93a293; }
.footer-social { display: flex; gap: 12px; }
.footer-social a { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.08); display: flex; align-items: center; justify-content: center; color: #cfd9cf; }
.footer-social a:hover { background: var(--brand-gold); color: var(--brand-forest-dark); }

/* ── Lightbox ── */
.lightbox { position: fixed; inset: 0; background: rgba(18,28,20,.92); display: flex; align-items: center; justify-content: center; padding: 40px; z-index: 10000; opacity: 0; pointer-events: none; transition: opacity .25s ease; }
.lightbox.open { opacity: 1; pointer-events: auto; }
.lightbox img { max-width: min(720px, 90vw); max-height: 86vh; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
.lightbox-close { position: absolute; top: 28px; right: 32px; width: 44px; height: 44px; border-radius: 50%; background: rgba(245,238,228,.15); color: #F5EEE4; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; border: 1px solid rgba(245,238,228,.3); }
.lightbox-close:hover { background: var(--brand-gold); color: var(--brand-forest-dark); }

/* ── Responsive ── */
@media (max-width: 900px) {
    .wrap { padding: 0 24px; }
    nav.links { display: none; }
    nav.links.open { display: flex; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-cream-soft); flex-direction: column; padding: 20px 24px; gap: 18px; border-bottom: 1px solid var(--lp-border); box-shadow: var(--lp-shadow-md); }
    .burger { display: block; }
    .header-actions .staff-btn { display: none; }
    .hero-grid { grid-template-columns: 1fr; padding: 0 24px; }
    .hero-photo { order: -1; max-width: 420px; margin: 0 auto; }
    .hero-copy { text-align: center; padding-bottom: 48px; }
    .hero-copy p { margin-left: auto; margin-right: auto; }
    .hero-ctas { justify-content: center; }
    .hero-stats { margin-left: auto; margin-right: auto; }
    .hero-badge { left: 50%; transform: translateX(-50%); bottom: -24px; }
    .services { padding: 80px 0 70px; }
    .services-grid { grid-template-columns: repeat(2,1fr); }
    .hours-grid { grid-template-columns: 1fr; gap: 40px; }
    .hours-copy { text-align: center; }
    .tip-row, .tip-row.reverse { grid-template-columns: 1fr; gap: 30px; }
    .tip-row .tip-media, .tip-row.reverse .tip-media { order: 1; max-width: 380px; margin: 0 auto; width: 100%; }
    .tip-row .tip-text, .tip-row.reverse .tip-text { order: 2; text-align: center; }
    .tip-text p { margin-left: auto; margin-right: auto; }
    .testi-grid { grid-template-columns: 1fr 1fr; }
    .contact-grid { grid-template-columns: 1fr; }
    .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
    .cta-strip-inner { justify-content: center; text-align: center; }
    .cta-strip h2 { margin: 0 auto; }
}
@media (max-width: 640px) {
    .wrap { padding: 0 20px; }
    .nav-inner { padding: 12px 20px; }
    .brand .name { font-size: 16px; }
    .brand img { height: 38px; width: 38px; }
    .hero { padding: 36px 0 0; }
    .hero-grid { padding: 0 20px; gap: 44px; }
    .hero-copy h1 { font-size: 34px; }
    .hero-copy .script { font-size: 24px; }
    .hero-copy p { font-size: 15.5px; }
    .hero-ctas { flex-direction: column; align-items: stretch; }
    .hero-ctas .btn { width: 100%; }
    .hero-ctas .tel-link { justify-content: center; }
    .hero-stats { flex-direction: column; gap: 16px; align-items: center; }
    .hero-badge { max-width: 200px; padding: 12px 16px; }
    .hero-leaf-tr { width: 90px; }
    .hero-leaf-bl { width: 80px; }
    .section-title { font-size: 27px; }
    .services { padding: 64px 0 56px; }
    .services-head { margin-bottom: 36px; }
    .services-grid { grid-template-columns: 1fr; gap: 18px; }
    .hours { padding: 64px 0; }
    .hours-row { padding: 18px 20px; flex-wrap: wrap; }
    .tips { padding: 64px 0 56px; }
    .tips-head { margin-bottom: 40px; }
    .tip-row { margin-bottom: 52px; }
    .tip-text h3 { font-size: 22px; }
    .testi { padding: 64px 0; }
    .testi-head { margin-bottom: 36px; }
    .testi-grid { grid-template-columns: 1fr; gap: 18px; }
    .contact { padding: 64px 0; }
    .contact-grid { gap: 32px; }
    .contact-map { padding: 36px 24px; }
    .cta-strip { padding: 52px 0; }
    .cta-strip h2 { font-size: 25px; }
    .cta-strip-actions { width: 100%; flex-direction: column; }
    .cta-strip-actions .btn { width: 100%; }
    footer { padding: 48px 0 24px; }
    .footer-grid { grid-template-columns: 1fr; gap: 28px; }
    .footer-bottom { flex-direction: column; align-items: flex-start; }
    .lightbox { padding: 16px; }
}
</style>
