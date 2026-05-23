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
  visits:       Object,
  selected:     Object,
  patients:     Array,
  filters:      Object,
  today:        String,
  lookups:      { type: Object, default: () => ({}) },
  drugItems:    { type: Array,  default: () => [] },
})

const flash = computed(() => usePage().props.flash?.success)
const { t } = useLocale()

/* ── Visit list navigation ────────────────────────── */
const search     = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? '')
let searchTimer  = null

function applyFilter() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get('/emr', {
      search: search.value || undefined,
      status: statusFilter.value || undefined,
      visit:  props.filters?.visit,
    }, { preserveState: true, replace: true })
  }, 350)
}

watch(search,       applyFilter)
watch(statusFilter, applyFilter)

function openVisit(id) {
  router.get('/emr', {
    visit:  id,
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true })
}

/* ── New visit modal ──────────────────────────────── */
const showNewModal   = ref(false)
const patientSearch  = ref('')
const patientResults = computed(() => {
  if (patientSearch.value.length < 2) return []
  const q = patientSearch.value.toLowerCase()
  return (props.patients ?? []).filter(p =>
    p.name.toLowerCase().includes(q) || p.ic_number.includes(q)
  ).slice(0, 6)
})

const newForm = useForm({
  patient_id:      '',
  doctor_name:     'Dr. Aiman Rashid',
  visit_date:      props.today,
  chief_complaint: '',
})

function selectNewPatient(p) {
  newForm.patient_id  = p.id
  patientSearch.value = `${p.name} (${p.ic_number})`
}
function openNewModal() {
  newForm.reset()
  newForm.visit_date   = props.today
  newForm.doctor_name  = 'Dr. Aiman Rashid'
  patientSearch.value  = ''
  showNewModal.value   = true
}
function submitNewVisit() {
  newForm.post('/emr', { onSuccess: () => { showNewModal.value = false } })
}

/* ── SOAP editor ─────────────────────────────────── */
const soapTab   = ref('S')
const soapDirty = ref(false)
const soapForm  = useForm({ soap_s: '', soap_o: '', soap_a: '', soap_p: '' })

watch(() => props.selected, (v) => {
  if (v) {
    soapForm.soap_s = v.soap_s ?? ''
    soapForm.soap_o = v.soap_o ?? ''
    soapForm.soap_a = v.soap_a ?? ''
    soapForm.soap_p = v.soap_p ?? ''
    soapDirty.value = false
  }
}, { immediate: true })

function saveSoap() {
  soapForm.patch(`/emr/${props.selected.id}/soap`, {
    preserveScroll: true,
    onSuccess: () => { soapDirty.value = false },
  })
}

/* ── Vitals ───────────────────────────────────────── */
const editVitals = ref(false)
const vitalsForm = useForm({
  bp_systolic: '', bp_diastolic: '', heart_rate: '',
  temperature: '', spo2: '', weight: '', height: '',
})

watch(() => props.selected?.vitals, (v) => {
  if (v) {
    vitalsForm.bp_systolic  = v.bp_systolic  ?? ''
    vitalsForm.bp_diastolic = v.bp_diastolic ?? ''
    vitalsForm.heart_rate   = v.heart_rate   ?? ''
    vitalsForm.temperature  = v.temperature  ?? ''
    vitalsForm.spo2         = v.spo2         ?? ''
    vitalsForm.weight       = v.weight       ?? ''
    vitalsForm.height       = v.height       ?? ''
  }
  editVitals.value = false
}, { immediate: true })

function saveVitals() {
  vitalsForm.post(`/emr/${props.selected.id}/vitals`, {
    preserveScroll: true,
    onSuccess: () => { editVitals.value = false },
  })
}

function bpAbnormal(sys, dia) { return sys > 140 || dia > 90 }
function hrAbnormal(hr)       { return hr < 60 || hr > 100 }
function tempAbnormal(t)      { return t < 36.1 || t > 37.5 }
function spo2Abnormal(s)      { return s < 95 }

/* ── Diagnosis ────────────────────────────────────── */
const showDxForm = ref(false)
const dxForm     = useForm({ icd_code: '', description: '', type: 'primary' })

const QUICK_ICD = [
  { code: 'J06.9', desc: 'Jangkitan saluran pernafasan atas akut' },
  { code: 'J02.9', desc: 'Faringitis akut' },
  { code: 'J00',   desc: 'Nasopharyngitis akut (selesema)' },
  { code: 'K21.0', desc: 'GERD dengan esofagitis' },
  { code: 'I10',   desc: 'Hipertensi penting' },
  { code: 'E11.9', desc: 'Diabetes Mellitus Jenis 2' },
  { code: 'J45.9', desc: 'Asma, tidak spesifik' },
  { code: 'E78.5', desc: 'Hiperlipidemia' },
  { code: 'N39.0', desc: 'Jangkitan saluran kencing' },
  { code: 'R50.9', desc: 'Demam, tidak spesifik' },
  { code: 'R05',   desc: 'Batuk' },
  { code: 'Z34.2', desc: 'Pengawasan kehamilan trimester kedua' },
]

function pickIcd(item) {
  dxForm.icd_code    = item.code
  dxForm.description = item.desc
}
function addDiagnosis() {
  dxForm.post(`/emr/${props.selected.id}/diagnoses`, {
    preserveScroll: true,
    onSuccess: () => { dxForm.reset(); dxForm.type = 'primary'; showDxForm.value = false },
  })
}
function removeDiagnosis(dxId) {
  router.delete(`/emr/${props.selected.id}/diagnoses/${dxId}`, { preserveScroll: true })
}

/* ── Medical Certificate ──────────────────────────── */
const showMcForm    = ref(false)
const showMcDelId   = ref(null)
const mcForm        = useForm({
  start_date: props.today,
  days:       1,
  diagnosis:  '',
  notes:      '',
})

function issueMc() {
  mcForm.post(`/emr/${props.selected.id}/mc`, {
    preserveScroll: true,
    onSuccess: () => { mcForm.reset(); mcForm.start_date = props.today; mcForm.days = 1; showMcForm.value = false },
  })
}
function deleteMc(mcId) {
  router.delete(`/mc/${mcId}`, { preserveScroll: true, onSuccess: () => { showMcDelId.value = null } })
}

/* ── Time Slip ────────────────────────────────────── */
const showTsForm  = ref(false)
const showTsDelId = ref(null)
const tsForm      = useForm({
  arrival_time:   '',
  departure_time: '',
  purpose:        '',
  notes:          '',
})

function issueTsSlip() {
  tsForm.post(`/emr/${props.selected.id}/timeslip`, {
    preserveScroll: true,
    onSuccess: () => { tsForm.reset(); showTsForm.value = false },
  })
}
function deleteTs(tsId) {
  router.delete(`/timeslip/${tsId}`, { preserveScroll: true, onSuccess: () => { showTsDelId.value = null } })
}

/* ── Referral Letter ──────────────────────────────── */
const showRefForm  = ref(false)
const showRefDelId = ref(null)
const refForm      = useForm({
  referred_to:      '',
  referred_to_dept: '',
  urgency:          'routine',
  reason:           '',
  clinical_summary: '',
  relevant_history: '',
})

function issueReferral() {
  refForm.post(`/emr/${props.selected.id}/referral`, {
    preserveScroll: true,
    onSuccess: () => { refForm.reset(); refForm.urgency = 'routine'; showRefForm.value = false },
  })
}
function deleteReferral(refId) {
  router.delete(`/referral/${refId}`, { preserveScroll: true, onSuccess: () => { showRefDelId.value = null } })
}

/* ── Quarantine Letter ────────────────────────────── */
const showQnForm  = ref(false)
const showQnDelId = ref(null)
const qnForm      = useForm({
  quarantine_start: props.today,
  days:             7,
  diagnosis:        '',
  reason:           '',
  notes:            '',
})

function issueQuarantine() {
  qnForm.post(`/emr/${props.selected.id}/quarantine`, {
    preserveScroll: true,
    onSuccess: () => { qnForm.reset(); qnForm.quarantine_start = props.today; qnForm.days = 7; showQnForm.value = false },
  })
}
function deleteQuarantine(qnId) {
  router.delete(`/quarantine/${qnId}`, { preserveScroll: true, onSuccess: () => { showQnDelId.value = null } })
}

/* ── Prescription from EMR ───────────────────────── */
const showRxModal   = ref(false)
const rxDrugSearch  = ref([])
const rxDrugOpen    = ref([])

function emptyRxItem() {
  return { inventory_item_id: null, drug_name: '', drug_unit: '', dosage: '', frequency: '', duration: '', quantity: 1, instructions: '' }
}

const rxForm = useForm({ notes: '', items: [emptyRxItem()] })

function openRxModal() {
  rxForm.reset()
  rxForm.items  = [emptyRxItem()]
  rxDrugSearch.value = ['']
  rxDrugOpen.value   = [false]
  showRxModal.value  = true
}
function addRxItem() {
  rxForm.items.push(emptyRxItem())
  rxDrugSearch.value.push('')
  rxDrugOpen.value.push(false)
}
function removeRxItem(i) {
  rxForm.items.splice(i, 1)
  rxDrugSearch.value.splice(i, 1)
  rxDrugOpen.value.splice(i, 1)
}

function filteredRxDrugs(i) {
  const q = (rxDrugSearch.value[i] ?? '').toLowerCase()
  if (!q) return props.drugItems.slice(0, 10)
  return props.drugItems.filter(d =>
    d.name.toLowerCase().includes(q) || (d.generic_name ?? '').toLowerCase().includes(q)
  ).slice(0, 10)
}
function selectRxDrug(i, inv) {
  rxForm.items[i].inventory_item_id = inv.id
  rxForm.items[i].drug_name         = inv.name
  rxForm.items[i].drug_unit         = inv.form ?? ''
  rxDrugSearch.value[i]             = inv.name
  rxDrugOpen.value[i]               = false
}
function clearRxDrug(i) {
  rxForm.items[i].inventory_item_id = null
  rxForm.items[i].drug_name         = ''
  rxDrugSearch.value[i]             = ''
}
function resolvedRxInv(item) {
  return item.inventory_item_id
    ? props.drugItems.find(d => d.id === item.inventory_item_id) ?? null
    : null
}

const RX_FREQS = computed(() => (props.lookups?.kekerapan_dos ?? []).map(v => v.label_ms))
const RX_INSTRS = computed(() => (props.lookups?.arahan_dos   ?? []).map(v => v.label_ms))

const RX_STATUS_TONE  = { pending:'orange', verifying:'blue', ready:'green', dispensed:'neutral', cancelled:'neutral' }
const RX_STATUS_LABEL = { pending:'Menunggu', verifying:'Sedang Disemak', ready:'Sedia', dispensed:'Telah Diambil', cancelled:'Dibatalkan' }

function submitRx() {
  rxForm.post(`/emr/${props.selected.id}/prescription`, {
    preserveScroll: true,
    onSuccess: () => { showRxModal.value = false },
  })
}

/* ── Close / Delete visit ─────────────────────────── */
const showCloseConfirm  = ref(false)
const showDeleteConfirm = ref(false)

function closeVisit() {
  router.patch(`/emr/${props.selected.id}/close`, {}, { preserveScroll: true, onSuccess: () => { showCloseConfirm.value = false } })
}
function deleteVisit() {
  router.delete(`/emr/${props.selected.id}`, { onSuccess: () => { showDeleteConfirm.value = false } })
}

/* ── Helpers ─────────────────────────────────────── */
const SOAP_LABELS = { S: 'Subjective', O: 'Objective', A: 'Assessment', P: 'Plan' }
const SOAP_FIELDS = { S: 'soap_s', O: 'soap_o', A: 'soap_a', P: 'soap_p' }
const soapHints = computed(() => ({
  S: t('emr_soap_hint_s'),
  O: t('emr_soap_hint_o'),
  A: t('emr_soap_hint_a'),
  P: t('emr_soap_hint_p'),
}))
</script>

<template>
  <div class="emr-root">

    <!-- ── Left: Visit list ──────────────────────────── -->
    <div class="visit-list">
      <div class="vl-header">
        <div style="font:700 14px var(--font-sans);color:var(--fg1)">{{ t('emr_records') }}</div>
        <Btn variant="primary" size="sm" @click="openNewModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          {{ t('emr_new_btn') }}
        </Btn>
      </div>

      <div style="padding:10px 12px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:6px">
        <input v-model="search" class="input" style="font-size:12px;padding:6px 10px"
               :placeholder="t('emr_search_ph')" />
        <div style="display:flex;gap:4px">
          <button v-for="f in [['', t('emr_filter_all')],['open', t('emr_filter_open')],['closed', t('emr_filter_closed')]]" :key="f[0]"
                  :class="['status-chip', statusFilter === f[0] ? 'active' : '']"
                  @click="statusFilter = f[0]">{{ f[1] }}</button>
        </div>
      </div>

      <div style="flex:1;overflow-y:auto">
        <div v-if="!visits?.data?.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 12px var(--font-sans)">
          {{ t('emr_no_records') }}
        </div>
        <div v-for="v in visits?.data" :key="v.id"
             :class="['vl-item', selected?.id === v.id ? 'active' : '']"
             @click="openVisit(v.id)">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
            <span style="font:600 12.5px var(--font-sans);color:var(--fg1);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ v.patient_name }}</span>
            <Badge :tone="v.status === 'open' ? 'yellow' : 'green'" style="font-size:9px;padding:1px 5px">{{ v.status === 'open' ? t('emr_filter_open') : t('emr_filter_closed') }}</Badge>
          </div>
          <div style="font:500 10.5px var(--font-mono);color:var(--fg3)">{{ v.patient_ic }} · {{ v.visit_date }}</div>
          <div v-if="v.chief_complaint" style="font:400 11px var(--font-sans);color:var(--fg2);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ v.chief_complaint }}</div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="visits?.last_page > 1" class="pagination" style="border-top:1px solid var(--border)">
        <button v-for="link in visits.links" :key="link.label"
                :disabled="!link.url"
                :class="['page-btn', link.active ? 'active':'']"
                @click="link.url && router.get(link.url, {search: search||undefined, status: statusFilter||undefined}, {preserveState:true})"
                v-html="link.label" />
      </div>
    </div>

    <!-- ── Right: Detail or empty state ─────────────── -->
    <div class="visit-detail">

      <!-- Empty state -->
      <div v-if="!selected" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color:var(--border)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        <div style="font:600 14px var(--font-sans);color:var(--fg2);margin-top:12px">{{ t('emr_select_record') }}</div>
        <div style="font:400 12px var(--font-sans);color:var(--fg3);margin-top:4px">{{ t('emr_or_create') }}</div>
        <Btn variant="primary" style="margin-top:14px" @click="openNewModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          {{ t('emr_new_record_btn') }}
        </Btn>
      </div>

      <!-- Visit detail -->
      <template v-else>

        <!-- Flash -->
        <div v-if="flash" class="flash-ok" style="margin:14px 16px 0">{{ flash }}</div>

        <!-- Patient banner -->
        <div class="card" style="margin:14px 16px 0;padding:14px 18px">
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
            <Avatar :name="selected.patient_name" size="lg" />
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <div style="font:700 15px var(--font-sans);color:var(--fg1)">{{ selected.patient_name }}</div>
                <Badge v-if="selected.patient_allergies" tone="red">⚠ {{ selected.patient_allergies }}</Badge>
                <Badge v-for="c in selected.patient_conditions" :key="c" tone="neutral">{{ c }}</Badge>
              </div>
              <div style="font:500 11px var(--font-mono);color:var(--fg3);margin-top:3px">
                {{ selected.patient_ic }} · {{ selected.patient_age_gender }} · {{ selected.patient_blood_type }} · {{ selected.patient_id_str }}
              </div>
              <div style="font:500 11px var(--font-sans);color:var(--fg3);margin-top:2px">
                {{ t('emr_lbl_doctor') }}: {{ selected.doctor_name }} · {{ selected.visit_date }}
              </div>
            </div>
            <!-- Vitals inline -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
              <template v-if="selected.vitals">
                <div class="vital">
                  <div class="vital__label">BP</div>
                  <div :class="['vital__val', bpAbnormal(selected.vitals.bp_systolic, selected.vitals.bp_diastolic) ? 'abnormal':'']">
                    {{ selected.vitals.bp_systolic }}/{{ selected.vitals.bp_diastolic }}
                  </div>
                  <div class="vital__unit">mmHg</div>
                </div>
                <div class="vital">
                  <div class="vital__label">HR</div>
                  <div :class="['vital__val', hrAbnormal(selected.vitals.heart_rate) ? 'abnormal':'']">{{ selected.vitals.heart_rate }}</div>
                  <div class="vital__unit">bpm</div>
                </div>
                <div class="vital">
                  <div class="vital__label">Temp</div>
                  <div :class="['vital__val', tempAbnormal(selected.vitals.temperature) ? 'abnormal':'']">{{ selected.vitals.temperature }}</div>
                  <div class="vital__unit">°C</div>
                </div>
                <div class="vital">
                  <div class="vital__label">SpO₂</div>
                  <div :class="['vital__val', spo2Abnormal(selected.vitals.spo2) ? 'abnormal':'']">{{ selected.vitals.spo2 }}</div>
                  <div class="vital__unit">%</div>
                </div>
                <div class="vital">
                  <div class="vital__label">{{ t('emr_vital_weight') }}</div>
                  <div class="vital__val">{{ selected.vitals.weight }}</div>
                  <div class="vital__unit">kg</div>
                </div>
              </template>
              <Btn variant="ghost" size="sm" @click="editVitals = !editVitals">
                {{ selected.vitals ? t('emr_edit_vitals') : t('emr_add_vitals') }}
              </Btn>
            </div>
          </div>

          <!-- Vitals edit form -->
          <div v-if="editVitals" class="vitals-form">
            <div class="form-grid-vitals">
              <div class="field">
                <label class="field__label">{{ t('emr_vital_systol') }}</label>
                <input v-model="vitalsForm.bp_systolic" type="number" class="input" placeholder="120" />
              </div>
              <div class="field">
                <label class="field__label">{{ t('emr_vital_diastol') }}</label>
                <input v-model="vitalsForm.bp_diastolic" type="number" class="input" placeholder="80" />
              </div>
              <div class="field">
                <label class="field__label">{{ t('emr_vital_pulse') }}</label>
                <input v-model="vitalsForm.heart_rate" type="number" class="input" placeholder="80" />
              </div>
              <div class="field">
                <label class="field__label">{{ t('emr_vital_temp') }}</label>
                <input v-model="vitalsForm.temperature" type="number" step="0.1" class="input" placeholder="36.8" />
              </div>
              <div class="field">
                <label class="field__label">SpO₂ (%)</label>
                <input v-model="vitalsForm.spo2" type="number" class="input" placeholder="99" />
              </div>
              <div class="field">
                <label class="field__label">{{ t('emr_vital_weight2') }}</label>
                <input v-model="vitalsForm.weight" type="number" step="0.1" class="input" placeholder="65.0" />
              </div>
              <div class="field">
                <label class="field__label">{{ t('emr_vital_height') }}</label>
                <input v-model="vitalsForm.height" type="number" class="input" placeholder="165" />
              </div>
            </div>
            <div class="row" style="gap:6px;margin-top:10px">
              <Btn variant="primary" size="sm" :disabled="vitalsForm.processing" @click="saveVitals">{{ t('emr_save_vitals') }}</Btn>
              <Btn variant="ghost" size="sm" @click="editVitals = false">{{ t('btn_cancel') }}</Btn>
            </div>
          </div>
        </div>

        <!-- Main content -->
        <div class="detail-body">

          <!-- Left col: SOAP + Diagnoses -->
          <div class="detail-left">

            <!-- SOAP notes -->
            <div class="card soap-card">
              <div class="card__header">
                <h3 class="card__title">{{ t('emr_soap') }}</h3>
                <p v-if="selected.status === 'closed'" class="card__sub" style="color:var(--brand-green)">
                  ✓ {{ t('emr_signed_by', { name: selected.signed_by, date: selected.signed_at }) }}
                </p>
                <div class="spacer"></div>
                <div class="tabs" style="margin:0">
                  <button v-for="[key, label] in Object.entries(SOAP_LABELS)" :key="key"
                          :class="['tab', soapTab === key ? 'active':'']"
                          @click="soapTab = key">{{ label }}</button>
                </div>
              </div>
              <div class="card__body soap-body">
                <textarea
                  v-model="soapForm[SOAP_FIELDS[soapTab]]"
                  class="input soap-textarea"
                  :placeholder="soapHints[soapTab]"
                  :disabled="selected.status === 'closed'"
                  @input="soapDirty = true"
                ></textarea>
                <div class="row" style="margin-top:8px;gap:6px;flex-shrink:0">
                  <Btn v-if="selected.status === 'open'" variant="primary" size="sm"
                       :disabled="soapForm.processing || !soapDirty" @click="saveSoap">
                    {{ soapForm.processing ? t('emr_saving') : t('emr_save_soap') }}
                  </Btn>
                  <span v-if="soapDirty" style="font:500 11px var(--font-sans);color:var(--brand-orange);padding-top:2px">
                    {{ t('emr_soap_unsaved') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Diagnoses -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('emr_dx') }}</h3>
                <div class="spacer"></div>
                <Btn v-if="selected.status === 'open'" variant="ghost" size="sm" @click="showDxForm = !showDxForm">
                  {{ showDxForm ? t('btn_close') : t('emr_add_dx') }}
                </Btn>
              </div>

              <!-- Add diagnosis form -->
              <div v-if="showDxForm && selected.status === 'open'" class="card__body" style="border-bottom:1px solid var(--border)">
                <div style="font:700 11px var(--font-sans);letter-spacing:.05em;text-transform:uppercase;color:var(--fg3);margin-bottom:8px">{{ t('emr_quick_select') }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px">
                  <button v-for="q in QUICK_ICD" :key="q.code"
                          :class="['icd-chip', dxForm.icd_code === q.code ? 'icd-chip--active':'']"
                          @click="pickIcd(q)">
                    <span style="font-weight:700">{{ q.code }}</span> {{ q.desc }}
                  </button>
                </div>
                <div class="form-grid-3" style="margin-bottom:10px">
                  <div class="field">
                    <label class="field__label">{{ t('emr_icd_code') }}</label>
                    <input v-model="dxForm.icd_code" class="input" placeholder="E11.9" maxlength="10" />
                    <span v-if="dxForm.errors.icd_code" class="field__error">{{ dxForm.errors.icd_code }}</span>
                  </div>
                  <div class="field" style="grid-column:2/-1">
                    <label class="field__label">{{ t('emr_diagnosis_desc') }}</label>
                    <input v-model="dxForm.description" class="input" :placeholder="t('emr_diagnosis_name_ph')" maxlength="255" />
                    <span v-if="dxForm.errors.description" class="field__error">{{ dxForm.errors.description }}</span>
                  </div>
                  <div class="field">
                    <label class="field__label">{{ t('emr_diagnosis_type') }}</label>
                    <select v-model="dxForm.type" class="select">
                      <option value="primary">{{ t('emr_dx_primary') }}</option>
                      <option value="secondary">{{ t('emr_dx_secondary') }}</option>
                    </select>
                  </div>
                </div>
                <Btn variant="primary" size="sm" :disabled="dxForm.processing || !dxForm.icd_code || !dxForm.description" @click="addDiagnosis">
                  {{ t('emr_add_diagnosis') }}
                </Btn>
              </div>

              <!-- Diagnosis list -->
              <div v-if="selected.diagnoses?.length">
                <div v-for="dx in selected.diagnoses" :key="dx.id"
                     class="row" style="padding:10px 18px;border-top:1px solid var(--border);gap:10px">
                  <Badge :tone="dx.type === 'primary' ? 'blue' : 'neutral'" style="flex-shrink:0">
                    {{ dx.icd_code }}
                  </Badge>
                  <div style="flex:1;font:500 13px var(--font-sans);color:var(--fg1)">{{ dx.description }}</div>
                  <span style="font:500 11px var(--font-sans);color:var(--fg3)">{{ dx.type === 'primary' ? t('emr_dx_primary') : t('emr_dx_secondary') }}</span>
                  <button v-if="selected.status === 'open'"
                          style="background:none;border:none;color:var(--fg3);cursor:pointer;font-size:14px;padding:0 2px"
                          @click="removeDiagnosis(dx.id)">✕</button>
                </div>
              </div>
              <div v-else style="padding:20px 18px;font:500 12px var(--font-sans);color:var(--fg3)">
                {{ t('emr_no_diagnosis') }}
              </div>
            </div>

          </div>

          <!-- Right col: Actions -->
          <div class="detail-right">

            <!-- Visit info -->
            <div class="card">
              <div class="card__header"><h3 class="card__title">{{ t('emr_sec_info') }}</h3></div>
              <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
                <div class="info-row">
                  <span class="info-label">{{ t('emr_visit_date') }}</span>
                  <span class="info-val">{{ selected.visit_date }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">{{ t('emr_lbl_complaint2') }}</span>
                  <span class="info-val">{{ selected.chief_complaint || '—' }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">{{ t('emr_lbl_status') }}</span>
                  <Badge :tone="selected.status === 'open' ? 'yellow' : 'green'">
                    {{ selected.status === 'open' ? t('emr_status_open') : t('emr_status_closed') }}
                  </Badge>
                </div>
                <div v-if="selected.signed_by" class="info-row">
                  <span class="info-label">{{ t('emr_signed_label') }}</span>
                  <span class="info-val" style="font-size:11px">{{ selected.signed_by }}<br>{{ selected.signed_at }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="card">
              <div class="card__header"><h3 class="card__title">{{ t('emr_actions') }}</h3></div>
              <div class="card__body" style="display:flex;flex-direction:column;gap:8px">
                <template v-if="selected.status === 'open'">
                  <Btn variant="primary" style="width:100%;justify-content:center" @click="showCloseConfirm = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ t('emr_close_sign') }}
                  </Btn>
                  <Btn variant="secondary" style="width:100%;justify-content:center" @click="saveSoap" :disabled="soapForm.processing">
                    {{ t('emr_save_soap') }}
                  </Btn>
                </template>
                <div v-else style="background:var(--brand-green-light);border:1px solid var(--brand-green);border-radius:8px;padding:10px 12px;font:500 12px var(--font-sans);color:var(--brand-green-dark);text-align:center">
                  {{ t('emr_record_closed') }}
                </div>
                <div class="hr"></div>
                <Btn variant="ghost" style="width:100%;justify-content:center;color:var(--brand-red)" @click="showDeleteConfirm = true">
                  {{ t('emr_delete_record') }}
                </Btn>
              </div>
            </div>

            <!-- Preskripsi Ubat -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">Preskripsi Ubat</h3>
                <div class="spacer"></div>
                <Btn v-if="selected.status === 'open'" variant="ghost" size="sm" @click="openRxModal">
                  + Preskripsi
                </Btn>
              </div>

              <div v-if="selected.prescriptions?.length">
                <div v-for="rx in selected.prescriptions" :key="rx.id"
                     style="padding:10px 14px;border-top:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
                    <span style="font:700 11px var(--font-mono);color:var(--brand-green-dark)">{{ rx.rx_number }}</span>
                    <span :class="['rx-status-chip', `rx-status--${rx.status}`]">{{ RX_STATUS_LABEL[rx.status] ?? rx.status }}</span>
                    <span style="font:500 10px var(--font-sans);color:var(--fg3);margin-left:auto">{{ rx.created_at }}</span>
                  </div>
                  <div style="display:flex;flex-direction:column;gap:3px">
                    <div v-for="(item, i) in rx.items" :key="i"
                         style="display:flex;align-items:baseline;gap:6px;font:500 12px var(--font-sans);color:var(--fg1)">
                      <span style="width:5px;height:5px;border-radius:50%;background:var(--brand-green);flex-shrink:0;margin-top:4px"></span>
                      <span>{{ item.drug_name }}</span>
                      <span v-if="item.dosage || item.frequency" style="font:400 11px var(--font-sans);color:var(--fg3)">
                        {{ [item.dosage, item.frequency].filter(Boolean).join(' · ') }}
                      </span>
                      <span style="font:700 11px var(--font-mono);color:var(--brand-green-dark);margin-left:auto">× {{ item.quantity }}</span>
                    </div>
                  </div>
                  <div style="margin-top:6px">
                    <a :href="`/pharmacy/prescriptions/${rx.id}/print`" target="_blank"
                       class="mc-print-btn" style="color:var(--brand-green-dark);border-color:var(--brand-green);background:var(--brand-green-light)">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                      Cetak Rx
                    </a>
                  </div>
                </div>
              </div>
              <div v-else style="padding:16px 14px;font:500 12px var(--font-sans);color:var(--fg3)">
                Tiada preskripsi untuk lawatan ini.
              </div>
            </div>

            <!-- Time Slip -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('ts_section') }}</h3>
                <div class="spacer"></div>
                <Btn variant="ghost" size="sm" @click="showTsForm = true">
                  {{ t('ts_issue_btn') }}
                </Btn>
              </div>

              <!-- Slip list -->
              <div v-if="selected.time_slips?.length">
                <div v-for="ts in selected.time_slips" :key="ts.id"
                     style="padding:10px 14px;border-top:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <span style="font:700 11px var(--font-mono);color:#7c3aed">{{ ts.slip_number }}</span>
                    <span style="font:500 10px var(--font-sans);color:var(--fg3);margin-left:auto">{{ ts.slip_date }}</span>
                  </div>
                  <div style="font:600 13px var(--font-mono);color:var(--fg1);margin-bottom:2px;letter-spacing:.03em">
                    {{ ts.arrival_time }} → {{ ts.departure_time }}
                  </div>
                  <div v-if="ts.purpose" style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:6px">{{ ts.purpose }}</div>
                  <div style="display:flex;gap:6px">
                    <a :href="`/timeslip/${ts.id}/print`" target="_blank" class="mc-print-btn" style="color:#7c3aed;border-color:#c4b5fd;background:#f5f3ff">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                      {{ t('ts_print') }}
                    </a>
                    <button class="mc-del-btn" @click="showTsDelId = ts.id">{{ t('ts_delete') }}</button>
                  </div>
                </div>
              </div>
              <div v-else style="padding:16px 14px;font:500 12px var(--font-sans);color:var(--fg3)">
                {{ t('ts_no_records') }}
              </div>
            </div>

            <!-- Referral Letter -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('ref_section') }}</h3>
                <div class="spacer"></div>
                <Btn variant="ghost" size="sm" @click="showRefForm = true">
                  {{ t('ref_issue_btn') }}
                </Btn>
              </div>

              <!-- Referral list -->
              <div v-if="selected.referrals?.length">
                <div v-for="ref in selected.referrals" :key="ref.id"
                     style="padding:10px 14px;border-top:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <span style="font:700 11px var(--font-mono);color:#1d4ed8">{{ ref.ref_number }}</span>
                    <span :class="['ref-urgency-chip', `ref-urgency-${ref.urgency}`]">{{ t(`ref_urgency_${ref.urgency}`) }}</span>
                    <span style="font:500 10px var(--font-sans);color:var(--fg3);margin-left:auto">{{ ref.issue_date }}</span>
                  </div>
                  <div style="font:600 12px var(--font-sans);color:var(--fg1);margin-bottom:2px">{{ ref.referred_to }}</div>
                  <div v-if="ref.referred_to_dept" style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:2px">{{ ref.referred_to_dept }}</div>
                  <div style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:6px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ ref.reason }}</div>
                  <div style="display:flex;gap:6px">
                    <a :href="`/referral/${ref.id}/print`" target="_blank" class="mc-print-btn" style="color:#1d4ed8;border-color:#93c5fd;background:#eff6ff">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                      {{ t('ref_print') }}
                    </a>
                    <button class="mc-del-btn" @click="showRefDelId = ref.id">{{ t('ref_delete') }}</button>
                  </div>
                </div>
              </div>
              <div v-else style="padding:16px 14px;font:500 12px var(--font-sans);color:var(--fg3)">
                {{ t('ref_no_records') }}
              </div>
            </div>

            <!-- Quarantine Letter -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('qn_section') }}</h3>
                <div class="spacer"></div>
                <Btn variant="ghost" size="sm" @click="showQnForm = true">
                  {{ t('qn_issue_btn') }}
                </Btn>
              </div>

              <!-- Quarantine list -->
              <div v-if="selected.quarantines?.length">
                <div v-for="qn in selected.quarantines" :key="qn.id"
                     style="padding:10px 14px;border-top:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <span style="font:700 11px var(--font-mono);color:#b45309">{{ qn.qn_number }}</span>
                    <span style="font:500 10px var(--font-sans);color:var(--fg3);margin-left:auto">{{ qn.issue_date }}</span>
                  </div>
                  <div style="font:500 12px var(--font-sans);color:var(--fg1);margin-bottom:2px">
                    {{ qn.days }} {{ t('qn_days_suffix') }} · {{ qn.quarantine_start }} → {{ qn.quarantine_end }}
                  </div>
                  <div v-if="qn.diagnosis" style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:2px">{{ qn.diagnosis }}</div>
                  <div v-if="qn.reason" style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:6px">{{ qn.reason }}</div>
                  <div style="display:flex;gap:6px">
                    <a :href="`/quarantine/${qn.id}/print`" target="_blank" class="mc-print-btn" style="color:#b45309;border-color:#fcd34d;background:#fffbeb">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                      {{ t('qn_print') }}
                    </a>
                    <button class="mc-del-btn" @click="showQnDelId = qn.id">{{ t('qn_delete') }}</button>
                  </div>
                </div>
              </div>
              <div v-else style="padding:16px 14px;font:500 12px var(--font-sans);color:var(--fg3)">
                {{ t('qn_no_records') }}
              </div>
            </div>

            <!-- Medical Certificate -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('mc_section') }}</h3>
                <div class="spacer"></div>
                <Btn variant="ghost" size="sm" @click="showMcForm = true">
                  {{ t('mc_issue_btn') }}
                </Btn>
              </div>

              <!-- MC list -->
              <div v-if="selected.mcs?.length">
                <div v-for="mc in selected.mcs" :key="mc.id"
                     style="padding:10px 14px;border-top:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                    <span style="font:700 11px var(--font-mono);color:var(--brand-green-dark)">{{ mc.mc_number }}</span>
                    <span style="font:500 10px var(--font-sans);color:var(--fg3);margin-left:auto">{{ mc.issue_date }}</span>
                  </div>
                  <div style="font:500 12px var(--font-sans);color:var(--fg1);margin-bottom:2px">
                    {{ mc.days }} {{ t('mc_days_suffix') }} · {{ mc.start_date }} → {{ mc.end_date }}
                  </div>
                  <div v-if="mc.diagnosis" style="font:400 11px var(--font-sans);color:var(--fg2);margin-bottom:6px">{{ mc.diagnosis }}</div>
                  <div style="display:flex;gap:6px">
                    <a :href="`/mc/${mc.id}/print`" target="_blank" class="mc-print-btn">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                      {{ t('mc_print') }}
                    </a>
                    <button class="mc-del-btn" @click="showMcDelId = mc.id">{{ t('mc_delete') }}</button>
                  </div>
                </div>
              </div>
              <div v-else style="padding:16px 14px;font:500 12px var(--font-sans);color:var(--fg3)">
                {{ t('mc_no_records') }}
              </div>
            </div>

          </div>
        </div>
      </template>
    </div>

    <!-- ── Prescription Modal ──────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showRxModal" class="modal-backdrop" @click.self="showRxModal = false; rxForm.clearErrors()">
        <div class="modal modal--xl">
          <div class="modal__header">
            <div style="display:flex;align-items:center;gap:10px;flex:1">
              <div style="width:34px;height:34px;border-radius:9px;background:var(--brand-green);color:#fff;font:800 13px var(--font-mono);display:grid;place-items:center;flex-shrink:0">Rx</div>
              <div>
                <h3 class="modal__title">Preskripsi Ubat</h3>
                <p style="font:500 11px var(--font-mono);color:var(--fg3);margin:2px 0 0">{{ selected?.patient_name }}</p>
              </div>
            </div>
            <button class="modal__close" @click="showRxModal = false; rxForm.clearErrors()">✕</button>
          </div>

          <div class="modal__body">
            <!-- Drug rows -->
            <div v-for="(item, i) in rxForm.items" :key="i" class="rx-emr-card">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <span class="rx-emr-num">Ubat {{ i + 1 }}</span>
                <button type="button" class="rx-emr-remove" @click="removeRxItem(i)" :disabled="rxForm.items.length === 1">×</button>
              </div>

              <!-- Drug name search -->
              <div class="field" style="margin-bottom:10px">
                <label class="field__label">Nama Ubat <span style="color:var(--brand-red)">*</span></label>
                <div style="position:relative">
                  <div v-if="item.inventory_item_id" class="rx-linked-bar">
                    <span class="rx-linked-name">{{ item.drug_name }}</span>
                    <span v-if="resolvedRxInv(item)" :class="['rx-stock-badge', resolvedRxInv(item).stock_quantity <= 0 ? 'rx-stock--out' : resolvedRxInv(item).stock_quantity <= 10 ? 'rx-stock--low' : 'rx-stock--ok']">
                      Stok: {{ resolvedRxInv(item).stock_quantity }}
                    </span>
                    <button type="button" class="rx-linked-clear" @click="clearRxDrug(i)">✕</button>
                  </div>
                  <template v-else>
                    <input
                      v-model="rxDrugSearch[i]"
                      class="input"
                      placeholder="Cari dari inventori atau taip nama ubat..."
                      autocomplete="off"
                      @input="rxDrugOpen[i] = true; item.drug_name = rxDrugSearch[i]"
                      @focus="rxDrugOpen[i] = true"
                      @blur="setTimeout(() => { rxDrugOpen[i] = false }, 180)"
                    />
                    <div v-if="rxDrugOpen[i] && filteredRxDrugs(i).length" class="rx-drug-dd">
                      <button
                        v-for="inv in filteredRxDrugs(i)" :key="inv.id"
                        type="button"
                        class="rx-drug-opt"
                        :class="{ 'rx-drug-opt--out': inv.stock_quantity <= 0 }"
                        @mousedown.prevent="selectRxDrug(i, inv)"
                      >
                        <span class="rx-drug-opt__name">{{ inv.name }}</span>
                        <span class="rx-drug-opt__meta">
                          <span v-if="inv.generic_name" style="color:var(--fg3)">{{ inv.generic_name }}</span>
                          <span v-if="inv.form"> · {{ inv.form }}</span>
                          <span :class="['rx-drug-opt__stock', inv.stock_quantity <= 0 ? 'out' : inv.stock_quantity <= 10 ? 'low' : '']">Stok: {{ inv.stock_quantity }}</span>
                          <span v-if="inv.selling_price > 0" style="color:var(--brand-green-dark);font-weight:700;margin-left:auto">RM {{ Number(inv.selling_price).toFixed(2) }}</span>
                        </span>
                      </button>
                    </div>
                  </template>
                </div>
                <span v-if="rxForm.errors[`items.${i}.drug_name`]" style="font:500 11px var(--font-sans);color:var(--brand-red)">Wajib diisi</span>
              </div>

              <!-- Dosage · Frequency · Duration · Qty grid -->
              <div class="rx-emr-grid">
                <div class="field">
                  <label class="field__label">Dos</label>
                  <input v-model="item.dosage" class="input" placeholder="1 tablet" />
                </div>
                <div class="field">
                  <label class="field__label">Kekerapan</label>
                  <input v-model="item.frequency" class="input" placeholder="OD / BD / TDS" list="rx-freq-list" />
                  <datalist id="rx-freq-list">
                    <option v-for="f in RX_FREQS" :key="f" :value="f" />
                  </datalist>
                </div>
                <div class="field">
                  <label class="field__label">Tempoh</label>
                  <input v-model="item.duration" class="input" placeholder="7 hari" />
                </div>
                <div class="field">
                  <label class="field__label">Kuantiti <span style="color:var(--brand-red)">*</span></label>
                  <input v-model.number="item.quantity" type="number" min="1" class="input" style="text-align:center" />
                </div>
              </div>

              <!-- Instructions -->
              <div class="field" style="margin-top:8px">
                <label class="field__label">Arahan</label>
                <input v-model="item.instructions" class="input" placeholder="Selepas makan" list="rx-instr-list" />
                <datalist id="rx-instr-list">
                  <option v-for="ins in RX_INSTRS" :key="ins" :value="ins" />
                </datalist>
              </div>
            </div>

            <!-- Add drug button -->
            <button type="button" class="rx-emr-add-btn" @click="addRxItem">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Tambah Ubat
            </button>

            <!-- Notes -->
            <div class="field" style="margin-top:14px">
              <label class="field__label">Nota (pilihan)</label>
              <textarea v-model="rxForm.notes" class="input" rows="2" placeholder="Nota khas untuk ahli farmasi..." style="resize:vertical"></textarea>
            </div>

            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showRxModal = false; rxForm.clearErrors()">Batal</Btn>
              <Btn variant="primary" :disabled="rxForm.processing || !rxForm.items.some(i => i.drug_name)" @click="submitRx">
                {{ rxForm.processing ? 'Menghantar...' : 'Hantar ke Farmasi' }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Issue MC Modal ────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showMcForm" class="modal-backdrop" @click.self="showMcForm = false; mcForm.clearErrors()">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('mc_issue_btn') }}</h3>
            <button class="modal__close" @click="showMcForm = false; mcForm.clearErrors()">✕</button>
          </div>
          <div class="modal__body">
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('mc_lbl_start') }}</label>
              <input v-model="mcForm.start_date" type="date" class="input" required />
              <span v-if="mcForm.errors.start_date" class="field__error">{{ mcForm.errors.start_date }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('mc_lbl_days') }}</label>
              <input v-model="mcForm.days" type="number" min="1" max="365" class="input" />
              <span v-if="mcForm.errors.days" class="field__error">{{ mcForm.errors.days }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('mc_lbl_diagnosis') }}</label>
              <input v-model="mcForm.diagnosis" type="text" class="input" :placeholder="t('mc_ph_diagnosis')" maxlength="255" />
            </div>
            <div class="field" style="margin-bottom:16px">
              <label class="field__label">{{ t('mc_lbl_notes') }}</label>
              <input v-model="mcForm.notes" type="text" class="input" :placeholder="t('mc_ph_notes')" maxlength="500" />
            </div>
            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showMcForm = false; mcForm.clearErrors()">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" :disabled="mcForm.processing || !mcForm.start_date || mcForm.days < 1" @click="issueMc">
                {{ mcForm.processing ? t('mc_submitting') : t('mc_issue_btn') }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Issue Timeslip Modal ───────────────────────────── -->
    <Teleport to="body">
      <div v-if="showTsForm" class="modal-backdrop" @click.self="showTsForm = false; tsForm.clearErrors()">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('ts_issue_btn') }}</h3>
            <button class="modal__close" @click="showTsForm = false; tsForm.clearErrors()">✕</button>
          </div>
          <div class="modal__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
              <div class="field">
                <label class="field__label">{{ t('ts_lbl_arrival') }} *</label>
                <input v-model="tsForm.arrival_time" type="time" class="input" required />
                <span v-if="tsForm.errors.arrival_time" class="field__error">{{ tsForm.errors.arrival_time }}</span>
              </div>
              <div class="field">
                <label class="field__label">{{ t('ts_lbl_departure') }} *</label>
                <input v-model="tsForm.departure_time" type="time" class="input" required />
                <span v-if="tsForm.errors.departure_time" class="field__error">{{ tsForm.errors.departure_time }}</span>
              </div>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ts_lbl_purpose') }}</label>
              <input v-model="tsForm.purpose" type="text" class="input" :placeholder="t('ts_ph_purpose')" maxlength="255" />
            </div>
            <div class="field" style="margin-bottom:16px">
              <label class="field__label">{{ t('ts_lbl_notes') }}</label>
              <input v-model="tsForm.notes" type="text" class="input" :placeholder="t('ts_ph_notes')" maxlength="500" />
            </div>
            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showTsForm = false; tsForm.clearErrors()">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" :disabled="tsForm.processing || !tsForm.arrival_time || !tsForm.departure_time" @click="issueTsSlip">
                {{ tsForm.processing ? t('ts_submitting') : t('ts_issue_btn') }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Issue Referral Modal ───────────────────────────── -->
    <Teleport to="body">
      <div v-if="showRefForm" class="modal-backdrop" @click.self="showRefForm = false; refForm.clearErrors()">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('ref_issue_btn') }}</h3>
            <button class="modal__close" @click="showRefForm = false; refForm.clearErrors()">✕</button>
          </div>
          <div class="modal__body">
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ref_lbl_to') }} *</label>
              <input v-model="refForm.referred_to" type="text" class="input" :placeholder="t('ref_ph_to')" maxlength="255" />
              <span v-if="refForm.errors.referred_to" class="field__error">{{ refForm.errors.referred_to }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ref_lbl_dept') }}</label>
              <input v-model="refForm.referred_to_dept" type="text" class="input" :placeholder="t('ref_ph_dept')" maxlength="255" />
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ref_lbl_urgency') }}</label>
              <select v-model="refForm.urgency" class="select">
                <template v-if="(lookups?.keutamaan_rujukan ?? []).length">
                  <option v-for="u in lookups.keutamaan_rujukan" :key="u.code" :value="u.code">{{ u.label_ms }}</option>
                </template>
                <template v-else>
                  <option value="routine">{{ t('ref_urgency_routine') }}</option>
                  <option value="urgent">{{ t('ref_urgency_urgent') }}</option>
                  <option value="emergency">{{ t('ref_urgency_emergency') }}</option>
                </template>
              </select>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ref_lbl_reason') }} *</label>
              <textarea v-model="refForm.reason" class="input" rows="3" :placeholder="t('ref_ph_reason')" maxlength="1000" style="resize:vertical"></textarea>
              <span v-if="refForm.errors.reason" class="field__error">{{ refForm.errors.reason }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('ref_lbl_summary') }}</label>
              <textarea v-model="refForm.clinical_summary" class="input" rows="3" :placeholder="t('ref_ph_summary')" maxlength="2000" style="resize:vertical"></textarea>
            </div>
            <div class="field" style="margin-bottom:16px">
              <label class="field__label">{{ t('ref_lbl_history') }}</label>
              <textarea v-model="refForm.relevant_history" class="input" rows="2" :placeholder="t('ref_ph_history')" maxlength="2000" style="resize:vertical"></textarea>
            </div>
            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showRefForm = false; refForm.clearErrors()">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" :disabled="refForm.processing || !refForm.referred_to || !refForm.reason" @click="issueReferral">
                {{ refForm.processing ? t('ref_submitting') : t('ref_issue_btn') }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Issue Quarantine Modal ─────────────────────────── -->
    <Teleport to="body">
      <div v-if="showQnForm" class="modal-backdrop" @click.self="showQnForm = false; qnForm.clearErrors()">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('qn_issue_btn') }}</h3>
            <button class="modal__close" @click="showQnForm = false; qnForm.clearErrors()">✕</button>
          </div>
          <div class="modal__body">
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('qn_lbl_start') }}</label>
              <input v-model="qnForm.quarantine_start" type="date" class="input" required />
              <span v-if="qnForm.errors.quarantine_start" class="field__error">{{ qnForm.errors.quarantine_start }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('qn_lbl_days') }}</label>
              <input v-model="qnForm.days" type="number" min="1" max="365" class="input" />
              <span v-if="qnForm.errors.days" class="field__error">{{ qnForm.errors.days }}</span>
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('qn_lbl_diagnosis') }}</label>
              <input v-model="qnForm.diagnosis" type="text" class="input" :placeholder="t('qn_ph_diagnosis')" maxlength="255" />
            </div>
            <div class="field" style="margin-bottom:12px">
              <label class="field__label">{{ t('qn_lbl_reason') }}</label>
              <input v-model="qnForm.reason" type="text" class="input" :placeholder="t('qn_ph_reason')" maxlength="255" />
            </div>
            <div class="field" style="margin-bottom:16px">
              <label class="field__label">{{ t('qn_lbl_notes') }}</label>
              <input v-model="qnForm.notes" type="text" class="input" :placeholder="t('qn_ph_notes')" maxlength="500" />
            </div>
            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showQnForm = false; qnForm.clearErrors()">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" :disabled="qnForm.processing || !qnForm.quarantine_start || qnForm.days < 1" @click="issueQuarantine">
                {{ qnForm.processing ? t('qn_submitting') : t('qn_issue_btn') }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── MC Delete confirm ───────────────────────────── -->
    <Teleport to="body">
      <div v-if="showMcDelId !== null" class="modal-backdrop" @click.self="showMcDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('mc_del_confirm') }}</h3>
            <button class="modal__close" @click="showMcDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 16px">
              {{ t('mc_del_body', { mc_number: selected?.mcs?.find(m => m.id === showMcDelId)?.mc_number }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showMcDelId = null">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteMc(showMcDelId)">{{ t('mc_del_yes') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── New Visit Modal ──────────────────────────── -->
    <Teleport to="body">
      <div v-if="showNewModal" class="modal-backdrop" @click.self="showNewModal = false">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('emr_new_modal_title') }}</h3>
            <button class="modal__close" @click="showNewModal = false">✕</button>
          </div>
          <form @submit.prevent="submitNewVisit" class="modal__body">
            <div class="modal-section-title">{{ t('emr_sec_patient') }}</div>
            <div class="field" style="margin-bottom:14px">
              <label class="field__label">{{ t('emr_lbl_search_patient') }}</label>
              <div style="position:relative">
                <input v-model="patientSearch" type="text" class="input"
                       :placeholder="t('emr_ph_patient')"
                       @input="newForm.patient_id = ''" autocomplete="off" />
                <div v-if="patientResults.length" class="pdrop">
                  <button v-for="p in patientResults" :key="p.id"
                          type="button" class="popt" @click="selectNewPatient(p)">
                    <span style="font:600 13px var(--font-sans);color:var(--fg1)">{{ p.name }}</span>
                    <span style="font:500 11px var(--font-mono);color:var(--fg3)">{{ p.ic_number }}</span>
                  </button>
                </div>
              </div>
              <span v-if="newForm.errors.patient_id" class="field__error">{{ newForm.errors.patient_id }}</span>
            </div>
            <div class="modal-section-title">{{ t('emr_details_section') }}</div>
            <div class="form-grid-3" style="margin-bottom:10px">
              <div class="field">
                <label class="field__label">{{ t('emr_date_label') }}</label>
                <input v-model="newForm.visit_date" type="date" class="input" required />
              </div>
              <div class="field" style="grid-column:2/-1">
                <label class="field__label">{{ t('emr_lbl_doctor') }}</label>
                <input v-model="newForm.doctor_name" type="text" class="input" />
              </div>
              <div class="field" style="grid-column:1/-1">
                <label class="field__label">{{ t('emr_lbl_complaint') }}</label>
                <input v-model="newForm.chief_complaint" type="text" class="input"
                       :placeholder="t('emr_complaint_ph')" maxlength="500" />
              </div>
            </div>
            <div class="modal__footer">
              <Btn variant="secondary" type="button" @click="showNewModal = false">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" type="submit" :disabled="newForm.processing || !newForm.patient_id">
                {{ newForm.processing ? t('emr_opening') : t('emr_open_record') }}
              </Btn>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Time Slip Delete confirm ──────────────────────── -->
    <Teleport to="body">
      <div v-if="showTsDelId !== null" class="modal-backdrop" @click.self="showTsDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('ts_del_confirm') }}</h3>
            <button class="modal__close" @click="showTsDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 16px">
              {{ t('ts_del_body', { slip_number: selected?.time_slips?.find(s => s.id === showTsDelId)?.slip_number }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showTsDelId = null">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteTs(showTsDelId)">{{ t('ts_del_yes') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Referral Delete confirm ───────────────────────── -->
    <Teleport to="body">
      <div v-if="showRefDelId !== null" class="modal-backdrop" @click.self="showRefDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('ref_del_confirm') }}</h3>
            <button class="modal__close" @click="showRefDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 16px">
              {{ t('ref_del_body', { ref_number: selected?.referrals?.find(r => r.id === showRefDelId)?.ref_number }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showRefDelId = null">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteReferral(showRefDelId)">{{ t('ref_del_yes') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Quarantine Delete confirm ───────────────────────── -->
    <Teleport to="body">
      <div v-if="showQnDelId !== null" class="modal-backdrop" @click.self="showQnDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('qn_del_confirm') }}</h3>
            <button class="modal__close" @click="showQnDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 16px">
              {{ t('qn_del_body', { qn_number: selected?.quarantines?.find(q => q.id === showQnDelId)?.qn_number }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showQnDelId = null">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteQuarantine(showQnDelId)">{{ t('qn_del_yes') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Close confirm ──────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showCloseConfirm" class="modal-backdrop" @click.self="showCloseConfirm = false">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('emr_confirm_close') }}</h3>
            <button class="modal__close" @click="showCloseConfirm = false">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              {{ t('emr_close_body_patient', { name: selected?.patient_name }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showCloseConfirm = false">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" @click="closeVisit">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ t('emr_confirm_close_btn') }}
              </Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Delete confirm ─────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showDeleteConfirm" class="modal-backdrop" @click.self="showDeleteConfirm = false">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">{{ t('emr_del_title') }}</h3>
            <button class="modal__close" @click="showDeleteConfirm = false">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 4px">
              {{ t('emr_del_body', { name: selected?.patient_name, date: selected?.visit_date }) }}
            </p>
            <p style="font:400 12px var(--font-sans);color:var(--fg3);margin:0">{{ t('emr_del_vitals_note') }}</p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showDeleteConfirm = false">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteVisit">{{ t('emr_del_confirm') }}</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<style scoped>
/* Root layout */
.emr-root {
  display: flex;
  height: 100%;
  overflow: hidden;
}

/* ── Visit list panel ───────────────────────────── */
.visit-list {
  width: 264px;
  flex-shrink: 0;
  background: #fff;
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.vl-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 12px 12px;
  border-bottom: 1px solid var(--border);
}
.vl-item {
  padding: 10px 12px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
}
.vl-item:hover   { background: var(--bg-soft); }
.vl-item.active  { background: var(--brand-green-light); border-left: 3px solid var(--brand-green); }

.status-chip {
  padding: 3px 10px; border: 1px solid var(--border); border-radius: 999px;
  background: #fff; color: var(--fg3); font: 500 11px var(--font-sans); cursor: pointer;
}
.status-chip:hover { border-color: var(--brand-green); color: var(--brand-green-dark); }
.status-chip.active { background: var(--brand-green); border-color: var(--brand-green); color: #fff; font-weight: 700; }

/* ── Detail panel ───────────────────────────────── */
.visit-detail {
  flex: 1;
  overflow-y: auto;
  background: var(--bg-soft);
  display: flex;
  flex-direction: column;
}

.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--fg3);
  padding: 40px;
}

.detail-body {
  display: grid;
  grid-template-columns: 1fr 240px;
  gap: 14px;
  padding: 14px 16px 24px;
  align-items: stretch;
}
.detail-left  { display: flex; flex-direction: column; gap: 14px; height: 100%; }
.detail-right { display: flex; flex-direction: column; gap: 14px; }

/* SOAP card — stretches to fill left column height */
.soap-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.soap-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.soap-textarea {
  flex: 1;
  resize: vertical;
  width: 100%;
  min-height: 160px;
}

/* Vitals edit form */
.vitals-form {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}
.form-grid-vitals {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

/* Info rows (right panel) */
.info-row   { display: flex; flex-direction: column; gap: 3px; }
.info-label { font: 600 10px var(--font-sans); letter-spacing: .05em; text-transform: uppercase; color: var(--fg3); }
.info-val   { font: 500 13px var(--font-sans); color: var(--fg1); }

/* ICD quick-select chips */
.icd-chip {
  display: inline-flex; gap: 4px; align-items: center;
  padding: 3px 8px; border: 1px solid var(--border); border-radius: 999px;
  background: #fff; color: var(--fg2); font: 400 11px var(--font-sans); cursor: pointer;
}
.icd-chip:hover    { border-color: var(--brand-green); color: var(--brand-green-dark); }
.icd-chip--active  { background: var(--brand-green); border-color: var(--brand-green); color: #fff; }

/* Shared modal/form patterns */
.flash-ok {
  background: var(--brand-green-light); border: 1px solid var(--brand-green);
  color: var(--brand-green-dark); padding: 10px 16px; border-radius: 8px;
  font: 600 13px var(--font-sans);
}
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
.modal--sm  { width: 420px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title  { flex: 1; font: 700 15px var(--font-sans); color: var(--fg1); margin: 0; }
.modal__close  { width: 28px; height: 28px; border: 0; background: var(--bg-muted); border-radius: 6px; cursor: pointer; font-size: 12px; color: var(--fg2); display: grid; place-items: center; }
.modal__body   { padding: 20px; }
.modal__footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
.modal-section-title { font: 700 11px var(--font-sans); letter-spacing: .06em; text-transform: uppercase; color: var(--fg3); margin-bottom: 10px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field__label { font: 600 11px var(--font-sans); color: var(--fg2); }
.field__error { font: 500 11px var(--font-sans); color: var(--brand-red); }

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

/* Pagination */
.pagination { display: flex; gap: 4px; padding: 8px 12px; justify-content: center; }
.page-btn { min-width: 28px; height: 28px; border: 1px solid var(--border); border-radius: 6px; background: #fff; color: var(--fg2); font: 500 11px var(--font-sans); cursor: pointer; padding: 0 6px; }
.page-btn.active { background: var(--brand-green); border-color: var(--brand-green); color: #fff; font-weight: 700; }
.page-btn:disabled { opacity: .4; cursor: default; }

/* MC buttons */
.mc-print-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 8px; background: var(--brand-green-light);
  border: 1px solid var(--brand-green); border-radius: 5px;
  color: var(--brand-green-dark); font: 600 11px var(--font-sans);
  text-decoration: none; cursor: pointer;
}
.mc-print-btn:hover { background: var(--brand-green); color: #fff; }
.mc-del-btn {
  display: inline-flex; align-items: center;
  padding: 3px 8px; background: none;
  border: 1px solid var(--border); border-radius: 5px;
  color: var(--fg3); font: 500 11px var(--font-sans); cursor: pointer;
}
.mc-del-btn:hover { border-color: var(--brand-red); color: var(--brand-red); }

/* Referral urgency chips */
.ref-urgency-chip {
  display: inline-block; padding: 1px 7px; border-radius: 999px;
  font: 600 9.5px var(--font-sans); text-transform: uppercase; letter-spacing: .04em;
}
.ref-urgency-routine   { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.ref-urgency-urgent    { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
.ref-urgency-emergency { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

/* ── Rx status chip (EMR card) ── */
.rx-status-chip {
  display: inline-flex; align-items: center;
  padding: 1px 7px; border-radius: 999px;
  font: 600 9.5px var(--font-sans); text-transform: uppercase; letter-spacing: .04em;
  border: 1px solid var(--border); background: var(--bg-soft); color: var(--fg2);
}
.rx-status--pending   { background:#FFF7ED; border-color:#FED7AA; color:#C2410C; }
.rx-status--verifying { background:#EFF6FF; border-color:#93C5FD; color:#1D4ED8; }
.rx-status--ready     { background:var(--brand-green-light); border-color:var(--brand-green); color:var(--brand-green-dark); }
.rx-status--dispensed { background:#F9FAFB; border-color:#D1D5DB; color:#6B7280; }
.rx-status--cancelled { background:#F9FAFB; border-color:#D1D5DB; color:#9CA3AF; }

/* ── Rx modal internals ── */
.rx-emr-card {
  border: 1.5px solid var(--border); border-radius: 12px;
  padding: 14px; margin-bottom: 10px; background: #fff;
  transition: border-color .15s;
}
.rx-emr-card:focus-within { border-color: var(--brand-green); box-shadow: 0 0 0 3px rgba(27,138,74,.08); }
.rx-emr-num {
  font: 700 10.5px var(--font-sans); text-transform: uppercase; letter-spacing: .07em;
  color: var(--brand-green-dark); background: var(--brand-green-light); padding: 3px 10px; border-radius: 999px;
}
.rx-emr-remove {
  width: 26px; height: 26px; border: 1px solid var(--border); border-radius: 6px;
  background: #fff; color: var(--fg3); cursor: pointer; font-size: 15px;
  display: grid; place-items: center;
}
.rx-emr-remove:hover:not(:disabled) { background: #FEE2E2; color: #dc2626; border-color: #FECACA; }
.rx-emr-remove:disabled { opacity: .3; cursor: default; }
.rx-emr-grid { display: grid; grid-template-columns: 1fr 1.4fr 0.9fr 0.7fr; gap: 10px; }
.rx-emr-add-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 9px; margin-top: 4px;
  border: 1.5px dashed var(--border); border-radius: 10px;
  background: transparent; color: var(--brand-green-dark);
  font: 600 13px var(--font-sans); cursor: pointer;
}
.rx-emr-add-btn:hover { background: var(--brand-green-light); border-color: var(--brand-green); }

/* Rx drug search dropdown */
.rx-drug-dd {
  position: absolute; top: calc(100% + 3px); left: 0; right: 0; z-index: 200;
  background: #fff; border: 1px solid var(--border); border-radius: 10px;
  box-shadow: var(--shadow-md); max-height: 220px; overflow-y: auto;
}
.rx-drug-opt {
  display: block; width: 100%; text-align: left; padding: 8px 12px;
  border: 0; background: transparent; cursor: pointer;
}
.rx-drug-opt:hover { background: var(--bg-soft); }
.rx-drug-opt + .rx-drug-opt { border-top: 1px solid var(--bg-muted); }
.rx-drug-opt--out { opacity: .5; }
.rx-drug-opt__name { display: block; font: 600 12.5px var(--font-sans); color: var(--fg1); }
.rx-drug-opt__meta { display: flex; align-items: center; gap: 6px; font: 500 11px var(--font-sans); margin-top: 2px; flex-wrap: wrap; }
.rx-drug-opt__stock { font: 700 10.5px var(--font-mono); padding: 1px 6px; border-radius: 999px; background: var(--brand-green-light); color: var(--brand-green-dark); }
.rx-drug-opt__stock.low { background: #FEF3C7; color: #92400E; }
.rx-drug-opt__stock.out { background: #FEE2E2; color: #991B1B; }

/* Rx linked drug bar */
.rx-linked-bar {
  display: flex; align-items: center; gap: 6px; min-height: 38px;
  padding: 6px 10px; border: 1.5px solid var(--brand-green);
  border-radius: 8px; background: var(--brand-green-light);
}
.rx-linked-name { flex: 1; font: 600 12.5px var(--font-sans); color: var(--brand-green-dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rx-stock-badge { font: 700 10.5px var(--font-mono); padding: 1px 7px; border-radius: 999px; flex-shrink: 0; }
.rx-stock--ok  { background: var(--brand-green-light); color: var(--brand-green-dark); border: 1px solid var(--brand-green); }
.rx-stock--low { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }
.rx-stock--out { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
.rx-linked-clear {
  width: 18px; height: 18px; border: 0; background: transparent; cursor: pointer;
  color: var(--brand-green-dark); font-size: 11px; display: grid; place-items: center; opacity: .7;
}
.rx-linked-clear:hover { opacity: 1; }

@media (max-width: 640px) {
  .rx-emr-grid { grid-template-columns: 1fr 1fr; }
}
</style>
