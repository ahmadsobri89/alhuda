<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useLocale } from '@/composables/useLocale'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  period:      String,
  periodLabel: String,
  kpi:         Object,
  byMethod:    Array,
  monthlyRev:  Array,
  dailyRev:    Array,
  byType:      Array,
  byStatus:    Array,
  topIcd:      Array,
  rxSummary:   Array,
  invTotal:    Number,
  invLow:      Array,
  invValue:    Number,
})

const { t } = useLocale()

/* ── period switch ── */
function setPeriod(p) {
  router.get('/reports', { period: p }, { preserveState: true, replace: true })
}

/* ── labels ── */
const methodLabel = computed(() => ({
  cash: t('method_cash'), card: t('method_card'), duitnow: t('method_duitnow'),
  panel: t('method_panel'), insurance: t('method_insurance'),
}))
const methodColor = { cash:'var(--brand-green)', card:'#2563EB', duitnow:'#D97706', panel:'#7C3AED', insurance:'#0891B2' }
const typeLabel = computed(() => ({
  new: t('type_new'), follow_up: t('type_follow_up'), annual_checkup: t('type_annual_checkup'),
  procedure: t('type_procedure'), antenatal: t('type_antenatal'), teleconsult: t('type_teleconsult'),
}))
const statusTone  = { done:'green', confirmed:'neutral', waiting:'yellow', in_room:'green', no_show:'neutral', cancelled:'red' }
const statusLabel = computed(() => ({
  done: t('status_done'), confirmed: t('queue_legend_conf'), waiting: t('status_waiting'),
  in_room: t('status_in_room'), no_show: t('status_no_show'), cancelled: t('status_cancelled'),
}))
const rxTone  = { pending:'yellow', verifying:'blue', ready:'green', dispensed:'neutral' }
const rxLabel = computed(() => ({
  pending: t('status_pending'), verifying: t('status_verifying'),
  ready: t('status_ready'), dispensed: t('status_dispensed'),
}))

/* ── diff badge ── */
function diffLabel(diff) {
  if (diff === null || diff === undefined) return null
  return (diff >= 0 ? '+' : '') + diff + '%'
}
function diffTone(diff) {
  return diff >= 0 ? 'green' : 'red'
}

/* ── bar chart helpers ── */
const maxMonthly  = computed(() => Math.max(...props.monthlyRev.map(r => r.value), 1))
const maxMethod   = computed(() => Math.max(...props.byMethod.map(r => r.total), 1))
const maxIcd      = computed(() => Math.max(...props.topIcd.map(r => r.count), 1))
const maxType     = computed(() => Math.max(...props.byType.map(r => r.count), 1))
const maxDaily    = computed(() => Math.max(...props.dailyRev.map(r => r.value), 1))

function barPct(val, max) { return Math.max(2, Math.round((val / max) * 100)) + '%' }
function barH(val, max)   { return Math.max(3, Math.round((val / max) * 80)) + 'px' }

/* ── revenue fmt ── */
function fmtRM(v) { return 'RM ' + Number(v).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
</script>

<template>
  <div class="rep-root">

    <!-- ── toolbar ── -->
    <div class="toolbar">
      <div class="period-tabs">
        <button class="ptab" :class="{ active: period === 'month' }"       @click="setPeriod('month')">{{ t('rep_period_month') }}</button>
        <button class="ptab" :class="{ active: period === 'last_month' }"  @click="setPeriod('last_month')">{{ t('rep_period_last_month') }}</button>
        <button class="ptab" :class="{ active: period === 'year' }"        @click="setPeriod('year')">{{ t('rep_period_year') }}</button>
      </div>
      <span class="period-label">{{ periodLabel }}</span>
    </div>

    <!-- ── KPI row ── -->
    <div class="kpi-row">

      <div class="kpi-card">
        <div class="kpi-card__label">{{ t('rep_kpi_revenue') }}</div>
        <div class="kpi-card__val kpi-card__val--green">{{ fmtRM(kpi.revenue) }}</div>
        <div class="kpi-card__sub">
          <span v-if="kpi.revenue_diff !== null">
            <Badge :tone="diffTone(kpi.revenue_diff)" size="xs">{{ diffLabel(kpi.revenue_diff) }}</Badge>
            {{ t('rep_vs_prev') }}
          </span>
          <span v-else>{{ t('rep_no_prev') }}</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">{{ t('rep_kpi_patients') }}</div>
        <div class="kpi-card__val">{{ kpi.patients }}</div>
        <div class="kpi-card__sub">
          <span v-if="kpi.patients_prev > 0">{{ t('rep_before', { n: kpi.patients_prev }) }}</span>
          <span v-else>{{ t('rep_new_patients_sub', { n: kpi.new_patients }) }}</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">{{ t('rep_kpi_appts') }}</div>
        <div class="kpi-card__val">{{ kpi.appt_total }}</div>
        <div class="kpi-card__sub">
          {{ kpi.appt_done }} {{ t('dash_done') }} ·
          <span style="color:#DC2626">{{ kpi.appt_no_show }} {{ t('status_no_show') }}</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">{{ t('rep_kpi_registered') }}</div>
        <div class="kpi-card__val">{{ kpi.total_patients }}</div>
        <div class="kpi-card__sub">{{ t('rep_new_period', { n: kpi.new_patients }) }}</div>
      </div>

    </div>

    <!-- ── row 1: monthly trend + daily revenue ── -->
    <div class="grid-2">

      <!-- Monthly revenue trend -->
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">{{ t('rep_trend') }}</h3>
          <p class="card__sub" style="margin-left:auto">{{ t('rep_12_months') }}</p>
        </div>
        <div class="monthly-chart">
          <div v-for="r in monthlyRev" :key="r.label + r.year" class="month-col">
            <div class="month-val" v-if="r.value > 0">{{ Math.round(r.value) }}</div>
            <div class="month-val" v-else style="opacity:0">0</div>
            <div class="month-bar-wrap">
              <div
                class="month-bar"
                :class="{ 'month-bar--cur': r.month === new Date().getMonth()+1 && r.year === new Date().getFullYear() }"
                :style="{ height: barH(r.value, maxMonthly) }"
              />
            </div>
            <div class="month-lbl">{{ r.label }}</div>
          </div>
        </div>
      </div>

      <!-- Revenue by payment method -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">{{ t('rep_by_method') }}</h3></div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:14px">
          <div v-if="byMethod.length" v-for="m in byMethod" :key="m.method" class="hbar-row">
            <div class="hbar-label">{{ methodLabel[m.method] ?? m.method }}</div>
            <div class="hbar-track">
              <div class="hbar-fill" :style="{ width: barPct(m.total, maxMethod), background: methodColor[m.method] ?? '#94A3B8' }" />
            </div>
            <div class="hbar-val">{{ fmtRM(m.total) }}</div>
          </div>
          <p v-else style="color:var(--fg3);font:400 12px var(--font-sans)">{{ t('rep_no_payments') }}</p>
        </div>
      </div>

    </div>

    <!-- ── row 2: daily + appt type + appt status ── -->
    <div class="grid-3">

      <!-- Daily revenue -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">{{ t('rep_daily') }}</h3></div>
        <div v-if="dailyRev.length" class="daily-chart">
          <div v-for="r in dailyRev" :key="r.date" class="daily-col">
            <div class="daily-val">{{ fmtRM(r.value).replace('RM ','') }}</div>
            <div class="daily-bar-wrap">
              <div class="daily-bar" :style="{ height: barH(r.value, maxDaily) }" />
            </div>
            <div class="daily-lbl">{{ r.label }}</div>
          </div>
        </div>
        <p v-else class="empty-section">{{ t('rep_no_daily') }}</p>
      </div>

      <!-- Appointments by type -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">{{ t('rep_by_type') }}</h3></div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:12px">
          <div v-if="byType.length" v-for="tp in byType" :key="tp.type" class="hbar-row">
            <div class="hbar-label">{{ typeLabel[tp.type] ?? tp.type }}</div>
            <div class="hbar-track">
              <div class="hbar-fill" :style="{ width: barPct(tp.count, maxType) }" />
            </div>
            <div class="hbar-val hbar-val--sm">{{ tp.count }}</div>
          </div>
          <p v-else style="color:var(--fg3);font:400 12px var(--font-sans)">{{ t('rep_no_data') }}</p>
        </div>
      </div>

      <!-- Appointments by status -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">{{ t('rep_by_status') }}</h3></div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
          <div v-if="byStatus.length" v-for="s in byStatus" :key="s.status" class="status-row">
            <Badge :tone="statusTone[s.status] ?? 'neutral'" size="xs">{{ statusLabel[s.status] ?? s.status }}</Badge>
            <div class="status-bar-wrap">
              <div class="status-bar" :style="{ width: barPct(s.count, Math.max(...byStatus.map(x=>x.count))) }" />
            </div>
            <span class="hbar-val hbar-val--sm">{{ s.count }}</span>
          </div>
          <p v-else style="color:var(--fg3);font:400 12px var(--font-sans)">{{ t('rep_no_data') }}</p>
        </div>
      </div>

    </div>

    <!-- ── row 3: ICD-10 + pharmacy + inventory ── -->
    <div class="grid-2">

      <!-- Top ICD-10 -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">{{ t('rep_top_dx') }}</h3></div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:12px">
          <div v-if="topIcd.length" v-for="(d, i) in topIcd" :key="d.code" class="icd-row">
            <span class="icd-rank">{{ i + 1 }}</span>
            <div class="icd-info">
              <div class="icd-code">{{ d.code }}</div>
              <div class="icd-desc">{{ d.description }}</div>
            </div>
            <div class="hbar-track" style="flex:1;max-width:120px">
              <div class="hbar-fill" :style="{ width: barPct(d.count, maxIcd) }" />
            </div>
            <div class="hbar-val hbar-val--sm">{{ d.count }}</div>
          </div>
          <p v-else class="empty-section">{{ t('rep_no_dx') }}</p>
        </div>
      </div>

      <!-- Right: pharmacy + inventory -->
      <div style="display:flex;flex-direction:column;gap:14px">

        <!-- Pharmacy summary -->
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">{{ t('rep_pharmacy') }}</h3>
            <Btn variant="ghost" size="sm" @click="router.visit('/pharmacy')">{{ t('rep_all') }}</Btn>
          </div>
          <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
            <div v-if="rxSummary.length" v-for="r in rxSummary" :key="r.status" class="status-row">
              <Badge :tone="rxTone[r.status] ?? 'neutral'" size="xs">{{ rxLabel[r.status] ?? r.status }}</Badge>
              <div class="status-bar-wrap">
                <div class="status-bar" :style="{ width: barPct(r.count, Math.max(...rxSummary.map(x=>x.count))) }" />
              </div>
              <span class="hbar-val hbar-val--sm">{{ r.count }}</span>
            </div>
            <p v-else class="empty-section">{{ t('rep_no_rx') }}</p>
          </div>
        </div>

        <!-- Inventory summary -->
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">{{ t('rep_inventory') }}</h3>
            <Btn variant="ghost" size="sm" @click="router.visit('/inventory')">{{ t('rep_manage') }}</Btn>
          </div>
          <div class="card__body" style="display:flex;flex-direction:column;gap:12px">

            <div class="inv-stats">
              <div class="inv-stat">
                <div class="inv-stat__val">{{ invTotal }}</div>
                <div class="inv-stat__lbl">{{ t('rep_inv_total') }}</div>
              </div>
              <div class="inv-stat">
                <div class="inv-stat__val" :style="invLow.length ? 'color:#DC2626' : 'color:var(--brand-green)'">{{ invLow.length }}</div>
                <div class="inv-stat__lbl">{{ t('rep_inv_low') }}</div>
              </div>
              <div class="inv-stat">
                <div class="inv-stat__val inv-stat__val--mono">RM {{ Number(invValue).toFixed(0) }}</div>
                <div class="inv-stat__lbl">{{ t('rep_inv_value') }}</div>
              </div>
            </div>

            <div v-if="invLow.length">
              <div class="inv-low-title">{{ t('rep_inv_restock') }}</div>
              <div v-for="item in invLow" :key="item.name" class="inv-low-row">
                <span class="inv-low-dot">⚠</span>
                <span class="inv-low-name">{{ item.name }}</span>
                <span class="inv-low-stock">{{ item.stock_quantity }} / {{ item.reorder_level }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.rep-root {
  padding: 20px 24px;
  overflow-y: auto;
  height: calc(100vh - 56px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: var(--bg-soft);
}

/* toolbar */
.toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
}
.period-tabs {
  display: flex;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
}
.ptab {
  font: 500 12px var(--font-sans);
  padding: 6px 16px;
  background: none;
  border: none;
  color: var(--fg3);
  cursor: pointer;
  border-right: 1px solid var(--border);
  transition: all .12s;
}
.ptab:last-child { border-right: none; }
.ptab:hover  { background: var(--bg-soft); color: var(--fg1); }
.ptab.active { background: var(--brand-green); color: #fff; }
.period-label { font: 600 12px var(--font-sans); color: var(--fg2); }

/* KPI row */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.kpi-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px 18px;
}
.kpi-card__label { font: 500 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; }
.kpi-card__val   { font: 800 24px var(--font-mono); color: var(--fg1); margin: 6px 0 6px; line-height: 1.1; }
.kpi-card__val--green { color: var(--brand-green); }
.kpi-card__sub   { font: 400 11.5px var(--font-sans); color: var(--fg3); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

/* grids */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; align-items: start; }

/* monthly chart */
.monthly-chart {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  padding: 8px 16px 14px;
  height: 130px;
}
.month-col  { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; height: 100%; justify-content: flex-end; }
.month-val  { font: 600 8px var(--font-mono); color: var(--fg3); min-height: 10px; white-space: nowrap; }
.month-bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; }
.month-bar  { width: 100%; border-radius: 4px 4px 0 0; background: #D1FAE5; border: 1px solid #A7F3D0; min-height: 3px; }
.month-bar--cur { background: var(--brand-green); border-color: var(--brand-green); }
.month-lbl  { font: 500 9px var(--font-sans); color: var(--fg3); }

/* daily chart */
.daily-chart {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  padding: 8px 16px 14px;
  height: 130px;
  overflow-x: auto;
}
.daily-col     { flex-shrink: 0; width: 32px; display: flex; flex-direction: column; align-items: center; gap: 3px; height: 100%; justify-content: flex-end; }
.daily-val     { font: 600 7.5px var(--font-mono); color: var(--fg3); min-height: 10px; white-space: nowrap; transform: rotate(-45deg); transform-origin: center; }
.daily-bar-wrap{ flex: 1; width: 100%; display: flex; align-items: flex-end; }
.daily-bar     { width: 100%; border-radius: 4px 4px 0 0; background: var(--brand-green); min-height: 3px; }
.daily-lbl     { font: 500 9px var(--font-sans); color: var(--fg3); }

/* horizontal bars */
.hbar-row   { display: flex; align-items: center; gap: 10px; }
.hbar-label { font: 500 12px var(--font-sans); color: var(--fg2); width: 110px; flex-shrink: 0; }
.hbar-track { flex: 1; height: 10px; background: var(--border); border-radius: 99px; overflow: hidden; }
.hbar-fill  { height: 100%; background: var(--brand-green); border-radius: 99px; transition: width .4s; }
.hbar-val   { font: 700 12px var(--font-mono); color: var(--fg1); width: 90px; text-align: right; flex-shrink: 0; }
.hbar-val--sm { width: 32px; }

/* status rows */
.status-row     { display: flex; align-items: center; gap: 10px; }
.status-bar-wrap { flex: 1; height: 8px; background: var(--border); border-radius: 99px; overflow: hidden; }
.status-bar     { height: 100%; background: var(--brand-green); border-radius: 99px; transition: width .4s; }

/* ICD rows */
.icd-row  { display: flex; align-items: center; gap: 10px; }
.icd-rank { font: 700 12px var(--font-mono); color: var(--fg3); width: 18px; text-align: center; flex-shrink: 0; }
.icd-info { flex: 1; min-width: 0; }
.icd-code { font: 700 11px var(--font-mono); color: var(--brand-green); }
.icd-desc { font: 400 11.5px var(--font-sans); color: var(--fg2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* inventory */
.inv-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.inv-stat  { text-align: center; padding: 10px; background: var(--bg-soft); border-radius: 8px; }
.inv-stat__val      { font: 800 18px var(--font-mono); color: var(--fg1); }
.inv-stat__val--mono { font-size: 14px; }
.inv-stat__lbl      { font: 400 11px var(--font-sans); color: var(--fg3); margin-top: 2px; }

.inv-low-title { font: 600 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.inv-low-row   { display: flex; align-items: center; gap: 6px; padding: 5px 0; border-top: 1px solid var(--border); }
.inv-low-dot   { font-size: 12px; color: #DC2626; flex-shrink: 0; }
.inv-low-name  { flex: 1; font: 500 12px var(--font-sans); color: var(--fg2); }
.inv-low-stock { font: 600 11px var(--font-mono); color: #DC2626; }

.empty-section {
  padding: 16px;
  text-align: center;
  color: var(--fg3);
  font: 400 12px var(--font-sans);
}
</style>
