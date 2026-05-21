<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  kpi:            Object,
  upcoming:       Array,
  recentVisits:   Array,
  alerts:         Array,
  revChart:       Array,
  recentInvoices: Array,
  userName:       String,
  today:          String,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

/* ── type labels ── */
const typeLabel = {
  new:'Baru', follow_up:'Susulan', annual_checkup:'Semakan Tahunan',
  procedure:'Prosedur', antenatal:'Antenatal', teleconsult:'Teleperubatan',
}
const statusTone  = { confirmed:'neutral', waiting:'yellow', in_room:'green', done:'green', open:'yellow', closed:'neutral' }
const statusLabel = { confirmed:'Belum Daftar', waiting:'Menunggu', in_room:'Dalam Bilik', done:'Selesai', open:'Buka', closed:'Tutup' }
const methodLabel = { cash:'Tunai', card:'Kad', duitnow:'DuitNow', panel:'Panel', insurance:'Insurans' }
const alertTone   = { orange:'#EA580C', yellow:'#D97706', blue:'#2563EB', red:'#DC2626' }
const alertBg     = { orange:'#FFF7ED', yellow:'#FFFBEB', blue:'#EFF6FF', red:'#FEF2F2' }
const alertIcon   = { stock:'📦', rx:'💊', inv:'📄', visit:'📋' }

/* ── bar chart helpers ── */
const maxRev = computed(() => Math.max(...props.revChart.map(r => r.value), 1))
function barH(val) { return Math.max(4, Math.round((val / maxRev.value) * 72)) + 'px' }
</script>

<template>
  <div class="dash-root">

    <!-- flash -->
    <div v-if="flash?.success" class="flash-ok">{{ flash.success }}</div>

    <!-- greeting -->
    <div class="greeting">
      <div>
        <h2 class="greeting__name">Selamat datang, {{ userName }}</h2>
        <p class="greeting__date">{{ today }}</p>
      </div>
      <div class="greeting__actions">
        <Btn variant="secondary" size="sm" @click="router.visit('/register-patient')">+ Daftar Pesakit</Btn>
        <Btn variant="primary"   size="sm" @click="router.visit('/appointments')">+ Temujanji</Btn>
      </div>
    </div>

    <!-- ── KPI row ── -->
    <div class="kpi-row">
      <!-- Pesakit hari ini -->
      <div class="kpi-card">
        <div class="kpi-card__label">Pesakit Hari Ini</div>
        <div class="kpi-card__val">{{ kpi.today_total }}</div>
        <div class="kpi-card__sub">
          <span class="dot dot--green" />{{ kpi.today_in_room }} dalam bilik ·
          <span class="dot dot--amber" />{{ kpi.today_waiting }} menunggu ·
          <span class="dot dot--grey"  />{{ kpi.today_done }} selesai
        </div>
      </div>

      <!-- Revenue bulan -->
      <div class="kpi-card">
        <div class="kpi-card__label">Kutipan Bulan Ini</div>
        <div class="kpi-card__val kpi-card__val--green">RM {{ Number(kpi.month_revenue).toLocaleString('ms-MY', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</div>
        <div class="kpi-card__sub">Hari ini: RM {{ Number(kpi.today_revenue).toFixed(2) }}</div>
      </div>

      <!-- Invois tertunggak -->
      <div class="kpi-card" :class="{ 'kpi-card--warn': kpi.pending_inv > 0 }">
        <div class="kpi-card__label">Invois Tertunggak</div>
        <div class="kpi-card__val" :style="kpi.pending_inv > 0 ? 'color:#D97706' : ''">{{ kpi.pending_inv }}</div>
        <div class="kpi-card__sub">RM {{ Number(kpi.pending_amount).toFixed(2) }} belum dikutip</div>
      </div>

      <!-- Jumlah pesakit aktif -->
      <div class="kpi-card">
        <div class="kpi-card__label">Pesakit Berdaftar</div>
        <div class="kpi-card__val">{{ kpi.total_patients }}</div>
        <div class="kpi-card__sub">{{ kpi.open_visits }} rekod EMR terbuka</div>
      </div>
    </div>

    <!-- ── main grid ── -->
    <div class="main-grid">

      <!-- LEFT column -->
      <div class="left-col">

        <!-- Jadual hari ini -->
        <div class="card">
          <div class="card__header">
            <div>
              <h3 class="card__title">Jadual Hari Ini</h3>
              <p class="card__sub">{{ upcoming.length }} temujanji aktif</p>
            </div>
            <Btn variant="ghost" size="sm" @click="router.visit('/queue')">Lihat Giliran →</Btn>
          </div>

          <div v-if="upcoming.length">
            <div
              v-for="u in upcoming"
              :key="u.id"
              class="sched-row"
              :class="'sched-row--' + u.status"
            >
              <div class="sched-time">{{ u.appointment_time }}</div>
              <Avatar :name="u.patient_name" size="sm" />
              <div class="sched-info">
                <div class="sched-name">{{ u.patient_name }}</div>
                <div class="sched-type">
                  {{ typeLabel[u.type] ?? u.type }}
                  <span v-if="u.reason"> · {{ u.reason }}</span>
                </div>
              </div>
              <Badge :tone="statusTone[u.status]" size="xs">{{ statusLabel[u.status] }}</Badge>
              <Btn
                variant="primary"
                size="sm"
                @click="router.visit('/queue')"
              >Mula →</Btn>
            </div>
          </div>
          <div v-else class="empty-card">
            Tiada temujanji aktif hari ini.
          </div>
        </div>

        <!-- Rekod EMR terkini -->
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">Rekod EMR Terkini</h3>
            <Btn variant="ghost" size="sm" @click="router.visit('/emr')">Semua →</Btn>
          </div>

          <div class="table__head" style="grid-template-columns:1fr 80px 110px 90px">
            <div>Pesakit</div><div>Tarikh</div><div>Doktor</div><div>Status</div>
          </div>
          <div
            v-for="v in recentVisits"
            :key="v.id"
            class="table__row"
            style="grid-template-columns:1fr 80px 110px 90px; cursor:pointer"
            @click="router.visit('/emr')"
          >
            <div style="display:flex;align-items:center;gap:8px">
              <Avatar :name="v.patient_name" size="sm" />
              <span style="font:600 12.5px var(--font-sans);color:var(--fg1)">{{ v.patient_name }}</span>
            </div>
            <div style="font:500 12px var(--font-sans);color:var(--fg3)">{{ v.visit_date }}</div>
            <div style="font:400 12px var(--font-sans);color:var(--fg3)">{{ v.doctor_name }}</div>
            <div><Badge :tone="statusTone[v.status]" size="xs">{{ statusLabel[v.status] }}</Badge></div>
          </div>
          <p v-if="!recentVisits.length" class="empty-card">Tiada rekod EMR.</p>
        </div>

      </div>

      <!-- RIGHT column -->
      <div class="right-col">

        <!-- Alerts -->
        <div v-if="alerts.length" class="card">
          <div class="card__header"><h3 class="card__title">Perhatian</h3></div>
          <div
            v-for="(a, i) in alerts"
            :key="i"
            class="alert-row"
            :style="{ background: alertBg[a.tone] }"
          >
            <div class="alert-icon" :style="{ color: alertTone[a.tone] }">{{ alertIcon[a.type] }}</div>
            <div class="alert-body">
              <div class="alert-title" :style="{ color: alertTone[a.tone] }">{{ a.title }}</div>
              <div class="alert-msg">{{ a.msg }}</div>
            </div>
          </div>
        </div>

        <!-- Revenue 7 hari -->
        <div class="card">
          <div class="card__header">
            <div>
              <h3 class="card__title">Pendapatan 7 Hari</h3>
              <p class="card__sub">RM {{ Number(revChart.reduce((s,r)=>s+r.value,0)).toLocaleString('ms-MY',{minimumFractionDigits:2}) }}</p>
            </div>
          </div>
          <div class="bar-chart">
            <div
              v-for="r in revChart"
              :key="r.date"
              class="bar-col"
            >
              <div class="bar-val">{{ r.value > 0 ? Number(r.value).toFixed(0) : '' }}</div>
              <div class="bar-wrap">
                <div class="bar" :style="{ height: barH(r.value) }" :class="{ 'bar--today': r.date === new Date().toISOString().slice(0,10) }" />
              </div>
              <div class="bar-lbl">{{ r.label }}</div>
            </div>
          </div>
        </div>

        <!-- Pembayaran terkini -->
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">Pembayaran Terkini</h3>
            <Btn variant="ghost" size="sm" @click="router.visit('/billing')">Semua →</Btn>
          </div>

          <div v-if="recentInvoices.length">
            <div
              v-for="inv in recentInvoices"
              :key="inv.id"
              class="pay-row"
              @click="router.visit('/billing?invoice=' + inv.id)"
            >
              <Avatar :name="inv.patient_name" size="sm" />
              <div class="pay-info">
                <div class="pay-name">{{ inv.patient_name }}</div>
                <div class="pay-meta">{{ inv.invoice_number }} · {{ methodLabel[inv.payment_method] ?? inv.payment_method }}</div>
              </div>
              <div class="pay-right">
                <div class="pay-amount">RM {{ Number(inv.total_amount).toFixed(2) }}</div>
                <div class="pay-time">{{ inv.paid_at }}</div>
              </div>
            </div>
          </div>
          <p v-else class="empty-card">Tiada pembayaran hari ini.</p>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.dash-root {
  padding: 20px 24px;
  overflow-y: auto;
  height: calc(100vh - 56px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: var(--bg-soft);
}

.flash-ok {
  background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px;
  color: #16A34A; font: 500 12.5px var(--font-sans); padding: 10px 16px;
}

/* greeting */
.greeting {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.greeting__name { font: 700 18px var(--font-sans); color: var(--fg1); }
.greeting__date { font: 400 12px var(--font-sans); color: var(--fg3); margin-top: 2px; }
.greeting__actions { display: flex; gap: 8px; }

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
.kpi-card--warn { border-color: #FDE68A; background: #FFFBEB; }
.kpi-card__label { font: 500 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; }
.kpi-card__val   { font: 800 26px var(--font-mono); color: var(--fg1); margin: 6px 0 4px; line-height: 1; }
.kpi-card__val--green { color: var(--brand-green); }
.kpi-card__sub   { font: 400 11.5px var(--font-sans); color: var(--fg3); display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.dot--green { background: var(--brand-green); }
.dot--amber { background: #D97706; }
.dot--grey  { background: #94A3B8; }

/* main grid */
.main-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 14px;
  align-items: start;
}
.left-col  { display: flex; flex-direction: column; gap: 14px; }
.right-col { display: flex; flex-direction: column; gap: 14px; }

/* schedule rows */
.sched-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
  border-top: 1px solid var(--border);
  transition: background .1s;
}
.sched-row:hover { background: var(--bg-soft); }
.sched-row--in_room { background: #F0FDF4; }
.sched-row--waiting { background: #FFFBEB; }
.sched-time { font: 700 13px var(--font-mono); color: var(--fg2); width: 46px; flex-shrink: 0; }
.sched-info { flex: 1; min-width: 0; }
.sched-name { font: 600 13px var(--font-sans); color: var(--fg1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sched-type { font: 400 11.5px var(--font-sans); color: var(--fg3); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* alerts */
.alert-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
  border-top: 1px solid var(--border);
}
.alert-icon  { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.alert-title { font: 700 12.5px var(--font-sans); }
.alert-msg   { font: 400 12px var(--font-sans); color: var(--fg3); margin-top: 2px; }

/* bar chart */
.bar-chart {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  padding: 12px 16px 14px;
  height: 120px;
}
.bar-col  { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%; justify-content: flex-end; }
.bar-val  { font: 600 9px var(--font-mono); color: var(--fg3); min-height: 12px; }
.bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; }
.bar      { width: 100%; border-radius: 4px 4px 0 0; background: var(--brand-green-light); border: 1px solid #A7F3D0; transition: height .3s; }
.bar--today { background: var(--brand-green); border-color: var(--brand-green); }
.bar-lbl  { font: 500 10px var(--font-sans); color: var(--fg3); }

/* payments */
.pay-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  border-top: 1px solid var(--border);
  cursor: pointer;
  transition: background .1s;
}
.pay-row:hover { background: var(--bg-soft); }
.pay-info  { flex: 1; min-width: 0; }
.pay-name  { font: 600 12.5px var(--font-sans); color: var(--fg1); }
.pay-meta  { font: 400 11px var(--font-sans); color: var(--fg3); margin-top: 1px; }
.pay-right { text-align: right; flex-shrink: 0; }
.pay-amount { font: 700 13px var(--font-mono); color: var(--brand-green); }
.pay-time   { font: 400 11px var(--font-sans); color: var(--fg3); margin-top: 1px; }

.empty-card {
  padding: 24px 16px;
  text-align: center;
  color: var(--fg3);
  font: 400 12.5px var(--font-sans);
}
</style>
