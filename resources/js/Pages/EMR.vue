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
const rxDrugSearch  = ref([''])
const rxDrugOpen    = ref([false])

function emptyRxItem() {
  return {
    inventory_item_id: null, drug_name: '', kegunaan: '', drug_unit: '',
    dosage: '', frequency: '', duration: '', quantity: 1,
    instructions: '', is_prn: false, complete_course: false, item_note: '',
  }
}

const rxForm = useForm({ notes: '', items: [emptyRxItem()] })

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

const RX_FREQS  = computed(() => (props.lookups?.kekerapan_dos ?? []).map(v => v.label_ms))
const RX_INSTRS = computed(() => (props.lookups?.arahan_dos    ?? []).map(v => v.label_ms))
const DRUG_UNITS = computed(() => (props.lookups?.bentuk_ubat  ?? []).map(v => ({ code: v.code, label: v.label_ms })))

const RX_STATUS_TONE  = { draft:'neutral', pending:'orange', verifying:'blue', ready:'green', dispensed:'neutral', cancelled:'neutral' }
const RX_STATUS_LABEL = { draft:'Draf', pending:'Menunggu', verifying:'Sedang Disemak', ready:'Sedia', dispensed:'Telah Diambil', cancelled:'Dibatalkan' }

const PRN_TAGS = ['Bila Perlu', 'Habiskan ubat']
function updateRxPrnLine(note, tag, active) {
  const lines  = (note ?? '').split('\n')
  const idx    = lines.findIndex(l => PRN_TAGS.some(t => l.includes(t)))
  let tags     = idx >= 0 ? PRN_TAGS.filter(t => lines[idx].includes(t)) : []
  if (idx >= 0) lines.splice(idx, 1)
  tags = active ? [...new Set([...tags, tag])] : tags.filter(t => t !== tag)
  const other  = lines.filter(l => l.trim())
  return [...other, ...(tags.length ? [tags.join(', ')] : [])].join('\n').trim()
}
function onRxPrnChange(item)      { item.item_note = updateRxPrnLine(item.item_note, 'Bila Perlu',    item.is_prn) }
function onRxCompleteChange(item) { item.item_note = updateRxPrnLine(item.item_note, 'Habiskan ubat', item.complete_course) }
function limitRxNoteLines(item)   { const ls = item.item_note.split('\n'); if (ls.length > 2) item.item_note = ls.slice(0, 2).join('\n') }

function submitRx() {
  rxForm.post(`/emr/${props.selected.id}/prescription`, {
    preserveScroll: true,
    onSuccess: () => {
      rxForm.reset()
      rxForm.items       = [emptyRxItem()]
      rxDrugSearch.value = ['']
      rxDrugOpen.value   = [false]
    },
  })
}

watch(() => props.selected?.id, () => {
  rxForm.reset()
  rxForm.items       = [emptyRxItem()]
  rxDrugSearch.value = ['']
  rxDrugOpen.value   = [false]
})

const showRxDelId = ref(null)
function deleteRx(rxId) {
  router.delete(`/emr/prescriptions/${rxId}`, {
    preserveScroll: true,
    onSuccess: () => { showRxDelId.value = null },
  })
}

/* ── Per-item edit ─────────────────────────────────── */
const editingItemId  = ref(null)
const editForm       = ref({})
const editDrugSearch = ref('')
const editDrugOpen   = ref(false)
const editSaving     = ref(false)

function openEditItem(item) {
  editingItemId.value  = item.id
  editDrugSearch.value = item.drug_name
  editDrugOpen.value   = false
  editForm.value = {
    inventory_item_id: item.inventory_item_id ?? null,
    drug_name:         item.drug_name,
    kegunaan:          item.kegunaan          ?? '',
    drug_unit:         item.drug_unit         ?? '',
    dosage:            item.dosage            ?? '',
    frequency:         item.frequency         ?? '',
    duration:          item.duration          ?? '',
    quantity:          item.quantity,
    instructions:      item.instructions      ?? '',
    is_prn:            item.is_prn            ?? false,
    complete_course:   item.complete_course   ?? false,
    item_note:         item.item_note         ?? '',
  }
}
function cancelEditItem() {
  editingItemId.value  = null
  editDrugSearch.value = ''
  editDrugOpen.value   = false
  editForm.value       = {}
}
function saveEditItem(itemId) {
  editSaving.value = true
  router.patch(`/emr/prescription-items/${itemId}`, editForm.value, {
    preserveScroll: true,
    onSuccess: () => { editingItemId.value = null; editForm.value = {} },
    onFinish:  () => { editSaving.value = false },
  })
}

function filteredEditDrugs() {
  const q = editDrugSearch.value.toLowerCase()
  if (!q) return props.drugItems.slice(0, 10)
  return props.drugItems.filter(d =>
    d.name.toLowerCase().includes(q) || (d.generic_name ?? '').toLowerCase().includes(q)
  ).slice(0, 10)
}
function selectEditDrug(inv) {
  editForm.value.inventory_item_id = inv.id
  editForm.value.drug_name         = inv.name
  editForm.value.drug_unit         = inv.form ?? ''
  editDrugSearch.value             = inv.name
  editDrugOpen.value               = false
}
function clearEditDrug() {
  editForm.value.inventory_item_id = null
  editForm.value.drug_name         = ''
  editDrugSearch.value             = ''
}
const resolvedEditInv = computed(() =>
  editForm.value.inventory_item_id
    ? props.drugItems.find(d => d.id === editForm.value.inventory_item_id) ?? null
    : null
)

/* ── Per-item delete ───────────────────────────────── */
const showItemDelId = ref(null)
function deleteItem(itemId) {
  router.delete(`/emr/prescription-items/${itemId}`, {
    preserveScroll: true,
    onSuccess: () => { showItemDelId.value = null },
  })
}

/* ── Services / Billing from EMR ─────────────────── */
const svcForm      = useForm({ type: 'consultation', description: '', quantity: 1, unit_price: '' })
const showSvcDelId = ref(null)

const QUICK_SERVICES = [
  { type: 'consultation', description: 'Yuran Perundingan',   unit_price: 20 },
  { type: 'consultation', description: 'Konsultasi Susulan',  unit_price: 15 },
  { type: 'procedure',    description: 'Pembersihan Luka',    unit_price: 30 },
  { type: 'procedure',    description: 'Suntikan',            unit_price: 25 },
  { type: 'lab',          description: 'Ujian Darah (FBC)',   unit_price: 50 },
  { type: 'lab',          description: 'Ujian Urin',          unit_price: 15 },
  { type: 'other',        description: 'Dressing',            unit_price: 10 },
]

const SVC_TYPES = {
  consultation: 'Perundingan',
  procedure:    'Prosedur',
  drug:         'Ubat',
  lab:          'Makmal',
  other:        'Lain-lain',
}

function pickService(s) {
  svcForm.type        = s.type
  svcForm.description = s.description
  svcForm.unit_price  = s.unit_price
  svcForm.quantity    = 1
}

const svcTotal = computed(() => Number(svcForm.quantity ?? 0) * Number(svcForm.unit_price ?? 0))

function addService() {
  svcForm.post(`/emr/${props.selected.id}/services`, {
    preserveScroll: true,
    onSuccess: () => { svcForm.reset(); svcForm.type = 'consultation'; svcForm.quantity = 1 },
  })
}

function deleteSvcItem(itemId) {
  router.delete(`/emr/${props.selected.id}/services/${itemId}`, {
    preserveScroll: true,
    onSuccess: () => { showSvcDelId.value = null },
  })
}

watch(() => props.selected?.id, () => {
  svcForm.reset()
  svcForm.type     = 'consultation'
  svcForm.quantity = 1
})

/* ── Close / Delete visit ─────────────────────────── */
const showCloseConfirm  = ref(false)
const showReopenConfirm = ref(false)
const showDeleteConfirm = ref(false)

function closeVisit() {
  router.patch(`/emr/${props.selected.id}/close`, {}, { preserveScroll: true, onSuccess: () => { showCloseConfirm.value = false } })
}
function reopenVisit() {
  router.patch(`/emr/${props.selected.id}/reopen`, {}, { preserveScroll: true, onSuccess: () => { showReopenConfirm.value = false } })
}
function deleteVisit() {
  router.delete(`/emr/${props.selected.id}`, { onSuccess: () => { showDeleteConfirm.value = false } })
}

/* ── Helpers ─────────────────────────────────────── */
const SOAP_LABELS = { S: 'Subjective', O: 'Objective', A: 'Assessment', P: 'Plan', Rx: 'Preskripsi', Bil: 'Perkhidmatan' }
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
            <Badge :tone="v.status === 'open' ? 'yellow' : v.status === 'reopened' ? 'orange' : 'green'" style="font-size:9px;padding:1px 5px">{{ v.status === 'open' ? t('emr_filter_open') : v.status === 'reopened' ? t('emr_filter_reopened') : t('emr_filter_closed') }}</Badge>
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

              <!-- S / O / A / P text areas -->
              <div v-if="soapTab !== 'Rx' && soapTab !== 'Bil'" class="card__body soap-body">
                <textarea
                  v-model="soapForm[SOAP_FIELDS[soapTab]]"
                  class="input soap-textarea"
                  :placeholder="soapHints[soapTab]"
                  :disabled="selected.status === 'closed'"
                  @input="soapDirty = true"
                ></textarea>
                <div class="row" style="margin-top:8px;gap:6px;flex-shrink:0">
                  <Btn v-if="selected.status === 'open' || selected.status === 'reopened'" variant="primary" size="sm"
                       :disabled="soapForm.processing || !soapDirty" @click="saveSoap">
                    {{ soapForm.processing ? t('emr_saving') : t('emr_save_soap') }}
                  </Btn>
                  <span v-if="soapDirty" style="font:500 11px var(--font-sans);color:var(--brand-orange);padding-top:2px">
                    {{ t('emr_soap_unsaved') }}
                  </span>
                </div>
              </div>

              <!-- Rx tab: inline prescription form -->
              <div v-else-if="soapTab === 'Rx'" class="rx-inline-wrap">

                <!-- Existing prescriptions -->
                <template v-if="selected.prescriptions?.length">
                  <div class="rx-inline-title">Preskripsi Sedia Ada</div>
                  <div v-for="rx in selected.prescriptions" :key="rx.id" class="rx-existing-card">
                    <div class="rx-existing-card__head">
                      <span class="rx-existing-card__num">{{ rx.rx_number }}</span>
                      <span :class="['rx-status-chip', `rx-status--${rx.status}`]">{{ RX_STATUS_LABEL[rx.status] ?? rx.status }}</span>
                      <span class="rx-existing-card__date">{{ rx.created_at }}</span>
                      <a :href="`/pharmacy/prescriptions/${rx.id}/print`" target="_blank" class="rx-existing-print">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Cetak
                      </a>
                      <button v-if="rx.status === 'draft'" class="rx-del-btn" @click="showRxDelId = rx.id" title="Padam preskripsi">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                      </button>
                    </div>
                    <template v-for="item in rx.items" :key="item.id">

                      <!-- ── Inline edit form ── -->
                      <div v-if="editingItemId === item.id" class="rx-item-edit-wrap">

                        <!-- Row 1: Nama + Kegunaan -->
                        <div class="rx-row rx-row--2">
                          <div class="field">
                            <label class="field__label">Nama Ubat</label>
                            <div style="position:relative">
                              <div v-if="editForm.inventory_item_id" class="drug-linked-bar">
                                <span class="drug-linked-name">{{ editForm.drug_name }}</span>
                                <span v-if="resolvedEditInv" :class="['drug-stock-badge', resolvedEditInv.stock_quantity <= 0 ? 'drug-stock-badge--out' : resolvedEditInv.stock_quantity <= 10 ? 'drug-stock-badge--low' : 'drug-stock-badge--ok']">
                                  Stok: {{ resolvedEditInv.stock_quantity }}
                                </span>
                                <button type="button" class="drug-clear-btn" @click="clearEditDrug">✕</button>
                              </div>
                              <template v-else>
                                <input v-model="editDrugSearch" class="input input--sm"
                                       placeholder="Cari atau taip nama ubat..."
                                       autocomplete="off"
                                       @input="editDrugOpen = true; editForm.drug_name = editDrugSearch"
                                       @focus="editDrugOpen = true"
                                       @blur="setTimeout(() => { editDrugOpen = false }, 180)" />
                                <div v-if="editDrugOpen && filteredEditDrugs().length" class="drug-dropdown">
                                  <button v-for="inv in filteredEditDrugs()" :key="inv.id"
                                          type="button" class="drug-option"
                                          :class="{ 'drug-option--out': inv.stock_quantity <= 0 }"
                                          @mousedown.prevent="selectEditDrug(inv)">
                                    <div class="drug-option__name">{{ inv.name }}</div>
                                    <div class="drug-option__meta">
                                      <span v-if="inv.generic_name" style="color:var(--fg3)">{{ inv.generic_name }}</span>
                                      <span v-if="inv.form"> · {{ inv.form }}</span>
                                      <span :class="['drug-option__stock', inv.stock_quantity <= 0 ? 'out' : inv.stock_quantity <= 10 ? 'low' : '']">Stok: {{ inv.stock_quantity }}</span>
                                      <span v-if="inv.selling_price > 0" class="drug-option__price">RM {{ Number(inv.selling_price).toFixed(2) }}</span>
                                    </div>
                                  </button>
                                </div>
                              </template>
                            </div>
                          </div>
                          <div class="field">
                            <label class="field__label">Kegunaan</label>
                            <input v-model="editForm.kegunaan" class="input input--sm" placeholder="cth: Demam" />
                          </div>
                        </div>

                        <!-- Row 2: Bentuk · Dos · Kekerapan · Tempoh -->
                        <div class="rx-row rx-row--4">
                          <div class="field">
                            <label class="field__label">Bentuk Ubat</label>
                            <select v-model="editForm.drug_unit" class="input input--sm">
                              <option value="">— Pilih —</option>
                              <option v-for="u in DRUG_UNITS" :key="u.code" :value="u.code">{{ u.label }}</option>
                            </select>
                          </div>
                          <div class="field">
                            <label class="field__label">Dos</label>
                            <input v-model="editForm.dosage" class="input input--sm" placeholder="1 tablet" />
                          </div>
                          <div class="field">
                            <label class="field__label">Kekerapan</label>
                            <input v-model="editForm.frequency" class="input input--sm" placeholder="OD / BD" list="edit-freq-list" />
                            <datalist id="edit-freq-list">
                              <option v-for="f in RX_FREQS" :key="f" :value="f" />
                            </datalist>
                          </div>
                          <div class="field">
                            <label class="field__label">Tempoh</label>
                            <input v-model="editForm.duration" class="input input--sm" placeholder="7 hari" />
                          </div>
                        </div>

                        <!-- Row 3: Qty · Arahan · PRN · Habiskan -->
                        <div class="rx-row rx-row--footer">
                          <div class="field rx-field--qty">
                            <label class="field__label">Kuantiti</label>
                            <input v-model.number="editForm.quantity" type="number" min="1" class="input input--sm" style="text-align:center" />
                          </div>
                          <div class="field rx-field--instr">
                            <label class="field__label">Arahan</label>
                            <input v-model="editForm.instructions" class="input input--sm" placeholder="Selepas makan" list="edit-instr-list" />
                            <datalist id="edit-instr-list">
                              <option v-for="ins in RX_INSTRS" :key="ins" :value="ins" />
                            </datalist>
                          </div>
                          <label class="rx-toggle">
                            <input type="checkbox" v-model="editForm.is_prn" @change="onRxPrnChange(editForm)" />
                            <span class="rx-toggle__txt"><strong>PRN</strong><small>Bila Perlu</small></span>
                          </label>
                          <label class="rx-toggle">
                            <input type="checkbox" v-model="editForm.complete_course" @change="onRxCompleteChange(editForm)" />
                            <span class="rx-toggle__txt"><strong>Habis</strong><small>Habiskan</small></span>
                          </label>
                        </div>

                        <!-- Row 4: Nota -->
                        <div class="rx-row rx-row--note">
                          <div class="field">
                            <label class="field__label" style="font-size:10px;color:var(--fg3)">Nota Ubat</label>
                            <textarea v-model="editForm.item_note" class="input input--sm rx-item-note"
                                      rows="2" style="resize:none" @input="limitRxNoteLines(editForm)"
                                      placeholder="cth: Simpan dalam peti sejuk..."></textarea>
                          </div>
                        </div>

                        <!-- Edit actions -->
                        <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px">
                          <button type="button" class="rx-edit-cancel" @click="cancelEditItem">Batal</button>
                          <button type="button" class="rx-edit-save" :disabled="editSaving || !editForm.drug_name" @click="saveEditItem(item.id)">
                            {{ editSaving ? 'Menyimpan...' : 'Simpan' }}
                          </button>
                        </div>
                      </div>

                      <!-- ── Display row ── -->
                      <div v-else class="rx-existing-drug">
                        <span class="rx-existing-drug__dot"></span>
                        <span class="rx-existing-drug__name">{{ item.drug_name }}</span>
                        <span v-if="item.kegunaan" class="rx-existing-drug__use">– {{ item.kegunaan }}</span>
                        <span v-if="item.dosage || item.frequency" class="rx-existing-drug__meta">
                          {{ [item.dosage, item.frequency].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-if="item.is_prn" class="rx-existing-drug__tag rx-existing-drug__tag--prn">PRN</span>
                        <span v-if="item.complete_course" class="rx-existing-drug__tag rx-existing-drug__tag--cc">Habis</span>
                        <span class="rx-existing-drug__qty">× {{ item.quantity }}</span>
                        <div v-if="rx.status === 'draft'" class="rx-existing-drug__actions">
                          <button class="rx-item-edit-btn" @click="openEditItem(item)" title="Edit ubat">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                          </button>
                          <button class="rx-item-del-btn" @click="showItemDelId = item.id" title="Padam ubat">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                          </button>
                        </div>
                      </div>

                      <!-- Item note (display only) -->
                      <div v-if="editingItemId !== item.id && item.item_note"
                           style="font:400 10.5px var(--font-sans);color:var(--fg3);padding-left:12px;margin-top:-1px">
                        ✎ {{ item.item_note }}
                      </div>
                    </template>
                  </div>
                  <div class="hr" style="margin:12px 0"></div>
                </template>

                <!-- New prescription form (open visits only) -->
                <template v-if="selected.status === 'open' || selected.status === 'reopened'">
                  <div class="rx-inline-title">Tambah Preskripsi Baru</div>

                  <div v-for="(item, i) in rxForm.items" :key="i" class="rx-drug-card">
                    <!-- Card header -->
                    <div class="rx-drug-card__head">
                      <span class="rx-drug-card__num">Ubat {{ i + 1 }}</span>
                      <button type="button" class="rx-remove-btn" @click="removeRxItem(i)" :disabled="rxForm.items.length === 1">×</button>
                    </div>

                    <!-- Row 1: Nama Ubat + Kegunaan -->
                    <div class="rx-row rx-row--2">
                      <div class="field">
                        <label class="field__label">Nama Ubat <span style="color:var(--brand-red)">*</span></label>
                        <div style="position:relative">
                          <div v-if="item.inventory_item_id" class="drug-linked-bar">
                            <span class="drug-linked-name">{{ item.drug_name }}</span>
                            <span v-if="resolvedRxInv(item)" :class="['drug-stock-badge', resolvedRxInv(item).stock_quantity <= 0 ? 'drug-stock-badge--out' : resolvedRxInv(item).stock_quantity <= 10 ? 'drug-stock-badge--low' : 'drug-stock-badge--ok']">
                              Stok: {{ resolvedRxInv(item).stock_quantity }}
                            </span>
                            <button type="button" class="drug-clear-btn" @click="clearRxDrug(i)" title="Tukar ubat">✕</button>
                          </div>
                          <template v-else>
                            <input
                              v-model="rxDrugSearch[i]"
                              class="input input--sm"
                              placeholder="Cari inventori atau taip nama ubat..."
                              autocomplete="off"
                              @input="rxDrugOpen[i] = true; item.drug_name = rxDrugSearch[i]"
                              @focus="rxDrugOpen[i] = true"
                              @blur="setTimeout(() => { rxDrugOpen[i] = false }, 180)"
                            />
                            <div v-if="rxDrugOpen[i] && filteredRxDrugs(i).length" class="drug-dropdown">
                              <button
                                v-for="inv in filteredRxDrugs(i)" :key="inv.id"
                                type="button"
                                class="drug-option"
                                :class="{ 'drug-option--out': inv.stock_quantity <= 0 }"
                                @mousedown.prevent="selectRxDrug(i, inv)"
                              >
                                <div class="drug-option__name">{{ inv.name }}</div>
                                <div class="drug-option__meta">
                                  <span v-if="inv.generic_name" style="color:var(--fg3)">{{ inv.generic_name }}</span>
                                  <span v-if="inv.form"> · {{ inv.form }}</span>
                                  <span :class="['drug-option__stock', inv.stock_quantity <= 0 ? 'out' : inv.stock_quantity <= 10 ? 'low' : '']">Stok: {{ inv.stock_quantity }}</span>
                                  <span v-if="inv.selling_price > 0" class="drug-option__price">RM {{ Number(inv.selling_price).toFixed(2) }}</span>
                                </div>
                              </button>
                            </div>
                          </template>
                        </div>
                        <span v-if="rxForm.errors[`items.${i}.drug_name`]" class="field__error">Wajib diisi</span>
                      </div>
                      <div class="field">
                        <label class="field__label">Kegunaan</label>
                        <input v-model="item.kegunaan" class="input input--sm" placeholder="cth: Demam, Sakit Kepala" />
                      </div>
                    </div>

                    <!-- Row 2: Bentuk · Dos · Kekerapan · Tempoh -->
                    <div class="rx-row rx-row--4">
                      <div class="field">
                        <label class="field__label">Bentuk Ubat</label>
                        <select v-model="item.drug_unit" class="input input--sm">
                          <option value="">— Pilih —</option>
                          <option v-for="u in DRUG_UNITS" :key="u.code" :value="u.code">{{ u.label }}</option>
                        </select>
                      </div>
                      <div class="field">
                        <label class="field__label">Dos</label>
                        <input v-model="item.dosage" class="input input--sm" placeholder="1 tablet" />
                      </div>
                      <div class="field">
                        <label class="field__label">Kekerapan</label>
                        <input v-model="item.frequency" class="input input--sm" placeholder="OD / BD / TDS" list="emr-freq-list" />
                        <datalist id="emr-freq-list">
                          <option v-for="f in RX_FREQS" :key="f" :value="f" />
                        </datalist>
                      </div>
                      <div class="field">
                        <label class="field__label">Tempoh</label>
                        <input v-model="item.duration" class="input input--sm" placeholder="7 hari" />
                      </div>
                    </div>

                    <!-- Row 3: Qty · Arahan · PRN · Habiskan -->
                    <div class="rx-row rx-row--footer">
                      <div class="field rx-field--qty">
                        <label class="field__label">Kuantiti <span style="color:var(--brand-red)">*</span></label>
                        <input v-model.number="item.quantity" type="number" min="1" class="input input--sm" style="text-align:center" />
                      </div>
                      <div class="field rx-field--instr">
                        <label class="field__label">Arahan</label>
                        <input v-model="item.instructions" class="input input--sm" placeholder="Selepas makan" list="emr-instr-list" />
                        <datalist id="emr-instr-list">
                          <option v-for="ins in RX_INSTRS" :key="ins" :value="ins" />
                        </datalist>
                      </div>
                      <label class="rx-toggle">
                        <input type="checkbox" v-model="item.is_prn" @change="onRxPrnChange(item)" />
                        <span class="rx-toggle__txt">
                          <strong>PRN</strong>
                          <small>Bila Perlu</small>
                        </span>
                      </label>
                      <label class="rx-toggle">
                        <input type="checkbox" v-model="item.complete_course" @change="onRxCompleteChange(item)" />
                        <span class="rx-toggle__txt">
                          <strong>Habis</strong>
                          <small>Habiskan</small>
                        </span>
                      </label>
                    </div>

                    <!-- Row 4: Nota ubat -->
                    <div class="rx-row rx-row--note">
                      <div class="field">
                        <label class="field__label" style="font-size:10px;color:var(--fg3)">Nota Ubat</label>
                        <textarea
                          v-model="item.item_note"
                          class="input input--sm rx-item-note"
                          rows="2"
                          placeholder="cth: Simpan dalam peti sejuk, elakkan cahaya matahari..."
                          style="resize:none"
                          @input="limitRxNoteLines(item)"
                        ></textarea>
                      </div>
                    </div>
                  </div>

                  <!-- Add drug button -->
                  <button type="button" class="rx-add-btn" @click="addRxItem">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Ubat
                  </button>

                  <!-- Notes -->
                  <div class="field" style="margin-top:14px">
                    <label class="field__label">Nota Farmasi (pilihan)</label>
                    <textarea v-model="rxForm.notes" class="input input--sm" rows="2" placeholder="Nota khas untuk ahli farmasi..." style="resize:vertical"></textarea>
                  </div>

                  <!-- Submit -->
                  <div class="rx-submit-row">
                    <div class="rx-pending-hint">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                      Ubat akan dihantar ke farmasi apabila rekod EMR ditutup.
                    </div>
                    <Btn variant="primary" :disabled="rxForm.processing || !rxForm.items.some(i => i.drug_name)" @click="submitRx">
                      {{ rxForm.processing ? 'Menyimpan...' : 'Simpan Ubat' }}
                    </Btn>
                  </div>
                </template>

                <div v-else-if="!selected.prescriptions?.length"
                     style="padding:24px 0;font:500 12px var(--font-sans);color:var(--fg3);text-align:center">
                  Tiada preskripsi untuk lawatan ini.
                </div>

              </div><!-- /rx-inline-wrap -->

              <!-- Bil (Perkhidmatan) tab -->
              <div v-else class="rx-inline-wrap">

                <!-- Existing service items -->
                <template v-if="selected.services?.items?.length">
                  <div class="rx-inline-title" style="display:flex;align-items:center;gap:8px">
                    <span>Bil {{ selected.services.invoice_number }}</span>
                    <span v-if="selected.services.status === 'emr_draft'"
                          class="rx-status-chip rx-status--draft" style="font-style:normal">
                      Dalam EMR
                    </span>
                    <span v-else-if="selected.services.status === 'draft'"
                          class="rx-status-chip rx-status--pending">
                      Dihantar ke Bahagian Bil
                    </span>
                    <span v-else-if="selected.services.status === 'unpaid'"
                          class="rx-status-chip rx-status--verifying">
                      Menunggu Bayaran
                    </span>
                    <span v-else-if="selected.services.status === 'paid'"
                          class="rx-status-chip rx-status--ready">
                      Telah Dibayar
                    </span>
                    <span style="margin-left:auto;font:700 13px var(--font-mono);color:var(--brand-green-dark)">
                      RM {{ Number(selected.services.total_amount ?? 0).toFixed(2) }}
                    </span>
                  </div>
                  <div class="svc-item-list">
                    <div v-for="item in selected.services.items" :key="item.id" class="svc-item">
                      <span :class="['svc-type-badge', `svc-type-${item.type}`]">{{ SVC_TYPES[item.type] ?? item.type }}</span>
                      <span class="svc-item__desc">{{ item.description }}</span>
                      <span class="svc-item__meta">{{ item.quantity }} × RM {{ Number(item.unit_price).toFixed(2) }}</span>
                      <span class="svc-item__total">RM {{ Number(item.total_price).toFixed(2) }}</span>
                      <button v-if="selected.services.status === 'emr_draft'" class="rx-item-del-btn" @click="showSvcDelId = item.id" title="Padam item">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                      </button>
                    </div>
                  </div>
                  <div class="hr" style="margin:12px 0"></div>
                </template>

                <!-- Add service form (open visits only) -->
                <template v-if="selected.status === 'open' || selected.status === 'reopened'">
                  <div class="rx-inline-title">Tambah Perkhidmatan</div>

                  <!-- Quick picks -->
                  <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px">
                    <button v-for="s in QUICK_SERVICES" :key="s.description"
                            type="button"
                            :class="['svc-chip', svcForm.description === s.description ? 'svc-chip--active' : '']"
                            @click="pickService(s)">
                      {{ s.description }}
                      <span class="svc-chip__price">RM {{ s.unit_price }}</span>
                    </button>
                  </div>

                  <!-- Form -->
                  <div class="svc-form-grid">
                    <div class="field">
                      <label class="field__label">Jenis *</label>
                      <select v-model="svcForm.type" class="input input--sm">
                        <option v-for="(label, key) in SVC_TYPES" :key="key" :value="key">{{ label }}</option>
                      </select>
                    </div>
                    <div class="field" style="grid-column:2/4">
                      <label class="field__label">Penerangan *</label>
                      <input v-model="svcForm.description" class="input input--sm" placeholder="cth: Yuran Perundingan" maxlength="255" />
                      <span v-if="svcForm.errors.description" class="field__error">{{ svcForm.errors.description }}</span>
                    </div>
                    <div class="field">
                      <label class="field__label">Kuantiti</label>
                      <input v-model.number="svcForm.quantity" type="number" min="0.01" step="0.01" class="input input--sm" style="text-align:center" />
                    </div>
                    <div class="field">
                      <label class="field__label">Harga Seunit (RM)</label>
                      <input v-model.number="svcForm.unit_price" type="number" min="0" step="0.01" class="input input--sm" />
                      <span v-if="svcForm.errors.unit_price" class="field__error">{{ svcForm.errors.unit_price }}</span>
                    </div>
                    <div class="field" style="display:flex;align-items:flex-end">
                      <div class="svc-line-total">RM {{ svcTotal.toFixed(2) }}</div>
                    </div>
                  </div>

                  <div class="rx-submit-row" style="margin-top:12px">
                    <div class="rx-pending-hint">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                      Perkhidmatan akan dihantar ke bahagian bil apabila rekod EMR ditutup.
                    </div>
                    <Btn variant="primary" size="sm"
                         :disabled="svcForm.processing || !svcForm.description || !svcForm.unit_price"
                         @click="addService">
                      {{ svcForm.processing ? 'Menyimpan...' : 'Tambah ke Bil' }}
                    </Btn>
                  </div>
                </template>

                <div v-else-if="!selected.services?.items?.length"
                     style="padding:24px 0;font:500 12px var(--font-sans);color:var(--fg3);text-align:center">
                  Tiada perkhidmatan dalam bil untuk lawatan ini.
                </div>

              </div><!-- /bil tab -->
            </div>

            <!-- Diagnoses -->
            <div class="card">
              <div class="card__header">
                <h3 class="card__title">{{ t('emr_dx') }}</h3>
                <div class="spacer"></div>
                <Btn v-if="selected.status === 'open' || selected.status === 'reopened'" variant="ghost" size="sm" @click="showDxForm = !showDxForm">
                  {{ showDxForm ? t('btn_close') : t('emr_add_dx') }}
                </Btn>
              </div>

              <!-- Add diagnosis form -->
              <div v-if="showDxForm && (selected.status === 'open' || selected.status === 'reopened')" class="card__body" style="border-bottom:1px solid var(--border)">
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
                  <button v-if="selected.status === 'open' || selected.status === 'reopened'"
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
                  <Badge :tone="selected.status === 'open' ? 'yellow' : selected.status === 'reopened' ? 'orange' : 'green'">
                    {{ selected.status === 'open' ? t('emr_status_open') : selected.status === 'reopened' ? t('emr_status_reopened') : t('emr_status_closed') }}
                  </Badge>
                </div>
                <div v-if="selected.signed_by" class="info-row">
                  <span class="info-label">{{ t('emr_signed_label') }}</span>
                  <span class="info-val" style="font-size:11px">{{ selected.signed_by }}<br>{{ selected.signed_at }}</span>
                </div>
                <div v-if="selected.reopened_by" class="info-row">
                  <span class="info-label">{{ t('emr_reopened_label') }}</span>
                  <span class="info-val" style="font-size:11px">{{ selected.reopened_by }}<br>{{ selected.reopened_at }}</span>
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
                <template v-else-if="selected.status === 'closed'">
                  <Btn variant="primary" style="width:100%;justify-content:center" @click="showReopenConfirm = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                    {{ t('emr_reopen_record') }}
                  </Btn>
                  <div style="background:var(--brand-green-light);border:1px solid var(--brand-green);border-radius:8px;padding:10px 12px;font:500 12px var(--font-sans);color:var(--brand-green-dark);text-align:center">
                    {{ t('emr_record_closed') }}
                  </div>
                </template>
                <template v-else-if="selected.status === 'reopened'">
                  <Btn variant="primary" style="width:100%;justify-content:center" @click="showCloseConfirm = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ t('emr_close_sign') }}
                  </Btn>
                  <Btn variant="secondary" style="width:100%;justify-content:center" @click="saveSoap" :disabled="soapForm.processing">
                    {{ t('emr_save_soap') }}
                  </Btn>
                  <div style="background:var(--brand-orange-light);border:1px solid var(--brand-orange);border-radius:8px;padding:10px 12px;font:500 12px var(--font-sans);color:var(--brand-orange-dark);text-align:center">
                    {{ t('emr_record_reopened') }}
                  </div>
                </template>
                <div class="hr"></div>
                <Btn variant="ghost" style="width:100%;justify-content:center;color:var(--brand-red)" @click="showDeleteConfirm = true">
                  {{ t('emr_delete_record') }}
                </Btn>
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

    <!-- ── Rx Delete confirm ─────────────────────────── -->
    <Teleport to="body">
      <div v-if="showRxDelId !== null" class="modal-backdrop" @click.self="showRxDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">Padam Preskripsi?</h3>
            <button class="modal__close" @click="showRxDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 13px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              Preskripsi
              <strong class="mono">{{ selected?.prescriptions?.find(r => r.id === showRxDelId)?.rx_number }}</strong>
              akan dipadam sepenuhnya.
            </p>
            <p style="font:400 12px var(--font-sans);color:var(--fg3);margin:0">Tindakan ini tidak boleh dibatalkan.</p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showRxDelId = null">Batal</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteRx(showRxDelId)">Padam</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Prescription item delete confirm ─────────── -->
    <Teleport to="body">
      <div v-if="showItemDelId !== null" class="modal-backdrop" @click.self="showItemDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">Padam Ubat?</h3>
            <button class="modal__close" @click="showItemDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 13px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              <strong>{{ selected?.prescriptions?.flatMap(r => r.items).find(i => i.id === showItemDelId)?.drug_name }}</strong>
              akan dibuang dari preskripsi.
            </p>
            <p style="font:400 12px var(--font-sans);color:var(--fg3);margin:0">
              Jika ini adalah ubat terakhir, keseluruhan preskripsi akan dipadam.
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showItemDelId = null">Batal</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteItem(showItemDelId)">Padam</Btn>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Service item delete confirm ─────────────── -->
    <Teleport to="body">
      <div v-if="showSvcDelId !== null" class="modal-backdrop" @click.self="showSvcDelId = null">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title" style="color:var(--brand-red)">Padam Item Bil?</h3>
            <button class="modal__close" @click="showSvcDelId = null">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 13px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              <strong>{{ selected?.services?.items?.find(i => i.id === showSvcDelId)?.description }}</strong>
              akan dibuang dari bil.
            </p>
            <p style="font:400 12px var(--font-sans);color:var(--fg3);margin:0">Tindakan ini tidak boleh dibatalkan.</p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showSvcDelId = null">Batal</Btn>
              <Btn variant="primary" style="background:var(--brand-red)" @click="deleteSvcItem(showSvcDelId)">Padam</Btn>
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

    <!-- ── Reopen confirm ─────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showReopenConfirm" class="modal-backdrop" @click.self="showReopenConfirm = false">
        <div class="modal modal--sm">
          <div class="modal__header">
            <h3 class="modal__title">{{ t('emr_confirm_reopen') }}</h3>
            <button class="modal__close" @click="showReopenConfirm = false">✕</button>
          </div>
          <div class="modal__body">
            <p style="font:400 14px var(--font-sans);color:var(--fg2);margin:0 0 6px">
              {{ t('emr_reopen_body_patient', { name: selected?.patient_name }) }}
            </p>
            <div class="modal__footer">
              <Btn variant="secondary" @click="showReopenConfirm = false">{{ t('btn_cancel') }}</Btn>
              <Btn variant="primary" @click="reopenVisit">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                {{ t('emr_confirm_reopen_btn') }}
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

/* ── Rx status chip ── */
.rx-status-chip {
  display: inline-flex; align-items: center;
  padding: 1px 7px; border-radius: 999px;
  font: 600 9.5px var(--font-sans); text-transform: uppercase; letter-spacing: .04em;
  border: 1px solid var(--border); background: var(--bg-soft); color: var(--fg2);
}
.rx-status--draft     { background:#F1F5F9; border-color:#CBD5E1; color:#475569; font-style:italic; }
.rx-status--pending   { background:#FFF7ED; border-color:#FED7AA; color:#C2410C; }
.rx-status--verifying { background:#EFF6FF; border-color:#93C5FD; color:#1D4ED8; }
.rx-status--ready     { background:var(--brand-green-light); border-color:var(--brand-green); color:var(--brand-green-dark); }
.rx-status--dispensed { background:#F9FAFB; border-color:#D1D5DB; color:#6B7280; }
.rx-status--cancelled { background:#F9FAFB; border-color:#D1D5DB; color:#9CA3AF; }

/* ── Right column "+" hint button ── */
.rx-tab-hint {
  font: 600 11px var(--font-sans); color: var(--brand-green-dark);
  background: var(--brand-green-light); border: 1px solid var(--brand-green);
  border-radius: 6px; padding: 3px 9px; cursor: pointer;
}
.rx-tab-hint:hover { background: var(--brand-green); color: #fff; }

/* ── Rx inline in SOAP card ── */
.rx-inline-wrap {
  flex: 1; overflow-y: auto; padding: 14px 18px;
  display: flex; flex-direction: column;
}
.rx-inline-title {
  font: 700 10.5px var(--font-sans); text-transform: uppercase; letter-spacing: .06em;
  color: var(--fg3); margin-bottom: 10px;
}

/* Existing prescription card */
.rx-existing-card {
  border: 1px solid var(--border); border-radius: 10px;
  padding: 10px 12px; margin-bottom: 8px; background: var(--bg-soft);
}
.rx-existing-card__head {
  display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;
}
.rx-existing-card__num  { font: 700 11px var(--font-mono); color: var(--brand-green-dark); }
.rx-existing-card__date { font: 500 10px var(--font-sans); color: var(--fg3); margin-left: auto; }
.rx-existing-print {
  display: inline-flex; align-items: center; gap: 3px;
  font: 600 11px var(--font-sans); color: var(--brand-green-dark);
  text-decoration: none; padding: 2px 7px;
  border: 1px solid var(--brand-green); border-radius: 4px;
  background: var(--brand-green-light);
}
.rx-existing-print:hover { background: var(--brand-green); color: #fff; }
.rx-del-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border: 1px solid var(--border); border-radius: 6px;
  background: #fff; color: var(--fg3); cursor: pointer; flex-shrink: 0;
}
.rx-del-btn:hover { background: #FEE2E2; color: #dc2626; border-color: #FECACA; }

/* Per-item inline edit */
.rx-item-edit-wrap {
  border: 1.5px solid var(--brand-green); border-radius: 10px;
  padding: 12px; margin: 6px 0; background: #fff;
}
.rx-existing-drug__actions {
  display: flex; gap: 3px; margin-left: auto; flex-shrink: 0;
}
.rx-item-edit-btn, .rx-item-del-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; border-radius: 5px; border: 1px solid var(--border);
  background: #fff; cursor: pointer; color: var(--fg3);
}
.rx-item-edit-btn:hover { background: #EFF6FF; color: #1D4ED8; border-color: #93C5FD; }
.rx-item-del-btn:hover  { background: #FEE2E2; color: #dc2626; border-color: #FECACA; }
.rx-edit-save {
  padding: 5px 14px; border-radius: 7px; border: none;
  background: var(--brand-green); color: #fff;
  font: 600 12px var(--font-sans); cursor: pointer;
}
.rx-edit-save:hover:not(:disabled) { filter: brightness(1.1); }
.rx-edit-save:disabled { opacity: .5; cursor: default; }
.rx-edit-cancel {
  padding: 5px 14px; border-radius: 7px;
  border: 1px solid var(--border); background: #fff;
  color: var(--fg2); font: 500 12px var(--font-sans); cursor: pointer;
}
.rx-edit-cancel:hover { background: var(--bg-soft); }
.rx-submit-row {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-top: 14px; padding-bottom: 4px; flex-wrap: wrap;
}
.rx-pending-hint {
  display: flex; align-items: center; gap: 5px;
  font: 500 11px var(--font-sans); color: var(--fg3); flex: 1;
}
.rx-existing-drug {
  display: flex; align-items: baseline; gap: 5px;
  font: 500 12px var(--font-sans); color: var(--fg1); margin-bottom: 3px; flex-wrap: wrap;
}
.rx-existing-drug__dot  { width: 5px; height: 5px; border-radius: 50%; background: var(--brand-green); flex-shrink: 0; margin-top: 4px; }
.rx-existing-drug__name { font-weight: 600; }
.rx-existing-drug__use  { font: 400 11px var(--font-sans); color: var(--fg2); }
.rx-existing-drug__meta { font: 400 11px var(--font-sans); color: var(--fg3); }
.rx-existing-drug__qty  { font: 700 11px var(--font-mono); color: var(--brand-green-dark); margin-left: auto; }
.rx-existing-drug__tag  { font: 700 9px var(--font-sans); padding: 1px 5px; border-radius: 999px; }
.rx-existing-drug__tag--prn { background:#FFF7ED; color:#C2410C; border:1px solid #FED7AA; }
.rx-existing-drug__tag--cc  { background:#EFF6FF; color:#1D4ED8; border:1px solid #93C5FD; }

/* ── Drug entry card (matches Pharmacy) ── */
.rx-drug-card {
  border: 1.5px solid var(--border); border-radius: 12px;
  padding: 14px; margin-bottom: 10px; background: #fff;
}
.rx-drug-card:focus-within { border-color: var(--brand-green); box-shadow: 0 0 0 3px rgba(27,138,74,.08); }
.rx-drug-card__head {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;
}
.rx-drug-card__num {
  font: 700 10.5px var(--font-sans); text-transform: uppercase; letter-spacing: .07em;
  color: var(--brand-green-dark); background: var(--brand-green-light); padding: 3px 10px; border-radius: 999px;
}
.rx-remove-btn {
  width: 26px; height: 26px; border: 1px solid var(--border); border-radius: 6px;
  background: #fff; color: var(--fg3); cursor: pointer; font-size: 15px; display: grid; place-items: center;
}
.rx-remove-btn:hover:not(:disabled) { background: #FEE2E2; color: #dc2626; border-color: #FECACA; }
.rx-remove-btn:disabled { opacity: .3; cursor: default; }

/* Rows (matches Pharmacy rx-row layout) */
.rx-row { display: grid; gap: 10px; margin-bottom: 10px; }
.rx-row--2      { grid-template-columns: 1fr 1fr; }
.rx-row--4      { grid-template-columns: 1fr 0.8fr 1.1fr 0.9fr; }
.rx-row--footer { grid-template-columns: 70px 1fr auto auto; align-items: end; }
.rx-row--note   { grid-template-columns: 1fr; }
.rx-field--qty  { max-width: 70px; }

/* Linked drug bar */
.drug-linked-bar {
  display: flex; align-items: center; gap: 6px; min-height: 34px;
  padding: 5px 10px; border: 1.5px solid var(--brand-green);
  border-radius: 8px; background: var(--brand-green-light);
}
.drug-linked-name {
  flex: 1; font: 600 12.5px var(--font-sans); color: var(--brand-green-dark);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.drug-stock-badge { font: 700 10px var(--font-mono); padding: 1px 6px; border-radius: 999px; flex-shrink: 0; }
.drug-stock-badge--ok  { background: var(--brand-green-light); color: var(--brand-green-dark); border: 1px solid var(--brand-green); }
.drug-stock-badge--low { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }
.drug-stock-badge--out { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
.drug-clear-btn {
  width: 18px; height: 18px; border: 0; background: transparent; cursor: pointer;
  color: var(--brand-green-dark); font-size: 11px; display: grid; place-items: center; opacity: .7;
}
.drug-clear-btn:hover { opacity: 1; }

/* Drug search dropdown */
.drug-dropdown {
  position: absolute; top: calc(100% + 3px); left: 0; right: 0; z-index: 200;
  background: #fff; border: 1px solid var(--border); border-radius: 10px;
  box-shadow: var(--shadow-md); max-height: 220px; overflow-y: auto;
}
.drug-option {
  display: block; width: 100%; text-align: left; padding: 8px 12px;
  border: 0; background: transparent; cursor: pointer;
}
.drug-option:hover { background: var(--bg-soft); }
.drug-option + .drug-option { border-top: 1px solid var(--bg-muted); }
.drug-option--out { opacity: .5; }
.drug-option__name { display: block; font: 600 12.5px var(--font-sans); color: var(--fg1); }
.drug-option__meta { display: flex; align-items: center; gap: 6px; font: 500 11px var(--font-sans); margin-top: 2px; flex-wrap: wrap; }
.drug-option__stock { font: 700 10.5px var(--font-mono); padding: 1px 6px; border-radius: 999px; background: var(--brand-green-light); color: var(--brand-green-dark); }
.drug-option__stock.low { background: #FEF3C7; color: #92400E; }
.drug-option__stock.out { background: #FEE2E2; color: #991B1B; }
.drug-option__price { color: var(--brand-green-dark); font-weight: 700; margin-left: auto; }

/* Add drug button */
.rx-add-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 9px; margin-top: 4px;
  border: 1.5px dashed var(--border); border-radius: 10px;
  background: transparent; color: var(--brand-green-dark);
  font: 600 13px var(--font-sans); cursor: pointer;
}
.rx-add-btn:hover { background: var(--brand-green-light); border-color: var(--brand-green); }

/* PRN / Complete toggle */
.rx-toggle { display: flex; align-items: center; gap: 5px; cursor: pointer; padding-bottom: 1px; }
.rx-toggle input[type=checkbox] { width: 14px; height: 14px; accent-color: var(--brand-green); cursor: pointer; }
.rx-toggle__txt { display: flex; flex-direction: column; line-height: 1.2; }
.rx-toggle__txt strong { font: 700 11px var(--font-sans); color: var(--fg1); }
.rx-toggle__txt small  { font: 400 9.5px var(--font-sans); color: var(--fg3); }

/* Note textarea */
.rx-item-note { min-height: 44px; }

/* Smaller inputs */
.input--sm { font-size: 12px !important; padding: 6px 9px !important; }

/* ── Services (Bil) tab ── */
.svc-item-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 4px; }
.svc-item {
  display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
  padding: 7px 10px; border: 1px solid var(--border); border-radius: 8px; background: #fff;
}
.svc-item__desc  { flex: 1; font: 600 12.5px var(--font-sans); color: var(--fg1); min-width: 0; }
.svc-item__meta  { font: 400 11px var(--font-sans); color: var(--fg3); white-space: nowrap; }
.svc-item__total { font: 700 12px var(--font-mono); color: var(--brand-green-dark); white-space: nowrap; }

.svc-type-badge {
  display: inline-flex; align-items: center; padding: 1px 7px; border-radius: 999px;
  font: 600 9.5px var(--font-sans); text-transform: uppercase; letter-spacing: .04em;
  white-space: nowrap; flex-shrink: 0;
}
.svc-type-consultation { background: #EFF6FF; border: 1px solid #93C5FD; color: #1D4ED8; }
.svc-type-procedure    { background: #FFFBEB; border: 1px solid #FCD34D; color: #92400E; }
.svc-type-drug         { background: var(--brand-green-light); border: 1px solid var(--brand-green); color: var(--brand-green-dark); }
.svc-type-lab          { background: #F5F3FF; border: 1px solid #C4B5FD; color: #5B21B6; }
.svc-type-other        { background: var(--bg-soft); border: 1px solid var(--border); color: var(--fg2); }

.svc-chip {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border: 1px solid var(--border); border-radius: 999px;
  background: #fff; color: var(--fg2); font: 400 11px var(--font-sans); cursor: pointer;
}
.svc-chip:hover      { border-color: var(--brand-green); color: var(--brand-green-dark); }
.svc-chip--active    { background: var(--brand-green); border-color: var(--brand-green); color: #fff; }
.svc-chip__price     { font: 700 10px var(--font-mono); opacity: .7; }
.svc-chip--active .svc-chip__price { opacity: 1; }

.svc-form-grid {
  display: grid;
  grid-template-columns: 120px 1fr 1fr 100px 100px 90px;
  gap: 10px; align-items: start;
}
.svc-line-total {
  font: 700 14px var(--font-mono); color: var(--brand-green-dark);
  padding-bottom: 7px;
}

@media (max-width: 900px) {
  .rx-row--4      { grid-template-columns: 1fr 1fr; }
  .rx-row--footer { grid-template-columns: 70px 1fr; }
  .rx-toggle { margin-top: 8px; }
}
</style>
