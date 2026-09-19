<script setup>
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import { useLocale } from '@/composables/useLocale'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  isAdmin:       Boolean,
  period:        String,
  periodLabel:   String,
  summary:       Object,
  byMethod:      Array,
  trend:         Array,
  transactions:  { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0, per_page: 15, last_page: 1 }) },
  selectedDate:  String,
  selectedMonth: Number,
  selectedYear:  Number,
  filterYears:   Array,
  filters:       { type: Object, default: () => ({}) },
})

const page = usePage()
const { t } = useLocale()

/* ── filter state ── */
const fDate  = ref(props.selectedDate)
const fMonth = ref(Number(props.selectedMonth))
const fYear  = ref(Number(props.selectedYear))

const months = computed(() =>
  Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: new Date(2000, i, 1).toLocaleString('ms-MY', { month: 'long' }),
  }))
)

const PER_PAGE_OPTIONS = [15, 30, 50, 100]
const search  = ref(props.filters?.search ?? '')
const perPage = ref(props.transactions?.per_page ?? 15)

function buildParams(period, extra = {}) {
  const data = { period }
  if (period === 'day')   data.date = fDate.value
  if (period === 'month') { data.month = fMonth.value; data.year = fYear.value }
  if (period === 'year')  data.year = fYear.value
  data.search   = search.value || undefined
  data.per_page = perPage.value !== 15 ? perPage.value : undefined
  return { ...data, ...extra }
}

function goFinance(data) {
  router.get('/finance', data, { preserveState: true, preserveScroll: true, replace: true })
}

function setPeriod(p) {
  if (p !== 'day' && !props.isAdmin) return
  goFinance(buildParams(p))
}

function applyFilter() { setPeriod(props.period) }

let searchTimer
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => goFinance(buildParams(props.period)), 350)
})

function setPerPage() { goFinance(buildParams(props.period)) }

function goToPage(url) {
  if (url) router.get(url, buildParams(props.period), { preserveState: true, preserveScroll: true })
}

/* ── audit export (CSV) — ikut penapis semasa ── */
const exportUrl = computed(() => {
  const params = new URLSearchParams({ period: props.period })
  if (props.period === 'day')   params.set('date', fDate.value)
  if (props.period === 'month') { params.set('month', fMonth.value); params.set('year', fYear.value) }
  if (props.period === 'year')  params.set('year', fYear.value)
  return '/finance/export?' + params.toString()
})

/* ── labels & helpers ── */
const methodLabel = computed(() => ({
  cash: t('method_cash'), card: t('method_card'), duitnow: t('method_duitnow'),
  panel: t('method_panel'), insurance: t('method_insurance'),
}))
const methodTone = { cash: 'green', card: 'blue', duitnow: 'yellow', panel: 'orange', insurance: 'neutral' }

function rm(v) {
  return 'RM ' + Number(v || 0).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/* ── graf trend ──────────────────────────────────────────────────────────────
   Satu siri sahaja (kutipan ikut tempoh), jadi satu warna hijau jenama; hanya
   titik tertinggi digelapkan + dilabel terus. Nilai lain dibaca dari paksi-Y,
   tooltip, atau paparan jadual — supaya tiada nombor bertindih atas setiap bar. */
const trendView = ref('chart')   // 'chart' | 'table'
const hoverIdx  = ref(null)      // indeks kolum yang dihover / difokus

const points     = computed(() => props.trend ?? [])
const trendTotal = computed(() => points.value.reduce((s, r) => s + Number(r.value || 0), 0))
const trendTxn   = computed(() => points.value.reduce((s, r) => s + Number(r.count || 0), 0))

// Purata dikira atas tempoh yang ada kutipan sahaja — hari klinik tutup tidak
// sepatutnya menarik garis purata ke bawah dan menipu perbandingan staf.
const activeCount = computed(() => points.value.filter(r => Number(r.value) > 0).length)
const avgValue    = computed(() => (activeCount.value ? trendTotal.value / activeCount.value : 0))

const peakIdx = computed(() => {
  let best = -1
  points.value.forEach((r, i) => {
    if (Number(r.value) > 0 && (best < 0 || Number(r.value) > Number(points.value[best].value))) best = i
  })
  return best
})
const peak = computed(() => (peakIdx.value < 0 ? null : points.value[peakIdx.value]))

/* Skala: bulatkan siling ke nombor kemas (1 / 2 / 2.5 / 5 × 10ⁿ) supaya tick
   paksi-Y mudah dibaca, dan sentiasa tinggalkan sedikit ruang atas bar. */
function niceStep(range) {
  const mag = Math.pow(10, Math.floor(Math.log10(range)))
  const n   = range / mag
  return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * mag
}
const scaleStep = computed(() => {
  const m = Math.max(...points.value.map(r => Number(r.value) || 0), 0)
  return m <= 0 ? 25 : niceStep(m / 4)
})
const scaleMax = computed(() => {
  const m = Math.max(...points.value.map(r => Number(r.value) || 0), 0)
  if (m <= 0) return 100
  let max = Math.ceil(m / scaleStep.value) * scaleStep.value
  if (max <= m) max += scaleStep.value   // jangan biar bar tertinggi cecah siling
  return max
})
// Tick jatuh pada gandaan langkah (0 / 500 / 1000 / 1500), bukan suku siling —
// suku boleh jadi nombor janggal seperti 1,125 yang susah dibaca sekilas.
const ticks = computed(() => {
  const step = scaleStep.value
  const out = []
  for (let v = scaleMax.value; v >= -1e-9; v -= step) out.push(Math.round(v))
  return out
})
const avgPct = computed(() => pctOf(avgValue.value))

function pctOf(v) { return (Math.max(0, Number(v) || 0) / scaleMax.value) * 100 }
// Tempoh tanpa kutipan kekal tunjuk tunggul nipis di garis dasar — supaya staf
// nampak "hari itu sifar", bukan tersilap sangka datanya hilang.
function barStyle(v) { return { height: Number(v) > 0 ? Math.max(2, pctOf(v)) + '%' : '2px' } }

/* Label paksi-X jadi jarang bila titik banyak (cth. 31 hari dalam sebulan). */
const labelEvery = computed(() => Math.max(1, Math.ceil(points.value.length / 11)))
function showLabel(i) {
  return i === 0 || i === points.value.length - 1 || i % labelEvery.value === 0
}

function compact(v) {
  const n = Number(v) || 0
  if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k'
  return String(Math.round(n))
}

const scopeLabel = computed(() => ({
  day: t('fin_scope_day'), month: t('fin_scope_month'), year: t('fin_scope_year'),
}[props.period] ?? ''))

const hoverRow = computed(() => (hoverIdx.value == null ? null : points.value[hoverIdx.value]))
// Beza terhadap purata — itu soalan pertama staf bila tengok satu hari.
const hoverDelta = computed(() => {
  const r = hoverRow.value
  if (!r || !avgValue.value || !Number(r.value)) return null
  return Math.round(((Number(r.value) - avgValue.value) / avgValue.value) * 100)
})
const tipStyle = computed(() => {
  const n = points.value.length || 1
  const pos = ((hoverIdx.value + 0.5) / n) * 100
  return { left: Math.min(86, Math.max(14, pos)) + '%' }   // kekal dalam kad di tepi
})
function colLabel(r) {
  return `${r.sub || r.label}: ${rm(r.value)}, ${r.count} ${t('fin_txn')}`
}
</script>

<template>
  <div class="fin">
    <!-- Header + period toggle -->
    <div class="fin__head">
      <div>
        <h2 class="fin__title">{{ t('fin_title') }}</h2>
        <p class="fin__sub">{{ periodLabel }}</p>
      </div>
      <div class="seg">
        <button :class="['seg__btn', period==='day'   ? 'on':'']" @click="setPeriod('day')">{{ t('fin_daily') }}</button>
        <button v-if="isAdmin" :class="['seg__btn', period==='month' ? 'on':'']" @click="setPeriod('month')">{{ t('fin_monthly') }}</button>
        <button v-if="isAdmin" :class="['seg__btn', period==='year'  ? 'on':'']" @click="setPeriod('year')">{{ t('fin_yearly') }}</button>
      </div>
    </div>

    <!-- Date / Month / Year picker -->
    <div class="fin__filter">
      <template v-if="period==='day'">
        <input type="date" v-model="fDate" class="inp" @change="applyFilter" />
      </template>
      <template v-else-if="period==='month'">
        <select v-model.number="fMonth" class="inp" @change="applyFilter">
          <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <select v-model.number="fYear" class="inp" @change="applyFilter">
          <option v-for="y in filterYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </template>
      <template v-else>
        <select v-model.number="fYear" class="inp" @change="applyFilter">
          <option v-for="y in filterYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </template>

      <a :href="exportUrl" class="btn-export" download>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        {{ t('fin_export') }}
      </a>
    </div>

    <!-- KPI cards -->
    <div class="kpis">
      <div class="kpi">
        <div class="kpi__lbl">{{ t('fin_total_collected') }}</div>
        <div class="kpi__val kpi__val--green">{{ rm(summary.total) }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__lbl">{{ t('fin_transactions') }}</div>
        <div class="kpi__val">{{ summary.count }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__lbl">{{ t('fin_avg') }}</div>
        <div class="kpi__val">{{ rm(summary.avg) }}</div>
      </div>
    </div>

    <div class="grid2">
      <!-- Payment-method breakdown -->
      <div class="card">
        <div class="card__hd">
          <h3 class="card__ttl">{{ t('fin_by_method') }}</h3>
        </div>
        <div class="methods">
          <div v-for="m in byMethod" :key="m.method" class="method">
            <div class="method__top">
              <Badge :tone="methodTone[m.method] || 'neutral'">{{ methodLabel[m.method] || m.method }}</Badge>
              <span class="method__amt">{{ rm(m.total) }}</span>
            </div>
            <div class="method__bar"><div class="method__fill" :style="{ width: m.pct + '%' }" /></div>
            <div class="method__meta">{{ m.count }} {{ t('fin_txn') }} · {{ m.pct }}%</div>
          </div>
        </div>
      </div>

      <!-- Trend chart -->
      <div class="card card--trend">
        <div class="trend__hd">
          <div>
            <h3 class="card__ttl">{{ t('fin_trend') }}</h3>
            <p class="trend__scope">{{ scopeLabel }}</p>
          </div>
          <div class="vsw" role="group" :aria-label="t('fin_trend')">
            <button :class="['vsw__btn', trendView==='chart' ? 'on':'']" :aria-pressed="trendView==='chart'"
                    @click="trendView='chart'">{{ t('fin_view_chart') }}</button>
            <button :class="['vsw__btn', trendView==='table' ? 'on':'']" :aria-pressed="trendView==='table'"
                    @click="trendView='table'">{{ t('fin_view_table') }}</button>
          </div>
        </div>

        <!-- Graf: satu warna, gridline nipis, garis purata, puncak dilabel terus -->
        <div v-if="trendView==='chart'" class="chart" @pointerleave="hoverIdx = null">
          <div class="chart__y">
            <span v-for="tk in ticks" :key="tk" class="chart__yt" :style="{ bottom: pctOf(tk)+'%' }">{{ compact(tk) }}</span>
          </div>

          <div class="plot">
            <div class="plot__grid">
              <i v-for="tk in ticks" :key="tk" class="plot__line" :style="{ bottom: pctOf(tk)+'%' }" />
            </div>

            <div v-if="avgValue > 0" class="plot__avg" :style="{ bottom: avgPct+'%' }" />

            <div class="cols">
              <button
                v-for="(r,i) in points" :key="i"
                :class="['col', i===peakIdx ? 'is-peak':'', hoverIdx===i ? 'on':'']"
                :aria-label="colLabel(r)"
                @pointerenter="hoverIdx = i" @focus="hoverIdx = i" @blur="hoverIdx = null"
              >
                <span class="col__zone" />
                <span :class="['col__bar', Number(r.value) > 0 ? '' : 'is-zero']" :style="barStyle(r.value)">
                  <span v-if="i===peakIdx" class="col__cap">{{ compact(r.value) }}</span>
                </span>
              </button>
            </div>

            <div v-if="trendTotal === 0" class="chart__empty">{{ t('fin_empty') }}</div>

            <div v-if="hoverRow" class="tip" :style="tipStyle">
              <div class="tip__d">{{ hoverRow.sub || hoverRow.label }}</div>
              <div class="tip__v">{{ rm(hoverRow.value) }}</div>
              <div class="tip__m">
                {{ hoverRow.count }} {{ t('fin_txn') }}<template v-if="hoverDelta !== null">
                · <em :class="hoverDelta >= 0 ? 'up':'dn'">{{ hoverDelta >= 0 ? '▲' : '▼' }} {{ Math.abs(hoverDelta) }}% {{ t('fin_vs_avg') }}</em></template>
              </div>
            </div>
          </div>

          <div class="xlbl">
            <span v-for="(r,i) in points" :key="i" :class="['xlbl__i', r.current ? 'is-now':'']">
              <template v-if="showLabel(i)">{{ r.label }}</template>
              <i v-if="r.current" class="xlbl__dot" />
            </span>
          </div>
        </div>

        <!-- Jadual: nilai tepat setiap tempoh (rujukan + pembaca skrin) -->
        <div v-else class="ttbl">
          <div class="ttbl__hd">
            <span>{{ t('fin_col_period') }}</span><span>{{ t('fin_txn') }}</span><span>{{ t('fin_col_amount') }}</span>
          </div>
          <div class="ttbl__body">
            <div v-for="(r,i) in points" :key="i" :class="['ttbl__row', i===peakIdx ? 'is-peak':'']">
              <span>{{ r.sub || r.label }}<i v-if="r.current" class="tag-now">{{ t('fin_today') }}</i></span>
              <span class="mono">{{ r.count }}</span>
              <span class="mono ttbl__amt">{{ rm(r.value) }}</span>
            </div>
          </div>
        </div>

        <div class="trend__ft">
          <span v-if="avgValue > 0" class="trend__key"><i class="trend__key-dash" />{{ t('fin_avg_line') }} {{ rm(avgValue) }}</span>
          <span v-if="peak">{{ t('fin_peak') }}: <b>{{ peak.sub || peak.label }}</b> · {{ rm(peak.value) }}</span>
          <span class="trend__ft-tot">{{ trendTxn }} {{ t('fin_txn') }} · {{ rm(trendTotal) }}</span>
        </div>
      </div>
    </div>

    <!-- Transactions -->
    <div class="card">
      <div class="card__hd">
        <h3 class="card__ttl">{{ t('fin_txn_list') }}</h3>
        <div style="margin-left:auto;display:flex;align-items:center;gap:12px">
          <input v-model="search" class="inp" style="width:220px" :placeholder="t('fin_search')" />
          <span class="card__sub">{{ transactions.total }} {{ t('fin_txn') }}</span>
        </div>
      </div>
      <div class="tbl-scroll">
      <div class="tbl__hd">
        <div>{{ t('fin_col_no') }}</div><div>{{ t('fin_col_invoice') }}</div><div>{{ t('fin_col_patient') }}</div>
        <div>{{ t('fin_col_method') }}</div><div style="text-align:right">{{ t('fin_col_amount') }}</div>
        <div>{{ t('fin_col_time') }}</div><div>{{ t('fin_col_by') }}</div>
      </div>
      <div v-if="!transactions.data.length" class="empty">{{ t('fin_empty') }}</div>
      <div v-for="(x, idx) in transactions.data" :key="x.id" class="tbl__row">
        <div class="mono" style="color:var(--fg3)">{{ transactions.from + idx }}</div>
        <div class="mono">{{ x.invoice_number }}</div>
        <div>{{ x.patient_name }}</div>
        <div><Badge :tone="methodTone[x.payment_method] || 'neutral'">{{ methodLabel[x.payment_method] || x.payment_method }}</Badge></div>
        <div style="text-align:right;font:700 13px var(--font-mono)">{{ rm(x.total_amount) }}</div>
        <div class="mono" style="color:var(--fg3)">{{ x.paid_at }}</div>
        <div style="color:var(--fg3)">{{ x.paid_by }}</div>
      </div>
      </div>

      <div v-if="transactions.data.length" class="pagination">
        <div class="pagination__info">
          {{ t('pg_show') }}
          <select v-model.number="perPage" @change="setPerPage" class="per-page-select">
            <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
          </select>
          / {{ t('pg_per_page') }} · {{ transactions.from }}–{{ transactions.to }} {{ t('pg_of') }} {{ transactions.total }}
        </div>
        <div v-if="transactions.last_page > 1" class="pagination__pages">
          <button
            v-for="link in transactions.links" :key="link.label"
            :disabled="!link.url"
            :class="['page-btn', link.active ? 'active':'']"
            @click="goToPage(link.url)"
            v-html="link.label"
          ></button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fin { padding: 20px; display: flex; flex-direction: column; gap: 16px; height: calc(100vh - 56px); overflow-y: auto; }
.fin__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.fin__title { font: 800 20px var(--font-sans); color: var(--fg1); }
.fin__sub { font: 500 13px var(--font-sans); color: var(--fg3); margin-top: 2px; text-transform: capitalize; }

.seg { display: inline-flex; background: var(--bg-muted); border: 1px solid var(--border); border-radius: 9px; padding: 3px; gap: 2px; }
.seg__btn { border: none; background: transparent; padding: 7px 16px; border-radius: 7px; font: 600 12.5px var(--font-sans); color: var(--fg2); cursor: pointer; }
.seg__btn.on { background: var(--brand-green); color: #fff; }

.fin__filter { display: flex; gap: 8px; align-items: center; }
.inp { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font: 500 13px var(--font-sans); background: #fff; color: var(--fg1); }
.btn-export {
  margin-left: auto; display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 16px; border-radius: 8px; border: 1px solid var(--brand-green);
  background: var(--brand-green-light); color: var(--brand-green-dark);
  font: 700 12.5px var(--font-sans); text-decoration: none; cursor: pointer;
  transition: all .12s;
}
.btn-export:hover { background: var(--brand-green); color: #fff; }

.kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.kpi { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm); }
.kpi__lbl { font: 600 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; }
.kpi__val { font: 800 26px var(--font-mono); color: var(--fg1); margin-top: 8px; line-height: 1; }
.kpi__val--green { color: var(--brand-green); }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm); }
.card__hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card__ttl { font: 700 14px var(--font-sans); color: var(--fg1); }
.card__sub { font: 700 13px var(--font-mono); color: var(--brand-green); }

.methods { display: flex; flex-direction: column; gap: 14px; }
.method__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.method__amt { font: 700 13px var(--font-mono); color: var(--fg1); }
.method__bar { height: 7px; background: var(--bg-muted); border-radius: 99px; overflow: hidden; }
.method__fill { height: 100%; background: var(--brand-green); border-radius: 99px; transition: width .3s; }
.method__meta { font: 500 11px var(--font-sans); color: var(--fg3); margin-top: 4px; }

/* ── Graf trend ──
   Satu siri → satu hijau (#3FA46F, 3.03:1 atas putih); puncak guna step gelap
   yang sama rampa (#0F6938) + label terus, jadi penekanan tak bergantung warna. */
.card--trend { --tr-bar: #3FA46F; --tr-peak: #0F6938; display: flex; flex-direction: column; }
.trend__hd { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.trend__scope { font: 500 11.5px var(--font-sans); color: var(--fg3); margin: 3px 0 0; }

.vsw { display: inline-flex; background: var(--bg-muted); border: 1px solid var(--border); border-radius: 8px; padding: 2px; gap: 2px; flex-shrink: 0; }
.vsw__btn { border: none; background: transparent; padding: 5px 11px; border-radius: 6px; font: 600 11.5px var(--font-sans); color: var(--fg3); cursor: pointer; }
.vsw__btn.on { background: #fff; color: var(--fg1); box-shadow: var(--shadow-sm); }

.chart { display: grid; grid-template-columns: 40px 1fr; column-gap: 8px; margin-top: 22px; }
.chart__y, .plot { height: 168px; position: relative; }
.chart__yt { position: absolute; right: 0; transform: translateY(50%); font: 500 10px var(--font-mono); color: var(--fg3); font-variant-numeric: tabular-nums; }

.plot__grid { position: absolute; inset: 0; pointer-events: none; }
.plot__line { position: absolute; left: 0; right: 0; height: 1px; background: var(--border); }
.plot__avg { position: absolute; left: 0; right: 0; border-top: 1px dashed #94A3B8; pointer-events: none; z-index: 2; }

.cols { position: absolute; inset: 0; display: flex; align-items: flex-end; gap: 2px; }
.col { position: relative; flex: 1; height: 100%; padding: 0; border: none; background: transparent; cursor: pointer; }
.col__zone { position: absolute; inset: 0; border-radius: 5px; transition: background .12s; }
.col.on .col__zone { background: rgba(15,23,42,.055); }
/* Ditambat mutlak ke garis dasar: tinggi % dalam <button> flex tak boleh
   dipercayai (Chrome pusatkan kandungan dalam kotak anonim butang). */
.col__bar { position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
  width: 100%; max-width: 24px; background: var(--tr-bar);
  border-radius: 4px 4px 0 0; transition: height .28s ease, background .12s; }
.col__bar.is-zero { background: var(--border); border-radius: 2px; }
.col.is-peak .col__bar, .col.on .col__bar:not(.is-zero) { background: var(--tr-peak); }
.col__cap { position: absolute; bottom: calc(100% + 5px); left: 50%; transform: translateX(-50%);
  font: 700 10px var(--font-mono); color: var(--fg2); white-space: nowrap; }
.col:focus-visible { outline: 2px solid var(--brand-green); outline-offset: 1px; border-radius: 5px; }

.xlbl { grid-column: 2; display: flex; gap: 2px; margin-top: 7px; }
.xlbl__i { flex: 1; min-width: 0; text-align: center; font: 500 10px var(--font-sans); color: var(--fg3); white-space: nowrap; }
.xlbl__i.is-now { color: var(--brand-green-dark); font-weight: 700; }
.xlbl__dot { display: block; width: 4px; height: 4px; border-radius: 99px; background: var(--brand-green); margin: 3px auto 0; }

.tip { position: absolute; top: -6px; transform: translateX(-50%); z-index: 4; min-width: 104px;
  background: var(--fg1); color: #fff; border-radius: 9px; padding: 8px 11px; box-shadow: var(--shadow-md); pointer-events: none; }
.tip__d { font: 600 10px var(--font-sans); color: #CBD5E1; }
.tip__v { font: 700 14px var(--font-mono); margin-top: 1px; }
.tip__m { font: 500 10px var(--font-sans); color: #CBD5E1; margin-top: 3px; }
.tip__m em { font-style: normal; }
.tip__m em.up { color: #6EE7A8; }
.tip__m em.dn { color: #FCA5A5; }

.chart__empty { position: absolute; inset: 0; display: grid; place-items: center;
  font: 500 12px var(--font-sans); color: var(--fg3); pointer-events: none; }

.ttbl { margin-top: 14px; }
.ttbl__hd, .ttbl__row { display: grid; grid-template-columns: 1fr 64px 1fr; gap: 8px; align-items: center; }
.ttbl__hd { padding: 0 2px 7px; border-bottom: 1px solid var(--border); font: 600 10px var(--font-sans);
  color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; }
.ttbl__hd span:not(:first-child), .ttbl__row span:not(:first-child) { text-align: right; }
.ttbl__body { max-height: 196px; overflow-y: auto; }
.ttbl__row { padding: 7px 2px; border-bottom: 1px solid var(--border); font: 500 12px var(--font-sans); color: var(--fg2); }
.ttbl__row:last-child { border-bottom: none; }
.ttbl__row.is-peak { color: var(--fg1); font-weight: 700; }
.ttbl__amt { font-weight: 700; color: var(--fg1); font-variant-numeric: tabular-nums; }
.tag-now { font-style: normal; margin-left: 6px; padding: 1px 6px; border-radius: 99px;
  background: var(--brand-green-light); color: var(--brand-green-dark); font: 700 9px var(--font-sans); }

.trend__ft { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
  margin-top: 14px; padding-top: 11px; border-top: 1px solid var(--border);
  font: 500 11.5px var(--font-sans); color: var(--fg3); }
.trend__ft b { color: var(--fg1); }
.trend__key { display: inline-flex; align-items: center; gap: 6px; }
.trend__key-dash { width: 16px; height: 0; border-top: 1px dashed #94A3B8; }
.trend__ft-tot { margin-left: auto; font: 700 12px var(--font-mono); color: var(--brand-green-dark); }

.tbl-scroll { overflow-x: auto; }
.tbl__hd, .tbl__row { display: grid; grid-template-columns: 44px 1.2fr 1.6fr 1fr 1.1fr 1.2fr 1fr; gap: 10px; align-items: center; min-width: 640px; }
.tbl__hd { padding: 8px 4px; border-bottom: 1px solid var(--border); font: 600 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; }
.tbl__row { padding: 10px 4px; border-bottom: 1px solid var(--border); font: 500 13px var(--font-sans); color: var(--fg1); }
.tbl__row:last-child { border-bottom: none; }
.tbl__row:hover { background: var(--bg-soft); }
.mono { font-family: var(--font-mono); font-size: 12px; }
.empty { padding: 28px; text-align: center; color: var(--fg3); font: 500 13px var(--font-sans); }

@media (max-width: 900px) {
  .grid2 { grid-template-columns: 1fr; }
  .kpis { grid-template-columns: 1fr; }
}

@media (max-width: 560px) {
  .card__hd { flex-wrap: wrap; gap: 8px; }
  .card__hd .inp { width: 100% !important; }
}
</style>
