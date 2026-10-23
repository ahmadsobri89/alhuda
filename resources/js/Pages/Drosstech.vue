<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    configured: { type: Boolean, default: false },
    agencyCode: { type: String, default: null },
})

const plate = ref('')
const loading = ref(false)
const result = ref(null)
const error = ref(null)

const prettyPlate = computed(() =>
    plate.value.toUpperCase().replace(/[^A-Z0-9]/g, '')
)

const resultOk = computed(() => result.value?.success === true)

const records = computed(() => {
    const data = result.value?.data
    if (!data) return []
    if (Array.isArray(data)) return data
    if (Array.isArray(data?.Compounds)) return data.Compounds
    if (Array.isArray(data?.data)) return data.data
    return []
})

async function search() {
    const value = prettyPlate.value
    if (!value || loading.value) return

    loading.value = true
    error.value = null
    result.value = null

    try {
        const { data } = await window.axios.get(
            route('drosstech.enquiry'),
            { params: { plate: value }, validateStatus: () => true }
        )
        result.value = data
        if (!data?.success) {
            error.value = data?.message || 'Enquiry gagal.'
        }
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Ralat rangkaian.'
    } finally {
        loading.value = false
    }
}

function fmt(v) {
    if (v === null || v === undefined || v === '') return '—'
    return String(v)
}
</script>

<template>
    <Head title="Drosstech Enquiry" />

    <div class="screen">
        <div class="glow glow-a" />
        <div class="glow glow-b" />

        <main class="shell">
            <header class="head">
                <div class="brand">
                    <span class="dot" />
                    <span>Drosstech · OVRS</span>
                </div>
                <h1>Semakan Kompaun</h1>
                <p class="sub">
                    Masukkan nombor plat kenderaan untuk semak rekod kompaun.
                    <template v-if="agencyCode"> Agensi: <strong>{{ agencyCode }}</strong>.</template>
                </p>
                <div v-if="!configured" class="warn">
                    Credential Drosstech belum ditetapkan dalam <code>.env</code>. Enquiry mungkin gagal.
                </div>
            </header>

            <section class="panel">
                <form class="searchbar" @submit.prevent="search">
                    <div class="plate">
                        <input
                            v-model="plate"
                            type="text"
                            inputmode="text"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="JDT 1234"
                            aria-label="Nombor plat"
                            @keyup.enter="search"
                        />
                    </div>
                    <button type="submit" :disabled="loading || !prettyPlate">
                        <span v-if="!loading">Semak</span>
                        <span v-else class="spin" />
                    </button>
                </form>
                <p class="hint">Tekan Enter atau butang Semak.</p>
            </section>

            <section v-if="loading" class="state">
                <div class="spin big" />
                <p>Menyemak rekod untuk <strong>{{ prettyPlate }}</strong>…</p>
            </section>

            <section v-else-if="result" class="results">
                <div class="statusrow">
                    <span class="badge" :class="resultOk ? 'ok' : 'bad'">
                        {{ resultOk ? 'Berjaya' : 'Gagal' }}
                    </span>
                    <span class="msg">{{ result.message }}</span>
                    <span v-if="result.status" class="code">HTTP {{ result.status }}</span>
                </div>

                <div v-if="error" class="errbox">{{ error }}</div>

                <template v-if="records.length">
                    <div class="count">{{ records.length }} rekod dijumpai</div>
                    <div class="cards">
                        <article v-for="(rec, i) in records" :key="i" class="reccard">
                            <div
                                v-for="(val, key) in rec"
                                :key="key"
                                class="field"
                            >
                                <span class="k">{{ key }}</span>
                                <span class="v">{{ fmt(val) }}</span>
                            </div>
                        </article>
                    </div>
                </template>

                <details class="raw">
                    <summary>Lihat respons mentah (JSON)</summary>
                    <pre>{{ JSON.stringify(result, null, 2) }}</pre>
                </details>
            </section>

            <section v-else class="state idle">
                <p>Tiada carian lagi.</p>
            </section>
        </main>
    </div>
</template>

<style scoped>
.screen {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: radial-gradient(130% 120% at 50% -10%, #142033 0%, #0a0f1a 55%, #070a12 100%);
    color: #e7edf6;
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    padding: 48px 16px 80px;
}
.glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.5;
    pointer-events: none;
}
.glow-a { width: 420px; height: 420px; background: #1d6feb; top: -120px; left: -80px; }
.glow-b { width: 380px; height: 380px; background: #14b8a6; bottom: -140px; right: -60px; opacity: 0.35; }

.shell {
    position: relative;
    max-width: 760px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.head { text-align: center; }
.brand {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase;
    color: #8aa0bd; margin-bottom: 14px;
}
.brand .dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 12px #34d399; }
.head h1 { margin: 0; font-size: 34px; font-weight: 700; letter-spacing: -0.02em; }
.sub { margin: 10px auto 0; max-width: 440px; color: #9fb2cc; font-size: 15px; line-height: 1.5; }
.sub strong { color: #e7edf6; }
.warn {
    margin: 16px auto 0; max-width: 460px;
    background: rgba(234,179,8,0.12); border: 1px solid rgba(234,179,8,0.35);
    color: #fde68a; border-radius: 12px; padding: 10px 14px; font-size: 13px;
}
.warn code { background: rgba(0,0,0,0.3); padding: 1px 5px; border-radius: 5px; }

.panel {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    padding: 22px;
    backdrop-filter: blur(10px);
    box-shadow: 0 24px 60px rgba(0,0,0,0.4);
}
.searchbar { display: flex; gap: 12px; align-items: stretch; }
.plate {
    flex: 1;
    background: linear-gradient(#fefce8, #fde68a);
    border-radius: 12px;
    padding: 4px;
    box-shadow: inset 0 0 0 3px #111;
}
.plate input {
    width: 100%; height: 56px; border: none; outline: none; background: transparent;
    text-align: center; font-size: 26px; font-weight: 800; letter-spacing: 0.14em;
    color: #111; text-transform: uppercase;
}
.plate input::placeholder { color: #a16207; opacity: 0.5; }
.searchbar button {
    min-width: 120px; border: none; border-radius: 12px; cursor: pointer;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff; font-size: 16px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.08s ease, opacity 0.2s ease;
}
.searchbar button:hover:not(:disabled) { transform: translateY(-1px); }
.searchbar button:disabled { opacity: 0.5; cursor: not-allowed; }
.hint { margin: 12px 2px 0; font-size: 12px; color: #6f85a3; }

.state { text-align: center; color: #9fb2cc; padding: 24px; display: flex; flex-direction: column; align-items: center; gap: 14px; }
.state.idle { opacity: 0.6; }

.spin {
    width: 18px; height: 18px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
    animation: rot 0.7s linear infinite; display: inline-block;
}
.spin.big { width: 34px; height: 34px; border-width: 3px; border-color: rgba(255,255,255,0.15); border-top-color: #38bdf8; }
@keyframes rot { to { transform: rotate(360deg); } }

.results { display: flex; flex-direction: column; gap: 16px; }
.statusrow { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.badge { padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; }
.badge.ok { background: rgba(52,211,153,0.15); color: #6ee7b7; border: 1px solid rgba(52,211,153,0.4); }
.badge.bad { background: rgba(248,113,113,0.15); color: #fca5a5; border: 1px solid rgba(248,113,113,0.4); }
.msg { color: #cdd9ea; font-size: 14px; }
.code { margin-left: auto; font-size: 12px; color: #7f93b0; font-family: ui-monospace, monospace; }

.errbox {
    background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3);
    color: #fca5a5; border-radius: 12px; padding: 12px 16px; font-size: 14px;
}
.count { font-size: 13px; color: #8aa0bd; }
.cards { display: grid; gap: 14px; }
.reccard {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px; padding: 16px; display: grid; gap: 8px;
}
.field { display: flex; justify-content: space-between; gap: 16px; font-size: 14px; border-bottom: 1px dashed rgba(255,255,255,0.07); padding-bottom: 8px; }
.field:last-child { border-bottom: none; padding-bottom: 0; }
.field .k { color: #8aa0bd; text-transform: capitalize; }
.field .v { color: #f1f5fb; font-weight: 600; text-align: right; word-break: break-word; }

.raw { background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 4px 14px; }
.raw summary { cursor: pointer; padding: 10px 0; font-size: 13px; color: #9fb2cc; }
.raw pre { margin: 0 0 12px; font-size: 12px; color: #a7f3d0; overflow: auto; font-family: ui-monospace, monospace; line-height: 1.5; }

@media (max-width: 560px) {
    .head h1 { font-size: 26px; }
    .searchbar { flex-direction: column; }
    .searchbar button { min-width: 0; height: 48px; }
    .code { margin-left: 0; width: 100%; }
}
</style>
