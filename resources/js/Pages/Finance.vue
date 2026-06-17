<script setup>
import { computed, ref } from 'vue'
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
  transactions:  Array,
  selectedDate:  String,
  selectedMonth: Number,
  selectedYear:  Number,
  filterYears:   Array,
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

function setPeriod(p) {
  if (p !== 'day' && !props.isAdmin) return
  const data = { period: p }
  if (p === 'day')   data.date = fDate.value
  if (p === 'month') { data.month = fMonth.value; data.year = fYear.value }
  if (p === 'year')  data.year = fYear.value
  router.visit('/finance', { data, preserveScroll: true })
}

function applyFilter() { setPeriod(props.period) }

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

/* ── bar chart ── */
const maxTrend = computed(() => Math.max(...props.trend.map(r => r.value), 1))
function barH(v) { return Math.max(3, Math.round((v / maxTrend.value) * 90)) + 'px' }
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
      <div class="card">
        <div class="card__hd">
          <h3 class="card__ttl">{{ t('fin_trend') }}</h3>
          <span class="card__sub">{{ rm(trend.reduce((s,r)=>s+r.value,0)) }}</span>
        </div>
        <div class="chart">
          <div v-for="(r,i) in trend" :key="i" class="col">
            <div class="col__val">{{ r.value > 0 ? Number(r.value).toFixed(0) : '' }}</div>
            <div class="col__wrap"><div class="col__bar" :style="{ height: barH(r.value) }" /></div>
            <div class="col__lbl">{{ r.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions -->
    <div class="card">
      <div class="card__hd">
        <h3 class="card__ttl">{{ t('fin_txn_list') }}</h3>
        <span class="card__sub">{{ transactions.length }} {{ t('fin_txn') }}</span>
      </div>
      <div class="tbl__hd">
        <div>{{ t('fin_col_invoice') }}</div><div>{{ t('fin_col_patient') }}</div>
        <div>{{ t('fin_col_method') }}</div><div style="text-align:right">{{ t('fin_col_amount') }}</div>
        <div>{{ t('fin_col_time') }}</div><div>{{ t('fin_col_by') }}</div>
      </div>
      <div v-if="!transactions.length" class="empty">{{ t('fin_empty') }}</div>
      <div v-for="x in transactions" :key="x.id" class="tbl__row">
        <div class="mono">{{ x.invoice_number }}</div>
        <div>{{ x.patient_name }}</div>
        <div><Badge :tone="methodTone[x.payment_method] || 'neutral'">{{ methodLabel[x.payment_method] || x.payment_method }}</Badge></div>
        <div style="text-align:right;font:700 13px var(--font-mono)">{{ rm(x.total_amount) }}</div>
        <div class="mono" style="color:var(--fg3)">{{ x.paid_at }}</div>
        <div style="color:var(--fg3)">{{ x.paid_by }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fin { padding: 20px; display: flex; flex-direction: column; gap: 16px; }
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

.chart { display: flex; align-items: flex-end; gap: 4px; height: 130px; }
.col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px; height: 100%; }
.col__val { font: 600 9px var(--font-mono); color: var(--fg3); }
.col__wrap { display: flex; align-items: flex-end; height: 90px; }
.col__bar { width: 70%; min-width: 8px; max-width: 26px; background: var(--brand-green-light); border: 1px solid #A7F3D0; border-radius: 4px 4px 0 0; transition: height .3s; }
.col__lbl { font: 500 10px var(--font-sans); color: var(--fg3); white-space: nowrap; }

.tbl__hd, .tbl__row { display: grid; grid-template-columns: 1.2fr 1.6fr 1fr 1.1fr 1.2fr 1fr; gap: 10px; align-items: center; }
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
</style>
