<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useLocale } from '@/composables/useLocale'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  currentRoute: String,
  weekStart:    String,
  weekEnd:      String,
  weekDates:    Array,
  weekMap:      Object,
  todayList:    Array,
  slots:        Array,
  stats:        Object,
  patients:     Array,
  today:        String,
})

const flash = computed(() => usePage().props.flash?.success)

/* ── Week navigation ─────────────────────────────────── */
function goWeek(offset) {
  const d = new Date(props.weekStart)
  d.setDate(d.getDate() + offset * 7)
  router.get('/appointments', { week: d.toISOString().slice(0, 10) }, { preserveScroll: true })
}
function goToday() {
  router.get('/appointments', {}, { preserveScroll: true })
}
const isCurrentWeek = computed(() => {
  const now = new Date(props.today)
  const ws  = new Date(props.weekStart)
  const we  = new Date(props.weekEnd)
  return now >= ws && now <= we
})

/* ── Status / type maps ──────────────────────────────── */
const STATUS_TONE = {
  confirmed: 'blue',
  waiting:   'yellow',
  in_room:   'purple',
  done:      'green',
  cancelled: 'neutral',
  no_show:   'red',
}
const { t } = useLocale()

const STATUS_LABELS = computed(() => ({
  confirmed: t('status_confirmed'),
  waiting:   t('status_waiting'),
  in_room:   t('status_in_room'),
  done:      t('status_done'),
  cancelled: t('status_cancelled'),
  no_show:   t('status_no_show'),
}))
const TYPE_LABELS = computed(() => ({
  new:            t('type_new'),
  follow_up:      t('type_follow_up'),
  annual_checkup: t('type_annual_checkup'),
  procedure:      t('type_procedure'),
  antenatal:      t('type_antenatal'),
  teleconsult:    t('type_teleconsult'),
}))
const CHIP_CLASS = {
  confirmed: 'chip-blue',
  waiting:   'chip-yellow',
  in_room:   'chip-purple',
  done:      'chip-green',
  cancelled: 'chip-neutral',
  no_show:   'chip-red',
}

function getAppt(date, time) {
  return props.weekMap?.[date]?.[time] ?? null
}

/* ── View drawer ─────────────────────────────────────── */
const viewAppt = ref(null)
function openView(appt) { viewAppt.value = appt }
function closeView()    { viewAppt.value = null }

/* ── Status update ───────────────────────────────────── */
function updateStatus(appt, status) {
  router.patch(`/appointments/${appt.id}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => {
      if (viewAppt.value?.id === appt.id) viewAppt.value = { ...viewAppt.value, status }
    },
  })
}

/* ── Create / Edit modal ─────────────────────────────── */
const showModal  = ref(false)
const editTarget = ref(null)

const form = useForm({
  patient_id:       '',
  doctor_name:      'Dr. Aiman Rashid',
  appointment_date: props.today,
  appointment_time: '08:00',
  duration_minutes: 30,
  type:             'follow_up',
  reason:           '',
  status:           'confirmed',
  notes:            '',
})

function openCreate(date = null, time = null) {
  editTarget.value          = null
  form.reset()
  form.appointment_date     = date ?? props.today
  form.appointment_time     = time ?? '08:00'
  form.doctor_name          = 'Dr. Aiman Rashid'
  form.duration_minutes     = 30
  form.type                 = 'follow_up'
  form.status               = 'confirmed'
  showModal.value           = true
}

function openEdit(appt) {
  editTarget.value          = appt
  form.patient_id           = appt.patient_id
  form.doctor_name          = appt.doctor_name
  form.appointment_date     = appt.appointment_date
  form.appointment_time     = appt.appointment_time
  form.duration_minutes     = appt.duration_minutes
  form.type                 = appt.type
  form.reason               = appt.reason ?? ''
  form.status               = appt.status
  form.notes                = appt.notes ?? ''
  showModal.value           = true
  viewAppt.value            = null
}

function closeModal() { showModal.value = false; editTarget.value = null }

function submitForm() {
  if (editTarget.value) {
    form.put(`/appointments/${editTarget.value.id}`, { preserveScroll: true, onSuccess: closeModal })
  } else {
    form.post('/appointments', { preserveScroll: true, onSuccess: closeModal })
  }
}

/* ── Delete ──────────────────────────────────────────── */
const deleteTarget = ref(null)
function confirmDelete(appt) { deleteTarget.value = appt; viewAppt.value = null }
function doDelete() {
  router.delete(`/appointments/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { deleteTarget.value = null },
  })
}

/* ── Patient search ──────────────────────────────────── */
const patientSearch  = ref('')
const patientResults = computed(() => {
  if (patientSearch.value.length < 2) return []
  const q = patientSearch.value.toLowerCase()
  return props.patients.filter(p =>
    p.name.toLowerCase().includes(q) || p.ic_number.includes(q)
  ).slice(0, 6)
})
function selectPatient(p) {
  form.patient_id     = p.id
  patientSearch.value = `${p.name} (${p.ic_number})`
}
watch(showModal, (open) => {
  if (open && editTarget.value) {
    const p = props.patients.find(p => p.id === editTarget.value.patient_id)
    if (p) patientSearch.value = `${p.name} (${p.ic_number})`
  } else if (!open) {
    patientSearch.value = ''
  }
})

/* ── Week label ──────────────────────────────────────── */
const weekLabel = computed(() => {
  const s = new Date(props.weekStart)
  const e = new Date(props.weekEnd)
  const fmt = d => d.toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' })
  return `${fmt(s)} – ${fmt(e)}`
})
</script>

<template>
  <div class="screen">

    <!-- Flash -->
    <div v-if="flash" class="flash-ok">{{ flash }}</div>

    <!-- ── Header ──────────────────────────────────── -->
    <div class="row">
      <div>
        <h1 style="font:700 18px var(--font-sans);color:var(--fg1);margin:0">Temujanji</h1>
        <p style="font:500 12px var(--font-sans);color:var(--fg3);margin:2px 0 0">Jadual mingguan & senarai hari ini</p>
      </div>
      <div class="spacer"></div>
      <Btn variant="primary" @click="openCreate()">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        {{ t('appt_new') }}
      </Btn>
    </div>

    <!-- ── KPIs ────────────────────────────────────── -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="kpi__label">{{ t('appt_kpi_total') }}</div>
        <div class="kpi__value">{{ stats.total }}</div>
        <div class="kpi__sub">{{ weekLabel }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('appt_kpi_confirmed') }}</div>
        <div class="kpi__value" style="color:#1d4ed8">{{ stats.confirmed }}</div>
        <div class="kpi__sub">{{ t('appt_kpi_pending') }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('appt_kpi_done') }}</div>
        <div class="kpi__value" style="color:var(--brand-green)">{{ stats.done }}</div>
        <div class="kpi__sub">{{ t('appt_kpi_week') }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('appt_kpi_cancelled') }}</div>
        <div class="kpi__value" style="color:var(--fg3)">{{ stats.cancelled }}</div>
        <div class="kpi__sub">{{ t('appt_kpi_week') }}</div>
      </div>
    </div>

    <!-- ── Two-column: calendar + queue ────────────── -->
    <div class="appt-layout">

      <!-- Calendar card -->
      <div class="card" style="display:flex;flex-direction:column;overflow:hidden;min-height:0">

        <!-- Week nav -->
        <div class="week-nav">
          <button class="btn btn--ghost btn--sm" style="padding:6px 8px" @click="goWeek(-1)">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          </button>
          <div class="row" style="gap:10px">
            <span style="font:700 13.5px var(--font-sans);color:var(--fg1)">{{ weekLabel }}</span>
            <button v-if="!isCurrentWeek" class="today-link" @click="goToday">Hari Ini</button>
          </div>
          <button class="btn btn--ghost btn--sm" style="padding:6px 8px" @click="goWeek(1)">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>

        <!-- Grid -->
        <div class="grid-wrap">
          <table class="cal-table">
            <thead>
              <tr>
                <th class="th-time">Masa</th>
                <th v-for="day in weekDates" :key="day.date"
                    :class="['th-day', day.is_today && 'th-day--today']">
                  <div style="font:700 12px var(--font-sans);color:var(--fg1)">{{ day.label }}</div>
                  <div style="font:500 10px var(--font-sans);color:var(--fg3)">{{ day.count }} temujanji</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="slot in slots" :key="slot">
                <td class="td-time">{{ slot }}</td>
                <td v-for="day in weekDates" :key="day.date"
                    :class="['td-slot', day.is_today && 'td-slot--today']"
                    @click="!getAppt(day.date, slot) && openCreate(day.date, slot)">
                  <div v-if="getAppt(day.date, slot)"
                       class="appt-chip"
                       :class="CHIP_CLASS[getAppt(day.date, slot).status]"
                       @click.stop="openView(getAppt(day.date, slot))">
                    <div style="font:600 11px var(--font-sans);line-height:1.3">{{ getAppt(day.date, slot).patient_name }}</div>
                    <div style="font:500 10px var(--font-sans);opacity:.75">{{ TYPE_LABELS[getAppt(day.date, slot).type] }}</div>
                  </div>
                  <div v-else class="slot-plus">+</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Queue card -->
      <div class="card" style="display:flex;flex-direction:column;overflow:hidden;min-height:0">
        <div class="card__header">
          <h3 class="card__title">{{ t('appt_today_list') }}</h3>
          <span class="spacer"></span>
          <span style="background:var(--brand-green);color:#fff;font:700 11px var(--font-sans);padding:2px 8px;border-radius:999px">{{ todayList.length }}</span>
        </div>

        <div v-if="todayList.length === 0"
             style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:32px;color:var(--fg3);font:500 13px var(--font-sans);text-align:center">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          {{ t('appt_no_today') }}
        </div>

        <div v-else style="flex:1;overflow-y:auto">
          <div v-for="a in todayList" :key="a.id" class="qi" @click="openView(a)">
            <div style="font:700 12px var(--font-mono);color:var(--brand-green);width:40px;flex-shrink:0;padding-top:1px">{{ a.appointment_time }}</div>
            <div style="flex:1;min-width:0">
              <div style="font:600 13px var(--font-sans);color:var(--fg1)">{{ a.patient_name }}</div>
              <div style="font:500 11px var(--font-sans);color:var(--fg3)">{{ TYPE_LABELS[a.type] }}</div>
              <div v-if="a.reason" style="font:500 11px var(--font-sans);color:var(--fg2);margin-top:1px">{{ a.reason }}</div>
            </div>
            <Badge :tone="STATUS_TONE[a.status]">{{ STATUS_LABELS[a.status] }}</Badge>
          </div>
        </div>
      </div>
    </div>

    <!-- ── View Drawer ──────────────────────────────── -->
    <Teleport to="body">
      <div v-if="viewAppt" class="drawer-backdrop" @click.self="closeView">
        <div class="drawer">
          <div class="drawer__header">
            <Avatar :name="viewAppt.patient_name" size="lg" />
            <div style="flex:1;min-width:0">
              <h2 class="drawer__name">{{ viewAppt.patient_name }}</h2>
              <div style="font:500 12px var(--font-mono);color:var(--fg3)">{{ viewAppt.patient_ic }}</div>
            </div>
            <button class="modal__close" @click="closeView">✕</button>
          </div>

          <div class="drawer__body">
            <!-- Appointment info -->
            <div class="drow-section-title">{{ t('appt_draw_info') }}</div>
            <div class="info-grid">
              <div class="info-row">
                <span class="info-label">{{ t('appt_lbl_date') }}</span>
                <span class="info-val">{{ viewAppt.appointment_date }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">{{ t('appt_lbl_time') }}</span>
                <span class="info-val mono">{{ viewAppt.appointment_time }} ({{ viewAppt.duration_minutes }} min)</span>
              </div>
              <div class="info-row">
                <span class="info-label">{{ t('appt_lbl_type') }}</span>
                <span class="info-val">{{ TYPE_LABELS[viewAppt.type] }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">{{ t('appt_lbl_doctor') }}</span>
                <span class="info-val">{{ viewAppt.doctor_name }}</span>
              </div>
              <div class="info-row" style="grid-column:1/-1">
                <span class="info-label">{{ t('appt_lbl_status') }}</span>
                <Badge :tone="STATUS_TONE[viewAppt.status]" style="align-self:flex-start;margin-top:2px">{{ STATUS_LABELS[viewAppt.status] }}</Badge>
              </div>
              <div v-if="viewAppt.reason" class="info-row" style="grid-column:1/-1">
                <span class="info-label">{{ t('appt_lbl_reason') }}</span>
                <span class="info-val">{{ viewAppt.reason }}</span>
              </div>
              <div v-if="viewAppt.patient_allergies" class="info-row" style="grid-column:1/-1">
                <span class="info-label">{{ t('appt_lbl_allergy') }}</span>
                <Badge tone="red" style="align-self:flex-start;margin-top:2px">⚠ {{ viewAppt.patient_allergies }}</Badge>
              </div>
              <div v-if="viewAppt.notes" class="info-row" style="grid-column:1/-1">
                <span class="info-label">{{ t('appt_lbl_notes') }}</span>
                <span class="info-val" style="white-space:pre-wrap">{{ viewAppt.notes }}</span>
              </div>
            </div>

            <!-- Status workflow -->
            <div v-if="!['done','cancelled','no_show'].includes(viewAppt.status)">
              <div class="hr" style="margin:18px 0 14px"></div>
              <div class="drow-section-title">{{ t('appt_update_status') }}</div>
              <div class="row" style="flex-wrap:wrap;gap:6px">
                <button v-if="viewAppt.status === 'confirmed'"
                        class="btn btn--secondary btn--sm" style="background:#fef9c3;border-color:#fde68a;color:#854d0e"
                        @click="updateStatus(viewAppt, 'waiting')">
                  {{ t('appt_arrived') }}
                </button>
                <button v-if="viewAppt.status === 'waiting'"
                        class="btn btn--secondary btn--sm" style="background:#f3e8ff;border-color:#e9d5ff;color:#6b21a8"
                        @click="updateStatus(viewAppt, 'in_room')">
                  {{ t('appt_enter_room') }}
                </button>
                <button v-if="viewAppt.status === 'in_room'"
                        class="btn btn--secondary btn--sm" style="background:var(--brand-green-light);border-color:var(--brand-green);color:var(--brand-green-dark)"
                        @click="updateStatus(viewAppt, 'done')">
                  {{ t('appt_done') }}
                </button>
                <button class="btn btn--ghost btn--sm"
                        @click="updateStatus(viewAppt, 'cancelled')">
                  {{ t('appt_cancel_action') }}
                </button>
                <button class="btn btn--ghost btn--sm" style="color:var(--brand-red)"
                        @click="updateStatus(viewAppt, 'no_show')">
                  {{ t('appt_no_show_action') }}
                </button>
              </div>
            </div>

            <div class="hr" style="margin:18px 0 14px"></div>
            <div class="row" style="gap:8px">
              <Btn variant="secondary" style="flex:1" @click="openEdit(viewAppt)">{{ t('appt_edit') }}</Btn>
              <Btn variant="ghost" style="color:var(--brand-red)" @click="confirmDelete(viewAppt)">{{ t('appt_delete') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Create / Edit Modal ──────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="modal modal--lg">
          <div class="modal__header">
            <h3 class="modal__title">{{ editTarget ? t('appt_modal_edit') : t('appt_modal_create') }}</h3>
            <button class="modal__close" @click="closeModal">✕</button>
          </div>
          <form @submit.prevent="submitForm" class="modal__body">

            <!-- Patient search -->
            <div class="modal-section-title">{{ t('appt_sec_patient') }}</div>
            <div class="field" style="margin-bottom:14px">
              <label class="field__label">{{ t('appt_search_patient') }} <span style="color:var(--brand-red)">*</span></label>
              <div style="position:relative">
                <input v-model="patientSearch" type="text" class="input"
                       :placeholder="t('appt_ph_patient')"
                       @input="form.patient_id = ''" autocomplete="off" />
                <div v-if="patientResults.length" class="pdrop">
                  <button v-for="p in patientResults" :key="p.id"
                          type="button" class="popt"
                          @click="selectPatient(p)">
                    <span style="font:600 13px var(--font-sans);color:var(--fg1)">{{ p.name }}</span>
                    <span style="font:500 11px var(--font-mono);color:var(--fg3)">{{ p.ic_number }}</span>
                  </button>
                </div>
              </div>
              <span v-if="form.errors.patient_id" class="field__error">{{ form.errors.patient_id }}</span>
            </div>

            <div class="modal-section-title">{{ t('appt_sec_details') }}</div>
            <div class="form-grid-3" style="margin-bottom:14px">
              <div class="field">
                <label class="field__label">{{ t('appt_lbl_date') }} <span style="color:var(--brand-red)">*</span></label>
                <input v-model="form.appointment_date" type="date" class="input" required />
              </div>
              <div class="field">
                <label class="field__label">{{ t('appt_lbl_time') }} <span style="color:var(--brand-red)">*</span></label>
                <select v-model="form.appointment_time" class="select" required>
                  <option v-for="s in slots" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
              <div class="field">
                <label class="field__label">{{ t('appt_lbl_duration') }}</label>
                <select v-model="form.duration_minutes" class="select">
                  <option :value="15">{{ t('appt_duration_15') }}</option>
                  <option :value="30">{{ t('appt_duration_30') }}</option>
                  <option :value="45">{{ t('appt_duration_45') }}</option>
                  <option :value="60">{{ t('appt_duration_60') }}</option>
                </select>
              </div>
              <div class="field">
                <label class="field__label">{{ t('appt_lbl_type') }}</label>
                <select v-model="form.type" class="select">
                  <option v-for="(lbl, val) in TYPE_LABELS" :key="val" :value="val">{{ lbl }}</option>
                </select>
              </div>
              <div class="field" style="grid-column:2/-1">
                <label class="field__label">{{ t('appt_lbl_doctor') }}</label>
                <input v-model="form.doctor_name" type="text" class="input" />
              </div>
              <div v-if="editTarget" class="field">
                <label class="field__label">{{ t('appt_lbl_status') }}</label>
                <select v-model="form.status" class="select">
                  <option v-for="(lbl, val) in STATUS_LABELS" :key="val" :value="val">{{ lbl }}</option>
                </select>
              </div>
              <div class="field" style="grid-column:1/-1">
                <label class="field__label">{{ t('appt_lbl_reason') }}</label>
                <input v-model="form.reason" type="text" class="input"
                       :placeholder="t('appt_ph_reason')" maxlength="255" />
              </div>
              <div class="field" style="grid-column:1/-1">
                <label class="field__label">{{ t('appt_lbl_notes') }}</label>
                <textarea v-model="form.notes" class="input" rows="3"
                          :placeholder="t('appt_ph_notes')" maxlength="1000"
                          style="resize:vertical"></textarea>
              </div>
            </div>

            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="closeModal">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" type="submit" :disabled="form.processing || !form.patient_id">
                {{ form.processing ? t('btn_saving') : (editTarget ? t('btn_update') : t('appt_save')) }}
              </Btn>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Delete confirm ───────────────────────────── -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('appt_del_title') }}</h3>
            <button class="modal__close" @click="deleteTarget = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              {{ t('appt_del_body', { name: deleteTarget.patient_name, date: deleteTarget.appointment_date, time: deleteTarget.appointment_time }) }}
            </p>
            <p style="font:400 13px var(--font-sans);color:var(--fg3);margin:0">{{ t('appt_del_warning') }}</p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="deleteTarget = null">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="doDelete">{{ t('appt_del_confirm') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<style scoped>
/* Flash */
.flash-ok {
  background: var(--brand-green-light); border: 1px solid var(--brand-green);
  color: var(--brand-green-dark); padding: 10px 16px; border-radius: 8px;
  font: 600 13px var(--font-sans);
}

/* KPI grid — same as Inventory */
.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }

/* Two-col layout */
.appt-layout { display: grid; grid-template-columns: 1fr 280px; gap: 14px; min-height: 0; flex: 1; }

/* ── Calendar ──────────────────────────────────────── */
.week-nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border-bottom: 1px solid var(--border);
}
.today-link {
  background: none; border: 1px solid var(--brand-green); color: var(--brand-green);
  border-radius: 999px; padding: 2px 10px; font: 600 11px var(--font-sans); cursor: pointer;
}
.today-link:hover { background: var(--brand-green-light); }

.grid-wrap { overflow: auto; flex: 1; }
.cal-table { width: 100%; border-collapse: collapse; min-width: 680px; }
.cal-table thead th {
  background: var(--bg-soft); position: sticky; top: 0; z-index: 2;
  padding: 8px 10px; text-align: center;
  border-bottom: 1px solid var(--border);
}
.th-time  { width: 60px; text-align: left !important; }
.th-day--today { background: #ecfdf5 !important; }

.td-time  { padding: 5px 8px; font: 600 11px var(--font-mono); color: var(--fg3); vertical-align: top; white-space: nowrap; border-bottom: 1px solid var(--border); }
.td-slot  { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid var(--border); border-left: 1px solid var(--border); cursor: pointer; min-width: 96px; }
.td-slot:hover { background: var(--bg-soft); }
.td-slot--today { background: #f7fdfb; }

/* Chip colours */
.appt-chip { border: 1px solid; border-radius: 6px; padding: 4px 7px; cursor: pointer; }
.appt-chip:hover { filter: brightness(.95); }
.chip-blue    { background: #dbeafe; border-color: #bfdbfe; color: #1e40af; }
.chip-yellow  { background: #fef9c3; border-color: #fde68a; color: #854d0e; }
.chip-purple  { background: #f3e8ff; border-color: #e9d5ff; color: #6b21a8; }
.chip-green   { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
.chip-neutral { background: #f3f4f6; border-color: #e5e7eb; color: #6b7280; }
.chip-red     { background: #fee2e2; border-color: #fecaca; color: #991b1b; }

.slot-plus { color: #d1d5db; text-align: center; padding: 4px; font-size: 14px; user-select: none; }

/* ── Queue items ──────────────────────────────────── */
.qi { display: flex; align-items: flex-start; gap: 10px; padding: 10px 16px; border-bottom: 1px solid var(--border); cursor: pointer; }
.qi:hover { background: var(--bg-soft); }

/* ── Modals ───────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 16px;
}
.modal {
  background: #fff; border-radius: 14px; width: 520px;
  max-width: 100%; max-height: 92vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.modal--sm { width: 420px; }
.modal--lg { width: 680px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title  { flex: 1; font: 700 15px var(--font-sans); color: var(--fg1); margin: 0; }
.modal__close  { width: 28px; height: 28px; border: 0; background: var(--bg-muted); border-radius: 6px; cursor: pointer; font-size: 12px; color: var(--fg2); display: grid; place-items: center; flex-shrink: 0; }
.modal__body   { padding: 20px; }
.modal__footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
.modal-section-title { font: 700 11px var(--font-sans); letter-spacing: .06em; text-transform: uppercase; color: var(--fg3); margin-bottom: 10px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field__label { font: 600 11px var(--font-sans); color: var(--fg2); }
.field__error { font: 500 11px var(--font-sans); color: var(--brand-red); }

/* Patient dropdown */
.pdrop {
  position: absolute; top: 100%; left: 0; right: 0; z-index: 10;
  background: #fff; border: 1px solid var(--border); border-radius: 8px;
  box-shadow: var(--shadow-md); overflow: hidden; margin-top: 2px;
}
.popt {
  display: flex; justify-content: space-between; align-items: center;
  width: 100%; background: none; border: none; padding: 8px 12px;
  cursor: pointer; border-bottom: 1px solid var(--bg-soft); text-align: left;
}
.popt:last-child { border-bottom: none; }
.popt:hover { background: var(--bg-soft); }

/* ── Drawer ───────────────────────────────────────── */
.drawer-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.35); display: flex; justify-content: flex-end; z-index: 9999; }
.drawer { width: 420px; max-width: 100%; background: #fff; height: 100%; overflow-y: auto; box-shadow: -8px 0 32px rgba(15,23,42,.12); display: flex; flex-direction: column; }
.drawer__header { display: flex; align-items: flex-start; gap: 12px; padding: 20px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: #fff; z-index: 1; }
.drawer__name { font: 700 16px var(--font-sans); color: var(--fg1); margin: 0 0 4px; }
.drawer__body { padding: 18px 20px; flex: 1; }
.drow-section-title { font: 700 10.5px var(--font-sans); letter-spacing: .06em; text-transform: uppercase; color: var(--fg3); margin-bottom: 10px; }
.info-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 4px; }
.info-row   { display: flex; flex-direction: column; gap: 3px; }
.info-label { font: 600 10.5px var(--font-sans); letter-spacing: .04em; text-transform: uppercase; color: var(--fg3); }
.info-val   { font: 500 13px var(--font-sans); color: var(--fg1); }
.info-val.mono { font-family: var(--font-mono); font-size: 12px; }
</style>
