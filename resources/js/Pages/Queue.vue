<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import TriagePill from '@/Components/Clinic/TriagePill.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  queue:  Array,
  stats:  Object,
  today:  String,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

/* ── split by group ── */
const active  = computed(() => props.queue.filter(r => ['in_room','waiting','confirmed'].includes(r.status)))
const finished = computed(() => props.queue.filter(r => ['done','no_show','cancelled'].includes(r.status)))

/* ── status helpers ── */
const statusTone = { in_room:'green', waiting:'yellow', confirmed:'neutral', done:'green', no_show:'neutral', cancelled:'red' }
const statusLabel = { in_room:'Dalam Bilik', waiting:'Menunggu', confirmed:'Belum Daftar', done:'Selesai', no_show:'Tiada Hadir', cancelled:'Batal' }

const typeLabel = {
  new:'Baru', follow_up:'Susulan', annual_checkup:'Semakan Tahunan',
  procedure:'Prosedur', antenatal:'Antenatal', teleconsult:'Teleperubatan',
}
const typeTone = {
  new:'blue', follow_up:'neutral', annual_checkup:'purple',
  procedure:'yellow', antenatal:'green', teleconsult:'blue',
}

function waitLabel(mins) {
  if (mins <= 0) return '< 1 min'
  if (mins < 60) return mins + ' min'
  const h = Math.floor(mins / 60), m = mins % 60
  return h + 'j ' + (m ? m + 'm' : '')
}

function waitColor(mins, status) {
  if (status === 'in_room' || status === 'done') return 'var(--fg3)'
  if (mins > 45) return '#DC2626'
  if (mins > 20) return '#D97706'
  return 'var(--fg2)'
}

/* ── row bg ── */
function rowBg(status) {
  if (status === 'in_room')  return '#F0FDF4'
  if (status === 'waiting')  return '#FFFBEB'
  if (status === 'done')     return '#F8FAFC'
  if (status === 'no_show')  return '#F8FAFC'
  return '#fff'
}

/* ── actions ── */
function setStatus(apptId, status) {
  router.patch(`/appointments/${apptId}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['queue', 'stats'] }),
  })
}

function goEmr(row) {
  router.visit('/emr')
}

/* ── refresh ── */
let refreshTimer
function startAutoRefresh() {
  refreshTimer = setInterval(() => {
    router.reload({ only: ['queue', 'stats'], preserveScroll: true })
  }, 60000) // every 60s
}
startAutoRefresh()
</script>

<template>
  <div class="queue-root">

    <!-- ── stats bar ── -->
    <div class="stats-bar">
      <div class="stat-box stat-box--total">
        <div class="stat-icon">📋</div>
        <div>
          <div class="stat-lbl">Giliran Hari Ini</div>
          <div class="stat-val">{{ stats.total_today }}</div>
        </div>
      </div>
      <div class="stat-box stat-box--wait">
        <div class="stat-icon">⏳</div>
        <div>
          <div class="stat-lbl">Menunggu</div>
          <div class="stat-val">{{ stats.waiting }}</div>
        </div>
      </div>
      <div class="stat-box stat-box--room">
        <div class="stat-icon">🚪</div>
        <div>
          <div class="stat-lbl">Dalam Bilik</div>
          <div class="stat-val">{{ stats.in_room }}</div>
        </div>
      </div>
      <div class="stat-box stat-box--done">
        <div class="stat-icon">✅</div>
        <div>
          <div class="stat-lbl">Selesai</div>
          <div class="stat-val">{{ stats.done }}</div>
        </div>
      </div>
      <div class="stat-date">
        <div class="stat-lbl">Tarikh</div>
        <div class="stat-day">{{ today }}</div>
      </div>
    </div>

    <!-- ── flash ── -->
    <div v-if="flash?.success" class="flash-ok">{{ flash.success }}</div>

    <!-- ── scrollable content ── -->
    <div class="queue-scroll">

      <!-- AKTIF -->
      <div class="card" style="overflow:hidden">
        <div class="card__header">
          <h3 class="card__title">Giliran Aktif</h3>
          <div style="display:flex;gap:6px;align-items:center">
            <span v-if="active.length" class="count-pill">{{ active.length }}</span>
            <Btn variant="secondary" size="sm" @click="router.visit('/appointments')">+ Temujanji Baru</Btn>
          </div>
        </div>

        <!-- legend -->
        <div class="legend">
          <span class="legend-item legend-item--room">Dalam Bilik</span>
          <span class="legend-item legend-item--wait">Menunggu</span>
          <span class="legend-item legend-item--conf">Belum Daftar</span>
        </div>

        <!-- table head -->
        <div class="table__head q-cols">
          <div>No. Giliran</div>
          <div>Pesakit</div>
          <div>Masa / Jenis</div>
          <div>IC / Umur</div>
          <div>Aduan Utama</div>
          <div>Tunggu</div>
          <div>Status</div>
          <div>Tindakan</div>
        </div>

        <!-- active rows -->
        <template v-if="active.length">
          <div
            v-for="row in active"
            :key="row.id"
            class="table__row q-cols"
            :style="{ background: rowBg(row.status) }"
          >
            <!-- queue no -->
            <div class="q-no">
              <span class="q-dot" :class="'q-dot--' + row.status" />
              <span>{{ row.queue_no }}</span>
            </div>

            <!-- patient -->
            <div style="display:flex;align-items:center;gap:8px">
              <Avatar :name="row.patient_name" size="sm" />
              <div>
                <div class="pt-name">{{ row.patient_name }}</div>
                <div v-if="row.patient_allergies" class="pt-allergy">⚠ {{ row.patient_allergies }}</div>
              </div>
            </div>

            <!-- time / type -->
            <div>
              <div class="appt-time">{{ row.appointment_time }}</div>
              <Badge :tone="typeTone[row.type] ?? 'neutral'" size="xs">{{ typeLabel[row.type] ?? row.type }}</Badge>
            </div>

            <!-- IC / age -->
            <div>
              <div class="pt-ic">{{ row.patient_ic }}</div>
              <div class="pt-age">{{ row.patient_age }}</div>
            </div>

            <!-- complaint -->
            <div class="pt-reason">{{ row.reason ?? '—' }}</div>

            <!-- wait -->
            <div class="wait-val" :style="{ color: waitColor(row.wait_minutes, row.status) }">
              {{ row.status === 'in_room' ? 'Dalam bilik' : waitLabel(row.wait_minutes) }}
            </div>

            <!-- status badge -->
            <div><Badge :tone="statusTone[row.status]" size="xs">{{ statusLabel[row.status] }}</Badge></div>

            <!-- actions -->
            <div class="action-cell">
              <!-- confirmed: check-in or no-show -->
              <template v-if="row.status === 'confirmed'">
                <Btn variant="primary" size="sm" @click="setStatus(row.id, 'waiting')">Daftar Masuk</Btn>
                <button class="ghost-sm" @click="setStatus(row.id, 'no_show')" title="Tiada Hadir">✕</button>
              </template>

              <!-- waiting: call to room or no-show -->
              <template v-else-if="row.status === 'waiting'">
                <Btn variant="primary" size="sm" @click="setStatus(row.id, 'in_room')">Panggil Masuk</Btn>
                <button class="ghost-sm" @click="setStatus(row.id, 'no_show')" title="Tiada Hadir">✕</button>
              </template>

              <!-- in_room: open EMR + done -->
              <template v-else-if="row.status === 'in_room'">
                <Btn variant="primary" size="sm" @click="goEmr(row)">Buka EMR →</Btn>
                <Btn variant="secondary" size="sm" @click="setStatus(row.id, 'done')">Selesai</Btn>
              </template>
            </div>
          </div>
        </template>

        <div v-else class="empty-q">
          <span>Tiada giliran aktif hari ini.</span>
          <Btn variant="secondary" size="sm" @click="router.visit('/appointments')">+ Daftar Pesakit</Btn>
        </div>
      </div>

      <!-- SELESAI -->
      <div v-if="finished.length" class="card" style="overflow:hidden">
        <div class="card__header">
          <h3 class="card__title" style="color:var(--fg3)">Selesai Hari Ini</h3>
          <span class="count-pill count-pill--grey">{{ finished.length }}</span>
        </div>

        <div class="table__head q-cols-done">
          <div>No.</div>
          <div>Pesakit</div>
          <div>Masa / Jenis</div>
          <div>IC / Umur</div>
          <div>Aduan Utama</div>
          <div>Status</div>
          <div>Tindakan</div>
        </div>

        <div
          v-for="row in finished"
          :key="row.id"
          class="table__row q-cols-done"
          style="opacity:.7"
        >
          <div class="q-no" style="color:var(--fg3)">{{ row.queue_no }}</div>

          <div style="display:flex;align-items:center;gap:8px">
            <Avatar :name="row.patient_name" size="sm" />
            <div class="pt-name" style="color:var(--fg2)">{{ row.patient_name }}</div>
          </div>

          <div>
            <div class="appt-time" style="color:var(--fg3)">{{ row.appointment_time }}</div>
            <Badge :tone="typeTone[row.type] ?? 'neutral'" size="xs">{{ typeLabel[row.type] ?? row.type }}</Badge>
          </div>

          <div>
            <div class="pt-ic">{{ row.patient_ic }}</div>
            <div class="pt-age">{{ row.patient_age }}</div>
          </div>

          <div class="pt-reason" style="color:var(--fg3)">{{ row.reason ?? '—' }}</div>

          <div><Badge :tone="statusTone[row.status]" size="xs">{{ statusLabel[row.status] }}</Badge></div>

          <div class="action-cell">
            <button
              v-if="row.status === 'done'"
              class="ghost-sm"
              style="font-size:11px;padding:3px 8px;color:var(--brand-green)"
              @click="goEmr(row)"
            >Lihat EMR</button>
            <button
              v-if="row.status === 'no_show'"
              class="ghost-sm"
              style="font-size:11px;padding:3px 8px"
              @click="setStatus(row.id, 'waiting')"
            >Daftar Semula</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.queue-root {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 56px);
  overflow: hidden;
  background: var(--bg-soft);
}

/* stats bar */
.stats-bar {
  display: flex;
  align-items: stretch;
  gap: 1px;
  background: var(--border);
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.stat-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  background: #fff;
  flex: 1;
}
.stat-icon { font-size: 20px; line-height: 1; }
.stat-lbl  { font: 500 11px var(--font-sans); color: var(--fg3); }
.stat-val  { font: 800 22px var(--font-mono); color: var(--fg1); margin-top: 1px; }

.stat-box--room .stat-val { color: var(--brand-green); }
.stat-box--wait .stat-val { color: #D97706; }

.stat-date {
  padding: 12px 20px;
  background: #fff;
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex-shrink: 0;
}
.stat-day { font: 600 12px var(--font-sans); color: var(--fg1); margin-top: 2px; }

/* flash */
.flash-ok {
  background: #F0FDF4;
  border-bottom: 1px solid #BBF7D0;
  color: #16A34A;
  font: 500 12.5px var(--font-sans);
  padding: 8px 20px;
  flex-shrink: 0;
}

/* scrollable */
.queue-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* legend */
.legend {
  display: flex;
  gap: 16px;
  padding: 8px 16px;
  border-bottom: 1px solid var(--border);
  background: var(--bg-soft);
}
.legend-item {
  font: 500 11px var(--font-sans);
  display: flex;
  align-items: center;
  gap: 5px;
}
.legend-item::before {
  content: '';
  width: 10px;
  height: 10px;
  border-radius: 3px;
}
.legend-item--room::before { background: #BBF7D0; }
.legend-item--wait::before { background: #FDE68A; }
.legend-item--conf::before { background: #E2E8F0; }
.legend-item--room { color: #16A34A; }
.legend-item--wait { color: #D97706; }
.legend-item--conf { color: var(--fg3); }

/* grid columns */
.q-cols {
  grid-template-columns: 100px 1.8fr 120px 130px 1.5fr 100px 110px 180px !important;
}
.q-cols-done {
  grid-template-columns: 80px 1.8fr 120px 130px 1.5fr 110px 120px !important;
}

/* queue no */
.q-no {
  display: flex;
  align-items: center;
  gap: 6px;
  font: 700 13px var(--font-mono);
  color: var(--fg1);
}
.q-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.q-dot--in_room  { background: #16A34A; box-shadow: 0 0 0 3px #BBF7D0; }
.q-dot--waiting  { background: #D97706; box-shadow: 0 0 0 3px #FDE68A; }
.q-dot--confirmed { background: #94A3B8; }

/* patient info */
.pt-name   { font: 600 13px var(--font-sans); color: var(--fg1); }
.pt-ic     { font: 500 11.5px var(--font-mono); color: var(--fg3); }
.pt-age    { font: 500 11.5px var(--font-sans); color: var(--fg2); margin-top: 2px; }
.pt-reason { font: 400 12.5px var(--font-sans); color: var(--fg2); }
.pt-allergy { font: 500 10.5px var(--font-sans); color: #DC2626; margin-top: 2px; }
.appt-time { font: 700 13px var(--font-mono); color: var(--fg1); margin-bottom: 3px; }

/* wait */
.wait-val  { font: 700 13px var(--font-mono); }

/* actions */
.action-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.ghost-sm {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: #fff;
  color: var(--fg3);
  font-size: 11px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .12s;
}
.ghost-sm:hover { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }

/* count pill */
.count-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--brand-green);
  color: #fff;
  font: 700 11px var(--font-sans);
  min-width: 22px;
  height: 22px;
  border-radius: 999px;
  padding: 0 6px;
}
.count-pill--grey {
  background: var(--border);
  color: var(--fg3);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font: 700 11px var(--font-sans);
  min-width: 22px;
  height: 22px;
  border-radius: 999px;
  padding: 0 6px;
}

/* empty */
.empty-q {
  padding: 40px 16px;
  text-align: center;
  color: var(--fg3);
  font: 400 13px var(--font-sans);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
</style>
