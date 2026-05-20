<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

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

const flash = computed(() => usePage().props.flash)

/* ─── Week navigation ─────────────────────────────── */
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

/* ─── Slot helpers ───────────────────────────────── */
const STATUS_COLORS = {
    confirmed: 'bg-blue-chip',
    waiting:   'bg-yellow-chip',
    in_room:   'bg-purple-chip',
    done:      'bg-green-chip',
    cancelled: 'bg-gray-chip',
    no_show:   'bg-red-chip',
}
const STATUS_LABELS = {
    confirmed: 'Disahkan',
    waiting:   'Menunggu',
    in_room:   'Dalam Bilik',
    done:      'Selesai',
    cancelled: 'Batal',
    no_show:   'Tidak Hadir',
}
const TYPE_LABELS = {
    new:            'Pesakit Baru',
    follow_up:      'Susulan',
    annual_checkup: 'Semakan Tahunan',
    procedure:      'Prosedur',
    antenatal:      'Antenatal',
    teleconsult:    'Teleperubatan',
}

function getAppt(date, time) {
    return props.weekMap?.[date]?.[time] ?? null
}

/* ─── View drawer ────────────────────────────────── */
const viewAppt = ref(null)
function openView(appt) { viewAppt.value = appt }
function closeView() { viewAppt.value = null }

/* ─── Status update ─────────────────────────────── */
function updateStatus(appt, status) {
    router.patch(`/appointments/${appt.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => {
            if (viewAppt.value?.id === appt.id) viewAppt.value = { ...viewAppt.value, status }
        },
    })
}

/* ─── Create / Edit modal ───────────────────────── */
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
    editTarget.value = null
    form.reset()
    form.appointment_date = date ?? props.today
    form.appointment_time = time ?? '08:00'
    form.doctor_name      = 'Dr. Aiman Rashid'
    form.duration_minutes = 30
    form.type             = 'follow_up'
    form.status           = 'confirmed'
    showModal.value       = true
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
        form.put(`/appointments/${editTarget.value.id}`, {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    } else {
        form.post('/appointments', {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    }
}

/* ─── Delete ─────────────────────────────────────── */
function deleteAppt(appt) {
    if (! confirm(`Padam temujanji ${appt.patient_name}?`)) return
    router.delete(`/appointments/${appt.id}`, {
        preserveScroll: true,
        onSuccess: () => { viewAppt.value = null },
    })
}

/* ─── Patient search ─────────────────────────────── */
const patientSearch = ref('')
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

/* ─── Week label ─────────────────────────────────── */
const weekLabel = computed(() => {
    const s = new Date(props.weekStart)
    const e = new Date(props.weekEnd)
    const fmt = d => d.toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' })
    return `${fmt(s)} – ${fmt(e)}`
})
</script>

<template>
    <div class="appt-page">

        <!-- Flash -->
        <div v-if="flash?.success" class="flash-bar">{{ flash.success }}</div>

        <!-- ─── Header ───────────────────────────────── -->
        <div class="page-hd">
            <div>
                <h1 class="page-ttl">Temujanji</h1>
                <p class="page-sub">Jadual mingguan & senarai hari ini</p>
            </div>
            <button class="btn-primary" @click="openCreate()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Temujanji Baru
            </button>
        </div>

        <!-- ─── Stats ────────────────────────────────── -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-num">{{ stats.total }}</div>
                <div class="stat-lbl">Jumlah Minggu Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-num blue">{{ stats.confirmed }}</div>
                <div class="stat-lbl">Disahkan</div>
            </div>
            <div class="stat-card">
                <div class="stat-num green">{{ stats.done }}</div>
                <div class="stat-lbl">Selesai</div>
            </div>
            <div class="stat-card">
                <div class="stat-num muted">{{ stats.cancelled }}</div>
                <div class="stat-lbl">Dibatalkan</div>
            </div>
        </div>

        <!-- ─── Main layout ──────────────────────────── -->
        <div class="main-layout">

            <!-- ─── Calendar panel ──────────────────── -->
            <div class="cal-panel">
                <!-- Week nav -->
                <div class="week-nav">
                    <button class="nav-btn" @click="goWeek(-1)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div class="week-info">
                        <span class="week-lbl">{{ weekLabel }}</span>
                        <button v-if="!isCurrentWeek" class="today-lnk" @click="goToday">Hari Ini</button>
                    </div>
                    <button class="nav-btn" @click="goWeek(1)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>

                <!-- Grid -->
                <div class="grid-wrap">
                    <table class="cal-tbl">
                        <thead>
                            <tr>
                                <th class="th-time">Masa</th>
                                <th v-for="day in weekDates" :key="day.date"
                                    :class="['th-day', day.is_today && 'th-today']">
                                    <div class="day-name">{{ day.label }}</div>
                                    <div class="day-cnt">{{ day.count }} temujanji</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="slot in slots" :key="slot">
                                <td class="td-time">{{ slot }}</td>
                                <td v-for="day in weekDates" :key="day.date"
                                    :class="['td-slot', day.is_today && 'td-today']"
                                    @click="!getAppt(day.date, slot) && openCreate(day.date, slot)">
                                    <div v-if="getAppt(day.date, slot)"
                                         class="appt-chip"
                                         :class="getAppt(day.date, slot).status"
                                         @click.stop="openView(getAppt(day.date, slot))">
                                        <div class="chip-name">{{ getAppt(day.date, slot).patient_name }}</div>
                                        <div class="chip-type">{{ TYPE_LABELS[getAppt(day.date, slot).type] }}</div>
                                    </div>
                                    <div v-else class="slot-plus">+</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ─── Today queue ──────────────────────── -->
            <div class="queue-panel">
                <div class="queue-hd">
                    <h2 class="queue-ttl">Senarai Hari Ini</h2>
                    <span class="queue-cnt">{{ todayList.length }}</span>
                </div>
                <div v-if="todayList.length === 0" class="queue-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span>Tiada temujanji hari ini</span>
                </div>
                <div v-else class="queue-list">
                    <div v-for="a in todayList" :key="a.id" class="qi" @click="openView(a)">
                        <div class="qi-time">{{ a.appointment_time }}</div>
                        <div class="qi-body">
                            <div class="qi-name">{{ a.patient_name }}</div>
                            <div class="qi-meta">{{ TYPE_LABELS[a.type] }}</div>
                            <div v-if="a.reason" class="qi-reason">{{ a.reason }}</div>
                        </div>
                        <span class="chip-badge" :class="a.status">{{ STATUS_LABELS[a.status] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── View drawer ──────────────────────────── -->
        <Teleport to="body">
            <div v-if="viewAppt" class="overlay" @click.self="closeView">
                <div class="drawer">
                    <div class="drawer-hd">
                        <div>
                            <h3 class="drawer-ttl">{{ viewAppt.patient_name }}</h3>
                            <p class="drawer-sub">{{ viewAppt.patient_ic }}</p>
                        </div>
                        <button class="icon-btn" @click="closeView">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <div class="drawer-body">
                        <div class="info-box">
                            <div class="ir"><span class="il">Tarikh</span><span class="iv">{{ viewAppt.appointment_date }}</span></div>
                            <div class="ir"><span class="il">Masa</span><span class="iv">{{ viewAppt.appointment_time }} ({{ viewAppt.duration_minutes }} min)</span></div>
                            <div class="ir"><span class="il">Jenis</span><span class="iv">{{ TYPE_LABELS[viewAppt.type] }}</span></div>
                            <div class="ir"><span class="il">Doktor</span><span class="iv">{{ viewAppt.doctor_name }}</span></div>
                            <div class="ir">
                                <span class="il">Status</span>
                                <span class="chip-badge" :class="viewAppt.status">{{ STATUS_LABELS[viewAppt.status] }}</span>
                            </div>
                            <div v-if="viewAppt.reason" class="ir"><span class="il">Sebab</span><span class="iv">{{ viewAppt.reason }}</span></div>
                            <div v-if="viewAppt.patient_allergies" class="ir">
                                <span class="il">Alahan</span>
                                <span class="iv allergy">{{ viewAppt.patient_allergies }}</span>
                            </div>
                            <div v-if="viewAppt.notes" class="ir full">
                                <span class="il">Nota</span>
                                <span class="iv">{{ viewAppt.notes }}</span>
                            </div>
                        </div>

                        <!-- Status flow buttons -->
                        <div v-if="!['done','cancelled','no_show'].includes(viewAppt.status)" class="status-flow">
                            <p class="flow-lbl">Kemaskini Status</p>
                            <div class="flow-btns">
                                <button v-if="viewAppt.status === 'confirmed'"
                                        class="fbtn yellow" @click="updateStatus(viewAppt, 'waiting')">
                                    Pesakit Tiba
                                </button>
                                <button v-if="viewAppt.status === 'waiting'"
                                        class="fbtn purple" @click="updateStatus(viewAppt, 'in_room')">
                                    Masuk Bilik
                                </button>
                                <button v-if="viewAppt.status === 'in_room'"
                                        class="fbtn green" @click="updateStatus(viewAppt, 'done')">
                                    Selesai
                                </button>
                                <button class="fbtn dimgray" @click="updateStatus(viewAppt, 'cancelled')">
                                    Batalkan
                                </button>
                                <button class="fbtn red" @click="updateStatus(viewAppt, 'no_show')">
                                    Tidak Hadir
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="drawer-ft">
                        <button class="btn-outline" @click="openEdit(viewAppt)">Edit</button>
                        <button class="btn-danger" @click="deleteAppt(viewAppt)">Padam</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── Create/Edit modal ────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="overlay" @click.self="closeModal">
                <div class="modal">
                    <div class="modal-hd">
                        <h3 class="modal-ttl">{{ editTarget ? 'Edit Temujanji' : 'Temujanji Baru' }}</h3>
                        <button class="icon-btn" @click="closeModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitForm" class="modal-body">
                        <!-- Patient search -->
                        <div class="frow">
                            <label class="flbl">Pesakit *</label>
                            <div class="psearch">
                                <input v-model="patientSearch" type="text" class="finput"
                                       placeholder="Cari nama atau nombor IC..."
                                       @input="form.patient_id = ''" autocomplete="off" />
                                <div v-if="patientResults.length" class="pdrop">
                                    <button v-for="p in patientResults" :key="p.id"
                                            type="button" class="popt"
                                            @click="selectPatient(p)">
                                        <span class="po-name">{{ p.name }}</span>
                                        <span class="po-ic">{{ p.ic_number }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="form.errors.patient_id" class="ferr">{{ form.errors.patient_id }}</p>
                        </div>

                        <div class="f2col">
                            <div class="frow">
                                <label class="flbl">Tarikh *</label>
                                <input v-model="form.appointment_date" type="date" class="finput" required />
                            </div>
                            <div class="frow">
                                <label class="flbl">Masa *</label>
                                <select v-model="form.appointment_time" class="finput" required>
                                    <option v-for="s in slots" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="f2col">
                            <div class="frow">
                                <label class="flbl">Jenis</label>
                                <select v-model="form.type" class="finput">
                                    <option v-for="(lbl, val) in TYPE_LABELS" :key="val" :value="val">{{ lbl }}</option>
                                </select>
                            </div>
                            <div class="frow">
                                <label class="flbl">Tempoh</label>
                                <select v-model="form.duration_minutes" class="finput">
                                    <option :value="15">15 minit</option>
                                    <option :value="30">30 minit</option>
                                    <option :value="45">45 minit</option>
                                    <option :value="60">60 minit</option>
                                </select>
                            </div>
                        </div>

                        <div class="frow">
                            <label class="flbl">Doktor</label>
                            <input v-model="form.doctor_name" type="text" class="finput" />
                        </div>

                        <div v-if="editTarget" class="frow">
                            <label class="flbl">Status</label>
                            <select v-model="form.status" class="finput">
                                <option v-for="(lbl, val) in STATUS_LABELS" :key="val" :value="val">{{ lbl }}</option>
                            </select>
                        </div>

                        <div class="frow">
                            <label class="flbl">Sebab / Aduan</label>
                            <input v-model="form.reason" type="text" class="finput"
                                   placeholder="cth: Kawalan tekanan darah" maxlength="255" />
                        </div>

                        <div class="frow">
                            <label class="flbl">Nota Tambahan</label>
                            <textarea v-model="form.notes" class="finput" rows="3"
                                      placeholder="Nota dalaman..." maxlength="1000"></textarea>
                        </div>

                        <div class="modal-ft">
                            <button type="button" class="btn-outline" @click="closeModal">Batal</button>
                            <button type="submit" class="btn-primary"
                                    :disabled="form.processing || !form.patient_id">
                                {{ form.processing ? 'Menyimpan...' : (editTarget ? 'Kemaskini' : 'Simpan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </div>
</template>

<style scoped>
.appt-page { display:flex; flex-direction:column; gap:20px; padding:24px; height:100%; overflow-y:auto; }

.flash-bar { background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:.875rem; }

/* Header */
.page-hd  { display:flex; justify-content:space-between; align-items:flex-start; }
.page-ttl { font-size:1.5rem; font-weight:700; color:var(--fg1); }
.page-sub { font-size:.875rem; color:var(--fg3); margin-top:2px; }
.btn-primary { display:flex; align-items:center; gap:6px; background:var(--brand-green); color:#fff; border:none; padding:9px 16px; border-radius:8px; font-size:.875rem; font-weight:600; cursor:pointer; }
.btn-primary:hover { filter:brightness(1.08); }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; }

/* Stats */
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:16px; text-align:center; }
.stat-num  { font-size:1.5rem; font-weight:700; color:var(--fg1); }
.stat-num.blue  { color:#2563eb; }
.stat-num.green { color:#16a34a; }
.stat-num.muted { color:#9ca3af; }
.stat-lbl  { font-size:.75rem; color:var(--fg3); margin-top:2px; }

/* Main */
.main-layout { display:grid; grid-template-columns:1fr 280px; gap:16px; min-height:0; flex:1; }

/* Calendar */
.cal-panel  { background:var(--surface); border:1px solid var(--border); border-radius:12px; display:flex; flex-direction:column; overflow:hidden; }
.week-nav   { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid var(--border); }
.nav-btn    { background:none; border:1px solid var(--border); border-radius:6px; padding:5px 8px; cursor:pointer; display:flex; align-items:center; color:var(--fg2); }
.nav-btn:hover { background:#f5f5f5; }
.week-info  { display:flex; align-items:center; gap:10px; }
.week-lbl   { font-weight:600; color:var(--fg1); font-size:.9rem; }
.today-lnk  { background:none; border:1px solid var(--brand-green); color:var(--brand-green); border-radius:6px; padding:3px 10px; font-size:.75rem; font-weight:600; cursor:pointer; }

.grid-wrap  { overflow:auto; flex:1; }
.cal-tbl    { width:100%; border-collapse:collapse; min-width:700px; }
.cal-tbl thead th { background:var(--surface); position:sticky; top:0; z-index:2; padding:8px 10px; font-size:.75rem; font-weight:600; color:var(--fg2); text-align:center; border-bottom:1px solid var(--border); }
.th-time    { width:58px; text-align:left !important; }
.th-today   { background:#f0fdf4 !important; }
.day-name   { font-weight:700; color:var(--fg1); }
.day-cnt    { font-size:.7rem; color:var(--fg3); font-weight:400; }

.td-time    { padding:6px 8px; font-size:.75rem; color:var(--fg3); font-weight:600; vertical-align:top; white-space:nowrap; border-bottom:1px solid var(--border); }
.td-slot    { padding:4px 6px; vertical-align:top; border-bottom:1px solid var(--border); border-left:1px solid var(--border); cursor:pointer; min-width:100px; }
.td-slot:hover { background:#f9fafb; }
.td-today   { background:#fafffe; }

/* Appointment chips */
.appt-chip  { border:1px solid; border-radius:6px; padding:4px 7px; cursor:pointer; font-size:.7rem; line-height:1.4; }
.appt-chip:hover { filter:brightness(.95); }
.chip-name  { font-weight:600; }
.chip-type  { opacity:.75; }
.slot-plus  { color:#d1d5db; font-size:.9rem; text-align:center; padding:4px; line-height:1; user-select:none; }

/* Status colors — chip & badge share these */
.confirmed { background:#dbeafe; color:#1e40af; border-color:#bfdbfe; }
.waiting   { background:#fef9c3; color:#854d0e; border-color:#fde68a; }
.in_room   { background:#f3e8ff; color:#6b21a8; border-color:#e9d5ff; }
.done      { background:#dcfce7; color:#166534; border-color:#bbf7d0; }
.cancelled { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
.no_show   { background:#fee2e2; color:#991b1b; border-color:#fecaca; }

.chip-badge { font-size:.65rem; font-weight:700; padding:2px 6px; border-radius:20px; border:1px solid; white-space:nowrap; flex-shrink:0; }

/* Queue */
.queue-panel { background:var(--surface); border:1px solid var(--border); border-radius:12px; display:flex; flex-direction:column; overflow:hidden; }
.queue-hd    { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid var(--border); }
.queue-ttl   { font-weight:700; color:var(--fg1); font-size:.95rem; }
.queue-cnt   { background:var(--brand-green); color:#fff; font-size:.75rem; font-weight:700; padding:1px 8px; border-radius:20px; }
.queue-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:32px; color:var(--fg3); font-size:.875rem; text-align:center; }
.queue-list  { flex:1; overflow-y:auto; }
.qi          { display:flex; align-items:flex-start; gap:10px; padding:10px 14px; border-bottom:1px solid var(--border); cursor:pointer; }
.qi:hover    { background:#f9f9f9; }
.qi-time     { font-size:.75rem; font-weight:700; color:var(--brand-green); width:38px; flex-shrink:0; padding-top:2px; }
.qi-body     { flex:1; min-width:0; }
.qi-name     { font-weight:600; font-size:.82rem; color:var(--fg1); }
.qi-meta     { font-size:.72rem; color:var(--fg3); }
.qi-reason   { font-size:.72rem; color:var(--fg2); margin-top:2px; }

/* Overlay/Drawer/Modal */
.overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:50; display:flex; align-items:center; justify-content:center; }
.drawer  { position:fixed; right:0; top:0; bottom:0; width:380px; max-width:100%; background:#fff; display:flex; flex-direction:column; box-shadow:-4px 0 24px rgba(0,0,0,.15); z-index:51; }
.drawer-hd { display:flex; align-items:flex-start; justify-content:space-between; padding:20px; border-bottom:1px solid var(--border); }
.drawer-ttl { font-size:1.1rem; font-weight:700; color:var(--fg1); }
.drawer-sub { font-size:.8rem; color:var(--fg3); margin-top:2px; }
.drawer-body { flex:1; overflow-y:auto; padding:20px; }
.drawer-ft  { padding:16px 20px; border-top:1px solid var(--border); display:flex; gap:8px; }
.icon-btn   { background:none; border:none; cursor:pointer; padding:4px; color:var(--fg3); border-radius:6px; }
.icon-btn:hover { background:#f5f5f5; }

.info-box   { border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:20px; }
.ir         { display:flex; gap:12px; padding:9px 12px; border-bottom:1px solid var(--border); font-size:.82rem; }
.ir:last-child { border-bottom:none; }
.ir.full    { flex-direction:column; }
.il         { color:var(--fg3); width:70px; flex-shrink:0; }
.iv         { color:var(--fg1); font-weight:500; }
.iv.allergy { color:#dc2626; }

.status-flow { }
.flow-lbl   { font-size:.75rem; font-weight:600; color:var(--fg3); margin-bottom:8px; text-transform:uppercase; letter-spacing:.05em; }
.flow-btns  { display:flex; flex-wrap:wrap; gap:6px; }
.fbtn       { border:none; border-radius:6px; padding:6px 12px; font-size:.8rem; font-weight:600; cursor:pointer; }
.fbtn.yellow  { background:#fef9c3; color:#854d0e; }
.fbtn.purple  { background:#f3e8ff; color:#6b21a8; }
.fbtn.green   { background:#dcfce7; color:#166534; }
.fbtn.dimgray { background:#f3f4f6; color:#4b5563; }
.fbtn.red     { background:#fee2e2; color:#991b1b; }
.fbtn:hover   { filter:brightness(.94); }

.btn-outline { flex:1; background:none; border:1px solid var(--border); color:var(--fg2); padding:8px; border-radius:8px; cursor:pointer; font-size:.875rem; font-weight:600; }
.btn-outline:hover { background:#f5f5f5; }
.btn-danger  { flex:1; background:#fee2e2; border:none; color:#991b1b; padding:8px; border-radius:8px; cursor:pointer; font-size:.875rem; font-weight:600; }
.btn-danger:hover { background:#fecaca; }

/* Modal */
.modal     { background:#fff; border-radius:14px; width:100%; max-width:560px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.2); z-index:51; }
.modal-hd  { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid var(--border); }
.modal-ttl { font-size:1rem; font-weight:700; color:var(--fg1); }
.modal-body { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:14px; }
.modal-ft  { display:flex; gap:8px; justify-content:flex-end; padding:14px 20px; border-top:1px solid var(--border); }

.frow  { display:flex; flex-direction:column; gap:4px; }
.f2col { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.flbl  { font-size:.8rem; font-weight:600; color:var(--fg2); }
.finput { border:1px solid var(--border); border-radius:8px; padding:8px 10px; font-size:.875rem; color:var(--fg1); background:#fff; outline:none; width:100%; box-sizing:border-box; }
.finput:focus { border-color:var(--brand-green); box-shadow:0 0 0 3px rgba(34,197,94,.12); }
textarea.finput { resize:vertical; }
.ferr  { font-size:.75rem; color:#dc2626; }

.psearch  { position:relative; }
.pdrop    { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid var(--border); border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:10; overflow:hidden; margin-top:2px; }
.popt     { display:flex; justify-content:space-between; align-items:center; width:100%; background:none; border:none; padding:8px 12px; cursor:pointer; border-bottom:1px solid #f3f4f6; text-align:left; }
.popt:last-child { border-bottom:none; }
.popt:hover { background:#f9fafb; }
.po-name  { font-size:.85rem; font-weight:600; color:var(--fg1); }
.po-ic    { font-size:.75rem; color:var(--fg3); }
</style>
