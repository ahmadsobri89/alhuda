<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Log Masuk · Poliklinik Al-Huda" />

    <div class="lr">

        <!-- ── Left panel (sticky) ── -->
        <aside class="lr-aside">
            <div class="lr-aside__inner">

                <div class="lr-brand">
                    <img src="/logo.png" alt="" class="lr-brand__logo" />
                    <div class="lr-brand__text">
                        <span class="lr-brand__sub">Poliklinik</span>
                        <span class="lr-brand__name">Al-Huda</span>
                    </div>
                </div>

                <div class="lr-hero">
                    <h1 class="lr-hero__h">Sistem Pengurusan<br>Klinik Bersepadu</h1>
                    <p class="lr-hero__p">Platform klinikal bertaraf tinggi dengan sokongan AI untuk meningkatkan kualiti penjagaan pesakit.</p>
                </div>

                <ul class="lr-feats">
                    <li class="lr-feat">
                        <div class="lr-feat__icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                        </div>
                        <div>
                            <div class="lr-feat__title">Rekod Perubatan Elektronik</div>
                            <div class="lr-feat__desc">SOAP notes, ICD-10, preskripsi digital</div>
                        </div>
                    </li>
                    <li class="lr-feat">
                        <div class="lr-feat__icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </div>
                        <div>
                            <div class="lr-feat__title">Sokongan AI Klinikal</div>
                            <div class="lr-feat__desc">Triage pintar &amp; semakan ubat automatik</div>
                        </div>
                    </li>
                    <li class="lr-feat">
                        <div class="lr-feat__icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </div>
                        <div>
                            <div class="lr-feat__title">Pengurusan Giliran &amp; Temujanji</div>
                            <div class="lr-feat__desc">Queue masa nyata dengan notifikasi pesakit</div>
                        </div>
                    </li>
                    <li class="lr-feat">
                        <div class="lr-feat__icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div class="lr-feat__title">Keselamatan &amp; Pematuhan</div>
                            <div class="lr-feat__desc">MFA, RBAC, log audit &amp; penyulitan data</div>
                        </div>
                    </li>
                </ul>

                <div class="lr-aside__footer">© 2026 Poliklinik Al-Huda · v2.0</div>

            </div>
        </aside>

        <!-- ── Right panel (scrollable) ── -->
        <main class="lr-main">
            <div class="lr-card">

                <div class="lr-card__logo">
                    <img src="/logo.png" alt="" />
                    <span>Poliklinik Al-Huda</span>
                </div>

                <div class="lr-card__head">
                    <h2>Log Masuk</h2>
                    <p>Masukkan kelayakan anda untuk meneruskan</p>
                </div>

                <div v-if="status" class="lr-status">{{ status }}</div>

                <form @submit.prevent="submit" class="lr-form">
                    <div class="lr-field">
                        <label for="email">Emel</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :class="['lr-input', form.errors.email ? 'lr-input--err' : '']"
                            placeholder="doktor@alhuda.my"
                            autocomplete="username"
                            autofocus
                            required
                        />
                        <span v-if="form.errors.email" class="lr-err">{{ form.errors.email }}</span>
                    </div>

                    <div class="lr-field">
                        <div class="lr-field__row">
                            <label for="password">Kata Laluan</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="lr-forgot">
                                Lupa kata laluan?
                            </Link>
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            :class="['lr-input', form.errors.password ? 'lr-input--err' : '']"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        />
                        <span v-if="form.errors.password" class="lr-err">{{ form.errors.password }}</span>
                    </div>

                    <label class="lr-check">
                        <input v-model="form.remember" type="checkbox" />
                        <span>Ingat saya selama 30 hari</span>
                    </label>

                    <button type="submit" class="lr-btn" :disabled="form.processing">
                        <svg v-if="form.processing" class="lr-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/></svg>
                        {{ form.processing ? 'Memproses…' : 'Log Masuk →' }}
                    </button>
                </form>

                <div class="lr-divider"><span>Sistem akses terhad</span></div>

                <div class="lr-roles">
                    <div class="lr-role lr-role--green">Doktor</div>
                    <div class="lr-role lr-role--blue">Jururawat</div>
                    <div class="lr-role lr-role--orange">Farmasi</div>
                    <div class="lr-role lr-role--red">Admin</div>
                </div>

            </div>
        </main>

    </div>
</template>

<style scoped>
/* ── Root ── */
.lr {
    display: flex;
    min-height: 100vh;
    flex: 1;
    font-family: var(--font-sans);
    background: #fff;
}

/* ── Left panel ── */
.lr-aside {
    width: 400px;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    height: 100vh;
    background: var(--brand-green-dark);
    overflow: hidden;
}

/* decorative blobs */
.lr-aside::before,
.lr-aside::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.lr-aside::before {
    width: 340px; height: 340px;
    top: -100px; right: -100px;
    background: rgba(255,255,255,.05);
}
.lr-aside::after {
    width: 260px; height: 260px;
    bottom: -70px; left: -70px;
    background: rgba(255,255,255,.04);
}

.lr-aside__inner {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 32px 36px;
}

/* brand */
.lr-brand {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 44px;
}
.lr-brand__logo {
    width: 38px; height: 38px;
    border-radius: 9px;
    background: rgba(255,255,255,.15);
    padding: 4px;
    object-fit: contain;
}
.lr-brand__text {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
}
.lr-brand__sub  { font: 500 12px var(--font-sans); color: rgba(255,255,255,.6); }
.lr-brand__name { font: 800 16px var(--font-sans); color: #fff; }

/* hero */
.lr-hero { margin-bottom: 36px; }
.lr-hero__h {
    font: 800 24px/1.25 var(--font-sans);
    color: #fff;
    margin: 0 0 12px;
}
.lr-hero__p {
    font: 400 13px/1.65 var(--font-sans);
    color: rgba(255,255,255,.6);
    margin: 0;
}

/* features */
.lr-feats {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
    flex: 1;
}
.lr-feat {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 14px 0;
    border-top: 1px solid rgba(255,255,255,.08);
}
.lr-feat:last-child { border-bottom: 1px solid rgba(255,255,255,.08); }
.lr-feat__icon {
    width: 32px; height: 32px;
    flex-shrink: 0;
    border-radius: 8px;
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.85);
    display: grid; place-items: center;
}
.lr-feat__title {
    font: 600 13px var(--font-sans);
    color: #fff;
    margin-bottom: 2px;
}
.lr-feat__desc {
    font: 400 11.5px var(--font-sans);
    color: rgba(255,255,255,.5);
    line-height: 1.45;
}

.lr-aside__footer {
    margin-top: 28px;
    font: 500 11px var(--font-mono);
    color: rgba(255,255,255,.3);
}

/* ── Right panel ── */
.lr-main {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
    background: #fff;
    overflow-y: auto;
}

/* card */
.lr-card {
    width: 100%;
    max-width: 400px;
}

.lr-card__logo {
    display: none;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
}
.lr-card__logo img {
    width: 32px; height: 32px;
    object-fit: contain;
    border-radius: 7px;
    background: var(--brand-green-light);
    padding: 3px;
}
.lr-card__logo span {
    font: 700 14px var(--font-sans);
    color: var(--brand-green-dark);
}

.lr-card__head { margin-bottom: 28px; }
.lr-card__head h2 {
    font: 800 26px var(--font-sans);
    color: var(--fg1);
    margin: 0 0 5px;
}
.lr-card__head p {
    font: 400 13.5px var(--font-sans);
    color: var(--fg3);
    margin: 0;
}

/* status */
.lr-status {
    padding: 10px 14px;
    background: var(--brand-green-light);
    border: 1px solid var(--brand-green);
    border-radius: 8px;
    font: 500 13px var(--font-sans);
    color: var(--brand-green-dark);
    margin-bottom: 18px;
}

/* form */
.lr-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.lr-field { display: flex; flex-direction: column; gap: 5px; }
.lr-field label {
    font: 600 11.5px var(--font-sans);
    color: var(--fg2);
}
.lr-field__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lr-input {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    font: 400 13.5px var(--font-sans);
    background: #fff;
    color: var(--fg1);
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.lr-input:focus {
    border-color: var(--brand-green);
    box-shadow: 0 0 0 3px rgba(27,138,74,.14);
}
.lr-input--err {
    border-color: var(--brand-red);
    box-shadow: 0 0 0 3px rgba(230,57,70,.1);
}
.lr-err {
    font: 500 11.5px var(--font-sans);
    color: var(--brand-red);
}

.lr-forgot {
    font: 500 12px var(--font-sans);
    color: var(--brand-green);
    text-decoration: none;
}
.lr-forgot:hover { text-decoration: underline; }

.lr-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font: 500 13px var(--font-sans);
    color: var(--fg2);
    cursor: pointer;
    user-select: none;
    margin-top: -2px;
}
.lr-check input {
    width: 15px; height: 15px;
    accent-color: var(--brand-green);
    cursor: pointer;
}

.lr-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: var(--brand-green);
    color: #fff;
    font: 700 14px var(--font-sans);
    border: none;
    border-radius: 9px;
    cursor: pointer;
    transition: background .15s;
    margin-top: 4px;
}
.lr-btn:hover:not(:disabled) { background: var(--brand-green-dark); }
.lr-btn:disabled { opacity: .6; cursor: not-allowed; }

@keyframes spin { to { transform: rotate(360deg); } }
.lr-spin {
    width: 15px; height: 15px;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
}

/* divider */
.lr-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 24px 0 18px;
    font: 500 11px var(--font-sans);
    color: var(--fg3);
    text-transform: uppercase;
    letter-spacing: .06em;
}
.lr-divider::before,
.lr-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

/* role badges */
.lr-roles {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.lr-role {
    padding: 4px 12px;
    border-radius: 999px;
    font: 600 11.5px var(--font-sans);
    border: 1.5px solid transparent;
}
.lr-role--green  { background: var(--brand-green-light);  color: var(--brand-green-dark); border-color: rgba(27,138,74,.2);  }
.lr-role--blue   { background: #DBEAFE; color: #1E40AF; border-color: rgba(37,99,235,.2);  }
.lr-role--orange { background: #FFEDD5; color: #9A3412; border-color: rgba(244,128,31,.2); }
.lr-role--red    { background: #FEE2E2; color: #991B1B; border-color: rgba(230,57,70,.2);  }
</style>
