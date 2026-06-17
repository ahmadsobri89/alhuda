<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { useLocale } from '@/composables/useLocale'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  queue:            { type: Array,  default: () => [] },
  history:          { type: Object, default: () => ({ data: [], links: [] }) },
  patients:         { type: Array,  default: () => [] },
  filters:          { type: Object, default: () => ({}) },
  allergiesInQueue: { type: Array,  default: () => [] },
  lookups:          { type: Object, default: () => ({}) },
  drugItems:        { type: Array,  default: () => [] },
})

const page  = usePage()
const flash = computed(() => page.props.flash?.success)
const { t } = useLocale()

const defaultDoctor = computed(() => {
  const u = page.props.auth?.user
  if (!u?.name) return ''
  return u.role === 'doctor' ? `Dr. ${u.name}` : u.name
})
const tab   = ref('queue')

// ─── History search & pagination ─────────────────────────────────────────────
const search  = ref(props.filters.search ?? '')
const perPage = ref(props.filters.per_page ?? 15)
const PER_PAGE_OPTIONS = [15, 30, 50, 100]
let searchTimer = null

function queryParams(extra = {}) {
  return {
    search: search.value || undefined,
    per_page: perPage.value !== 15 ? perPage.value : undefined,
    ...extra,
  }
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('pharmacy'), queryParams(), { preserveState: true, replace: true })
  }, 350)
})

function setPerPage() {
  router.get(route('pharmacy'), queryParams(), { preserveState: true, replace: true })
}

function goToPage(url) {
  if (url) router.get(url, queryParams(), { preserveState: true, preserveScroll: true })
}

// ─── Status helpers ────────────────────────────────────────────────────────
const STATUS_TONE = { pending: 'orange', verifying: 'blue', ready: 'green', dispensed: 'neutral', cancelled: 'neutral' }
const STATUS_LABEL = computed(() => ({
  pending: t('status_pending'), verifying: t('status_verifying'),
  ready: t('status_ready'), dispensed: t('status_dispensed'), cancelled: t('status_cancelled'),
}))

// ─── Patient search (for form) ─────────────────────────────────────────────
const patientSearch   = ref('')
const patientDropdown = ref(false)
const selectedPatient = ref(null)

const filteredPatients = computed(() =>
  patientSearch.value.length < 1 ? [] :
  props.patients.filter(p =>
    p.name.toLowerCase().includes(patientSearch.value.toLowerCase()) ||
    p.ic_number.includes(patientSearch.value) ||
    p.patient_id.includes(patientSearch.value)
  ).slice(0, 8)
)

function selectPatient(p) {
  selectedPatient.value = p
  rxForm.patient_id = p.id
  patientSearch.value = p.name
  patientDropdown.value = false
}

// ─── Rx Form ──────────────────────────────────────────────────────────────
const showModal    = ref(false)
const editingRx    = ref(null)

function emptyItem() {
  return { inventory_item_id: null, drug_name: '', kegunaan: '', drug_unit: '', dosage: '', frequency: '', duration: '', quantity: 1, instructions: '', item_note: '', is_prn: false, complete_course: false }
}

const rxForm = useForm({
  patient_id:         '',
  prescribing_doctor: defaultDoctor.value,
  notes:              '',
  items:              [emptyItem()],
})

// ─── Inventory drug search (per-item UI state, not submitted) ─────────────
const itemDrugSearch   = ref([])
const itemDrugDropdown = ref([])

function filteredDrugsFor(i) {
  const q = (itemDrugSearch.value[i] ?? '').toLowerCase()
  if (!q) return props.drugItems.slice(0, 12)
  return props.drugItems.filter(d =>
    d.name.toLowerCase().includes(q) ||
    (d.generic_name ?? '').toLowerCase().includes(q)
  ).slice(0, 12)
}

function selectDrug(i, inv) {
  rxForm.items[i].inventory_item_id = inv.id
  rxForm.items[i].drug_name         = inv.name
  rxForm.items[i].drug_unit         = inv.form ?? ''
  itemDrugSearch.value[i]           = inv.name
  itemDrugDropdown.value[i]         = false
}

function clearDrugSelection(i) {
  rxForm.items[i].inventory_item_id = null
  rxForm.items[i].drug_name         = ''
  itemDrugSearch.value[i]           = ''
  itemDrugDropdown.value[i]         = false
}

function resolvedInvItem(item) {
  if (!item.inventory_item_id) return null
  return props.drugItems.find(d => d.id === item.inventory_item_id) ?? null
}

function resetDrugSearchArrays(len) {
  itemDrugSearch.value   = Array(len).fill('')
  itemDrugDropdown.value = Array(len).fill(false)
}

function openCreate() {
  editingRx.value = null
  selectedPatient.value = null
  patientSearch.value = ''
  rxForm.reset()
  rxForm.items = [emptyItem()]
  rxForm.prescribing_doctor = defaultDoctor.value
  rxForm.clearErrors()
  resetDrugSearchArrays(1)
  showModal.value = true
}

function openEdit(rx) {
  editingRx.value = rx
  const p = props.patients.find(p => p.id === rx.patient_id)
  selectedPatient.value = p ?? null
  patientSearch.value = rx.patient_name
  rxForm.patient_id         = rx.patient_id
  rxForm.prescribing_doctor = rx.prescribing_doctor
  rxForm.notes              = rx.notes ?? ''
  rxForm.items              = rx.items.map(i => ({
    inventory_item_id: i.inventory_item_id ?? null,
    drug_name: i.drug_name, kegunaan: i.kegunaan ?? '', drug_unit: i.drug_unit ?? '',
    dosage: i.dosage ?? '', frequency: i.frequency ?? '', duration: i.duration ?? '',
    quantity: i.quantity, instructions: i.instructions ?? '', item_note: i.item_note ?? '',
    is_prn: i.is_prn ?? false, complete_course: i.complete_course ?? false,
  }))
  rxForm.clearErrors()
  resetDrugSearchArrays(rx.items.length)
  rx.items.forEach((it, idx) => { itemDrugSearch.value[idx] = it.drug_name })
  showModal.value = true
}

function closeModal() { showModal.value = false }

function submitRx() {
  if (editingRx.value) {
    rxForm.put(route('pharmacy.update', editingRx.value.id), { onSuccess: closeModal })
  } else {
    rxForm.post(route('pharmacy.store'), { onSuccess: closeModal })
  }
}

function addItem() {
  rxForm.items.push(emptyItem())
  itemDrugSearch.value.push('')
  itemDrugDropdown.value.push(false)
}
function removeItem(i) {
  rxForm.items.splice(i, 1)
  itemDrugSearch.value.splice(i, 1)
  itemDrugDropdown.value.splice(i, 1)
}

const PRN_TAGS = ['Bila Perlu', 'Habiskan ubat']

function updatePrnLine(note, tag, active) {
  const lines = (note ?? '').split('\n')
  const prnIdx = lines.findIndex(l => PRN_TAGS.some(t => l.includes(t)))
  let prnTags = prnIdx >= 0 ? PRN_TAGS.filter(t => lines[prnIdx].includes(t)) : []
  if (prnIdx >= 0) lines.splice(prnIdx, 1)
  prnTags = active ? [...new Set([...prnTags, tag])] : prnTags.filter(t => t !== tag)
  const other = lines.filter(l => l.trim())
  return [...other, ...(prnTags.length ? [prnTags.join(', ')] : [])].join('\n').trim()
}

function onPrnChange(item) {
  item.item_note = updatePrnLine(item.item_note, 'Bila Perlu', item.is_prn)
}
function onCompleteChange(item) {
  item.item_note = updatePrnLine(item.item_note, 'Habiskan ubat', item.complete_course)
}

function limitNoteLines(item) {
  const lines = item.item_note.split('\n')
  if (lines.length > 2) item.item_note = lines.slice(0, 2).join('\n')
}

const FREQUENCIES  = computed(() => (props.lookups?.kekerapan_dos ?? []).map(v => v.label_ms))
const INSTRUCTIONS = computed(() => (props.lookups?.arahan_dos    ?? []).map(v => v.label_ms))
const DRUG_UNITS   = computed(() => (props.lookups?.bentuk_ubat   ?? []).map(v => ({ code: v.code, label: v.label_ms })))

// ─── View Drawer ───────────────────────────────────────────────────────────
const viewRx = ref(null)

// ─── Status update ─────────────────────────────────────────────────────────
function setStatus(rx, status) {
  router.patch(route('pharmacy.status', rx.id), { status }, { preserveScroll: true })
}

// ─── Delete ────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)
function doDelete() {
  router.delete(route('pharmacy.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
  })
}

// ─── Dispense confirm ──────────────────────────────────────────────────────
const dispenseTarget = ref(null)
function confirmDispense(rx) { dispenseTarget.value = rx }
function doDispense() {
  setStatus(dispenseTarget.value, 'dispensed')
  dispenseTarget.value = null
  if (viewRx.value?.id === dispenseTarget.value?.id) viewRx.value = null
}
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-ok">{{ flash }}</div>

    <!-- AI Drug Check Banner -->
    <div class="ai-box">
      <Icon name="sparkle" :size="16" />
      <div>
        <div class="ai-box__title">
          {{ t('rx_ai_check') }} ·
          <span v-if="allergiesInQueue.length">
            {{ t('rx_allergy_detected', { list: allergiesInQueue.join(', ') }) }}
          </span>
          <span v-else>{{ t('rx_no_interaction') }}</span>
        </div>
        <div class="ai-box__body">
          {{ t('rx_ai_summary', { total: queue.length, ready: queue.filter(r=>r.status==='ready').length }) }}
        </div>
      </div>
    </div>

    <!-- Tabs + New Rx button -->
    <div class="row">
      <div class="tabs">
        <button :class="['tab', tab==='queue'   ? 'active':'']" @click="tab='queue'">{{ t('rx_queue_tab', { n: queue.length }) }}</button>
        <button :class="['tab', tab==='history' ? 'active':'']" @click="tab='history'">{{ t('rx_history_tab') }}</button>
      </div>
      <div class="spacer"></div>
      <Btn variant="primary" @click="openCreate"><Icon name="plus" :size="14" /> {{ t('rx_new') }}</Btn>
    </div>

    <!-- ── Queue Tab ────────────────────────────────────────────────────── -->
    <div v-if="tab==='queue'" class="card table-card">
      <div class="card__header">
        <h3 class="card__title">{{ t('rx_queue_title') }}</h3>
        <p class="card__sub">{{ t('rx_queue_waiting', { n: queue.length }) }}</p>
      </div>
      <div class="table-scroll">
      <div class="table__head" style="grid-template-columns:130px 1.8fr 60px 1.2fr 110px 110px 190px">
        <div>{{ t('rx_col_no') }}</div><div>{{ t('rx_col_patient') }}</div><div>{{ t('rx_col_drug') }}</div><div>{{ t('rx_col_doctor') }}</div><div>{{ t('rx_col_wait') }}</div><div>{{ t('rx_col_status') }}</div><div></div>
      </div>
      <div
        v-for="rx in queue" :key="rx.id"
        class="table__row"
        style="grid-template-columns:130px 1.8fr 60px 1.2fr 110px 110px 190px"
      >
        <div class="mono" style="font:700 12px var(--font-mono);color:var(--fg1)">{{ rx.rx_number }}</div>
        <div class="row" style="gap:8px">
          <Avatar :name="rx.patient_name" size="sm" />
          <div>
            <div style="font:600 13px var(--font-sans)">{{ rx.patient_name }}</div>
            <div v-if="rx.patient_allergies" style="font:500 11px var(--font-sans);color:var(--brand-red)">⚠ {{ rx.patient_allergies }}</div>
          </div>
        </div>
        <div class="mono" style="font:700 13px var(--font-mono);color:var(--fg1)">{{ rx.items.length }}</div>
        <div style="font:500 12.5px var(--font-sans);color:var(--fg2)">{{ rx.prescribing_doctor }}</div>
        <div class="mono" style="font:500 12px var(--font-mono);color:var(--fg3)">{{ rx.wait_time }}</div>
        <div><Badge :tone="STATUS_TONE[rx.status]">{{ STATUS_LABEL[rx.status] }}</Badge></div>
        <div class="row" style="gap:4px;justify-content:flex-end">
          <Btn variant="ghost" size="sm" @click="viewRx = rx">{{ t('rx_view') }}</Btn>
          <Btn v-if="rx.status==='pending'"    variant="ghost"   size="sm" @click="setStatus(rx,'verifying')">{{ t('rx_check') }}</Btn>
          <Btn v-if="rx.status==='verifying'"  variant="ghost"   size="sm" @click="setStatus(rx,'ready')">{{ t('rx_ready') }}</Btn>
          <Btn v-if="rx.status==='ready'"      variant="primary" size="sm" @click="confirmDispense(rx)">{{ t('rx_dispense') }}</Btn>
          <Btn v-if="rx.status!=='dispensed'"  variant="ghost"   size="sm" style="color:var(--fg3)" @click="setStatus(rx,'cancelled')">{{ t('rx_cancel') }}</Btn>
        </div>
      </div>
      <div v-if="!queue.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        {{ t('rx_queue_empty') }}
      </div>
      </div><!-- /.table-scroll -->
    </div>

    <!-- ── History Tab ──────────────────────────────────────────────────── -->
    <div v-if="tab==='history'" class="tab-panel">
      <div class="row">
        <div style="position:relative;flex:1;max-width:360px">
          <input v-model="search" class="input" :placeholder="t('rx_history_search_ph')" style="padding-left:36px" />
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--fg3)">
            <Icon name="search" :size="15" />
          </span>
        </div>
      </div>
      <div class="card table-card">
        <div class="table-scroll">
        <div class="table__head" style="grid-template-columns:130px 1.8fr 60px 1.2fr 150px 100px 120px">
          <div>{{ t('rx_col_no') }}</div><div>{{ t('rx_col_patient') }}</div><div>{{ t('rx_col_drug') }}</div><div>{{ t('rx_col_doctor') }}</div><div>{{ t('rx_col_wait') }}</div><div>{{ t('rx_col_status') }}</div><div></div>
        </div>
        <div
          v-for="rx in history.data" :key="rx.id"
          class="table__row"
          style="grid-template-columns:130px 1.8fr 60px 1.2fr 150px 100px 120px"
        >
          <div class="mono" style="font:700 12px var(--font-mono);color:var(--fg1)">{{ rx.rx_number }}</div>
          <div class="row" style="gap:8px">
            <Avatar :name="rx.patient_name" size="sm" />
            <div style="font:600 13px var(--font-sans)">{{ rx.patient_name }}</div>
          </div>
          <div class="mono" style="font:700 13px var(--font-mono);color:var(--fg1)">{{ rx.items.length }}</div>
          <div style="font:500 12.5px var(--font-sans);color:var(--fg2)">{{ rx.prescribing_doctor }}</div>
          <div>
            <div style="font:500 11.5px var(--font-sans);color:var(--fg2)">{{ rx.dispensed_at ?? rx.created_at }}</div>
            <div v-if="rx.dispensed_by" style="font:500 11px var(--font-sans);color:var(--fg3)">{{ rx.dispensed_by }}</div>
          </div>
          <div><Badge :tone="STATUS_TONE[rx.status]">{{ STATUS_LABEL[rx.status] }}</Badge></div>
          <div class="row" style="gap:4px">
            <Btn variant="ghost" size="sm" @click="viewRx = rx">{{ t('rx_view') }}</Btn>
            <a :href="`/pharmacy/prescriptions/${rx.id}/print`" target="_blank" class="rx-print-btn rx-print-btn--sm" :title="t('rx_print')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            </a>
            <a :href="`/pharmacy/prescriptions/${rx.id}/label`" target="_blank" class="rx-print-btn rx-print-btn--sm rx-print-btn--label" :title="t('rx_label')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            </a>
            <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="deleteTarget = rx">⊗</Btn>
          </div>
        </div>
        <div v-if="!history.data?.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
          {{ t('rx_no_history') }}
        </div>
        </div><!-- /.table-scroll -->
      </div>

      <!-- Pagination — bar sticky di bawah skrin -->
      <div v-if="history.data?.length" class="pagination">
        <div class="pagination__info">
          {{ t('pg_show') }}
          <select v-model.number="perPage" @change="setPerPage" class="per-page-select">
            <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
          </select>
          / {{ t('pg_per_page') }} · {{ history.from }}–{{ history.to }} {{ t('pg_of') }} {{ history.total }}
        </div>
        <div v-if="history.last_page > 1" class="pagination__pages">
          <button
            v-for="link in history.links" :key="link.label"
            :disabled="!link.url"
            :class="['page-btn', link.active ? 'active':'']"
            @click="goToPage(link.url)"
            v-html="link.label"
          ></button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── View Rx Drawer ──────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="viewRx" class="drawer-backdrop" @click.self="viewRx=null">
      <div class="drawer">
        <div class="drawer__header">
          <div style="flex:1;min-width:0">
            <div class="mono" style="font:500 11px var(--font-mono);color:var(--fg3)">{{ viewRx.rx_number }}</div>
            <h2 class="drawer__name">{{ viewRx.patient_name }}</h2>
            <div style="font:500 11.5px var(--font-mono);color:var(--fg3)">{{ viewRx.patient_ic }}</div>
          </div>
          <Badge :tone="STATUS_TONE[viewRx.status]" style="align-self:flex-start">{{ STATUS_LABEL[viewRx.status] }}</Badge>
          <button class="modal__close" @click="viewRx=null">✕</button>
        </div>

        <div class="drawer__body">
          <!-- Allergy alert -->
          <div v-if="viewRx.patient_allergies" class="allergy-alert">
            ⚠ Alahan: <strong>{{ viewRx.patient_allergies }}</strong>
          </div>

          <!-- Drug check -->
          <div v-if="viewRx.drug_check_notes" class="ai-box" style="margin-bottom:14px;font-size:12px">
            <Icon name="sparkle" :size="14" />
            <div>
              <div class="ai-box__title" style="font-size:12px">Semakan AI</div>
              <div class="ai-box__body" style="font-size:11.5px">{{ viewRx.drug_check_notes }}</div>
            </div>
          </div>

          <!-- Meta -->
          <div class="info-grid" style="margin-bottom:14px">
            <div class="info-row"><span class="info-label">{{ t('rx_draw_doctor') }}</span><span class="info-val">{{ viewRx.prescribing_doctor }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('rx_draw_time') }}</span><span class="info-val mono">{{ viewRx.created_at }}</span></div>
            <div v-if="viewRx.dispensed_by" class="info-row"><span class="info-label">{{ t('rx_draw_dispensed_by') }}</span><span class="info-val">{{ viewRx.dispensed_by }}</span></div>
            <div v-if="viewRx.dispensed_at" class="info-row"><span class="info-label">{{ t('rx_draw_dispensed_at') }}</span><span class="info-val mono">{{ viewRx.dispensed_at }}</span></div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Drug items -->
          <div class="drow-section-title">{{ t('rx_draw_drugs', { n: viewRx.items.length }) }}</div>
          <div class="drug-list">
            <div v-for="(item, i) in viewRx.items" :key="i" class="drug-card">
              <div class="drug-card__name">{{ item.drug_name }}</div>
              <div class="drug-card__meta">
                <span v-if="item.dosage">{{ item.dosage }}</span>
                <span v-if="item.frequency"> · {{ item.frequency }}</span>
                <span v-if="item.duration"> · {{ item.duration }}</span>
              </div>
              <div class="drug-card__footer">
                <span class="drug-card__qty">{{ t('rx_draw_qty', { n: item.quantity }) }}</span>
                <span v-if="item.instructions" class="drug-card__instr">{{ item.instructions }}</span>
              </div>
              <div v-if="item.item_note" class="drug-card__note">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                {{ item.item_note }}
              </div>
            </div>
          </div>

          <div v-if="viewRx.notes" style="margin-top:14px">
            <div class="drow-section-title">{{ t('rx_draw_notes') }}</div>
            <p style="font:400 12.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0">{{ viewRx.notes }}</p>
          </div>

          <div class="hr" style="margin:16px 0"></div>

          <!-- Actions -->
          <div class="row" style="gap:8px;flex-wrap:wrap">
            <template v-if="viewRx.status === 'pending'">
              <Btn variant="secondary" style="flex:1" @click="setStatus(viewRx,'verifying');viewRx=null">{{ t('rx_semak') }}</Btn>
            </template>
            <template v-if="viewRx.status === 'verifying'">
              <Btn variant="ghost" size="sm" @click="setStatus(viewRx,'pending');viewRx=null">← {{ t('btn_back') }}</Btn>
              <Btn variant="secondary" style="flex:1" @click="setStatus(viewRx,'ready');viewRx=null">{{ t('rx_tandakan_sedia') }}</Btn>
            </template>
            <template v-if="viewRx.status === 'ready'">
              <Btn variant="ghost" size="sm" @click="setStatus(viewRx,'verifying');viewRx=null">← {{ t('btn_back') }}</Btn>
              <Btn variant="primary" style="flex:1" @click="confirmDispense(viewRx);viewRx=null">{{ t('rx_dispense') }}</Btn>
            </template>
            <Btn v-if="viewRx.status !== 'dispensed' && viewRx.status !== 'cancelled'" variant="ghost" size="sm" @click="openEdit(viewRx);viewRx=null">{{ t('btn_edit') }}</Btn>
            <a :href="`/pharmacy/prescriptions/${viewRx.id}/print`" target="_blank" class="rx-print-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              {{ t('rx_print') }}
            </a>
            <a :href="`/pharmacy/prescriptions/${viewRx.id}/label`" target="_blank" class="rx-print-btn rx-print-btn--label">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              {{ t('rx_label') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Create / Edit Rx Modal ──────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal modal--xl">

        <!-- Header -->
        <div class="modal__header">
          <div class="rx-modal-title-wrap">
            <span class="rx-modal-badge">Rx</span>
            <div>
              <h3 class="modal__title">{{ editingRx ? t('rx_modal_edit') : t('rx_modal_create') }}</h3>
              <p v-if="editingRx" class="rx-modal-sub">{{ editingRx.rx_number }}</p>
            </div>
          </div>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="submitRx" class="modal__body">

          <!-- ① Maklumat Asas -->
          <div class="rx-section">
            <div class="rx-section__title">{{ t('rx_sec_info') }}</div>
            <div class="rx-info-grid">
              <!-- Patient -->
              <div class="rx-field">
                <label class="rx-field__lbl">{{ t('rx_lbl_patient') }} <span class="req">*</span></label>
                <div style="position:relative">
                  <input
                    v-model="patientSearch"
                    class="input"
                    :placeholder="t('bill_ph_patient')"
                    @input="patientDropdown=true"
                    @focus="patientDropdown=true"
                    autocomplete="off"
                  />
                  <div v-if="patientDropdown && filteredPatients.length" class="patient-dropdown">
                    <button
                      v-for="p in filteredPatients" :key="p.id"
                      type="button"
                      class="patient-option"
                      @click="selectPatient(p)"
                    >
                      <div style="font:600 13px var(--font-sans)">{{ p.name }}</div>
                      <div style="font:500 11px var(--font-mono);color:var(--fg3)">{{ p.patient_id }} · {{ p.ic_number }}</div>
                      <div v-if="p.allergies" style="font:500 11px var(--font-sans);color:var(--brand-red)">⚠ {{ p.allergies }}</div>
                    </button>
                  </div>
                </div>
                <span v-if="rxForm.errors.patient_id" class="rx-field__err">{{ rxForm.errors.patient_id }}</span>
              </div>
              <!-- Doctor -->
              <div class="rx-field">
                <label class="rx-field__lbl">{{ t('rx_lbl_doctor') }} <span class="req">*</span></label>
                <input v-model="rxForm.prescribing_doctor" class="input" placeholder="Dr. Nama" />
                <span v-if="rxForm.errors.prescribing_doctor" class="rx-field__err">{{ rxForm.errors.prescribing_doctor }}</span>
              </div>
            </div>
          </div>

          <!-- ② Senarai Ubat -->
          <div class="rx-section">
            <div class="rx-section__title-row">
              <span class="rx-section__title">{{ t('rx_sec_drugs') }}</span>
              <span class="rx-drug-count">{{ rxForm.items.length }} ubat</span>
            </div>

            <div v-for="(item, i) in rxForm.items" :key="i" class="rx-drug-card">

              <!-- Card header -->
              <div class="rx-drug-card__head">
                <span class="rx-drug-card__num">Ubat {{ i + 1 }}</span>
                <button type="button" class="rx-remove-btn" @click="removeItem(i)" :disabled="rxForm.items.length===1" title="Buang ubat ini">×</button>
              </div>

              <!-- Row 1: Nama + Kegunaan -->
              <div class="rx-row rx-row--2">
                <div class="rx-field">
                  <label class="rx-field__lbl">{{ t('rx_lbl_drug') }} <span class="req">*</span></label>
                  <div style="position:relative">
                    <!-- Linked inventory badge + clear -->
                    <div v-if="item.inventory_item_id" class="drug-linked-bar">
                      <span class="drug-linked-name">{{ item.drug_name }}</span>
                      <span v-if="resolvedInvItem(item)" :class="['drug-stock-badge', resolvedInvItem(item).stock_quantity <= 0 ? 'drug-stock-badge--out' : resolvedInvItem(item).stock_quantity <= 10 ? 'drug-stock-badge--low' : 'drug-stock-badge--ok']">
                        Stok: {{ resolvedInvItem(item).stock_quantity }}
                      </span>
                      <button type="button" class="drug-clear-btn" @click="clearDrugSelection(i)" title="Tukar ubat">✕</button>
                    </div>
                    <!-- Search input (shown when no inventory item linked) -->
                    <template v-else>
                      <input
                        v-model="itemDrugSearch[i]"
                        class="input input--sm"
                        placeholder="Cari ubat inventori atau taip nama..."
                        autocomplete="off"
                        @input="itemDrugDropdown[i] = true; item.drug_name = itemDrugSearch[i]"
                        @focus="itemDrugDropdown[i] = true"
                        @blur="setTimeout(() => { itemDrugDropdown[i] = false }, 180)"
                      />
                      <div v-if="itemDrugDropdown[i] && filteredDrugsFor(i).length" class="drug-dropdown">
                        <button
                          v-for="inv in filteredDrugsFor(i)" :key="inv.id"
                          type="button"
                          class="drug-option"
                          :class="{ 'drug-option--out': inv.stock_quantity <= 0 }"
                          @mousedown.prevent="selectDrug(i, inv)"
                        >
                          <div class="drug-option__name">{{ inv.name }}</div>
                          <div class="drug-option__meta">
                            <span v-if="inv.generic_name" style="color:var(--fg3)">{{ inv.generic_name }}</span>
                            <span v-if="inv.form" style="color:var(--fg3)"> · {{ inv.form }}</span>
                            <span :class="['drug-option__stock', inv.stock_quantity <= 0 ? 'out' : inv.stock_quantity <= 10 ? 'low' : '']">
                              Stok: {{ inv.stock_quantity }}
                            </span>
                            <span v-if="inv.selling_price > 0" class="drug-option__price">RM {{ Number(inv.selling_price).toFixed(2) }}</span>
                          </div>
                        </button>
                      </div>
                    </template>
                  </div>
                  <span v-if="rxForm.errors[`items.${i}.drug_name`]" class="rx-field__err">Wajib diisi</span>
                </div>
                <div class="rx-field">
                  <label class="rx-field__lbl">Kegunaan</label>
                  <input v-model="item.kegunaan" class="input input--sm" placeholder="cth: Demam, Sakit Kepala" />
                </div>
              </div>

              <!-- Row 2: Bentuk · Dos · Kekerapan · Tempoh -->
              <div class="rx-row rx-row--4">
                <div class="rx-field">
                  <label class="rx-field__lbl">Bentuk Ubat</label>
                  <select v-model="item.drug_unit" class="input input--sm">
                    <option value="">— Pilih —</option>
                    <option v-for="u in DRUG_UNITS" :key="u.code" :value="u.code">{{ u.label }}</option>
                  </select>
                </div>
                <div class="rx-field">
                  <label class="rx-field__lbl">{{ t('rx_lbl_dose') }}</label>
                  <input v-model="item.dosage" class="input input--sm" placeholder="1" />
                </div>
                <div class="rx-field">
                  <label class="rx-field__lbl">{{ t('rx_lbl_freq') }}</label>
                  <input v-model="item.frequency" class="input input--sm" placeholder="OD / BD / TDS" list="freq-list" />
                  <datalist id="freq-list">
                    <option v-for="f in FREQUENCIES" :key="f" :value="f" />
                  </datalist>
                </div>
                <div class="rx-field">
                  <label class="rx-field__lbl">{{ t('rx_lbl_duration') }}</label>
                  <input v-model="item.duration" class="input input--sm" placeholder="7 hari" />
                </div>
              </div>

              <!-- Row 3: Qty · Arahan · PRN · Habiskan -->
              <div class="rx-row rx-row--footer">
                <div class="rx-field rx-field--qty">
                  <label class="rx-field__lbl">{{ t('rx_lbl_qty') }}</label>
                  <input v-model.number="item.quantity" type="number" min="1" class="input input--sm" style="text-align:center" />
                </div>
                <div class="rx-field rx-field--instr">
                  <label class="rx-field__lbl">{{ t('rx_lbl_instruction') }}</label>
                  <input v-model="item.instructions" class="input input--sm" placeholder="Selepas makan" list="instr-list" />
                  <datalist id="instr-list">
                    <option v-for="ins in INSTRUCTIONS" :key="ins" :value="ins" />
                  </datalist>
                </div>
                <label class="rx-toggle">
                  <input type="checkbox" v-model="item.is_prn" @change="onPrnChange(item)" />
                  <span class="rx-toggle__txt">
                    <strong>PRN</strong>
                    <small>Bila Perlu</small>
                  </span>
                </label>
                <label class="rx-toggle">
                  <input type="checkbox" v-model="item.complete_course" @change="onCompleteChange(item)" />
                  <span class="rx-toggle__txt">
                    <strong>Habis</strong>
                    <small>Habiskan</small>
                  </span>
                </label>
              </div>

              <!-- Row 4: Nota ubat (optional) -->
              <div class="rx-row rx-row--note">
                <div class="rx-field">
                  <label class="rx-field__lbl rx-field__lbl--note">Nota Ubat</label>
                  <textarea
                    v-model="item.item_note"
                    class="input input--sm rx-item-note"
                    rows="2"
                    placeholder="cth: Simpan dalam peti sejuk, elakkan cahaya matahari..."
                    style="resize:none"
                    @input="limitNoteLines(item)"
                  ></textarea>
                </div>
              </div>

            </div><!-- /v-for -->

            <span v-if="rxForm.errors.items" class="rx-field__err" style="display:block;margin-top:4px">{{ rxForm.errors.items }}</span>
            <button type="button" class="rx-add-btn" @click="addItem">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              {{ t('rx_add_drug') }}
            </button>
          </div>

          <!-- ③ Nota -->
          <div class="rx-section">
            <div class="rx-section__title">{{ t('rx_lbl_notes') }}</div>
            <textarea v-model="rxForm.notes" class="input" rows="2" :placeholder="t('rx_notes_ph')" style="resize:vertical"></textarea>
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="rxForm.processing">
              {{ editingRx ? t('rx_modal_edit_btn') : t('rx_modal_create_btn') }}
            </Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Dispense Confirmation ───────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="dispenseTarget" class="modal-backdrop" @click.self="dispenseTarget=null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title">{{ t('rx_dispense_confirm') }}</h3>
          <button class="modal__close" @click="dispenseTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <div style="font:500 13px var(--font-sans);color:var(--fg2);line-height:1.6;margin-bottom:14px">
            {{ t('rx_dispense_body', { patient: dispenseTarget.patient_name }) }}
            <strong class="mono">{{ dispenseTarget.rx_number }}</strong>
            <br/>{{ t('rx_dispense_items', { n: dispenseTarget.items.length }) }}
          </div>
          <div v-if="dispenseTarget.patient_allergies" class="allergy-alert" style="margin-bottom:14px">
            ⚠ {{ t('rx_patient_allergy', { list: dispenseTarget.patient_allergies }) }}
          </div>
          <!-- Drug summary -->
          <div style="background:var(--bg-soft);border-radius:8px;padding:10px 12px;display:flex;flex-direction:column;gap:6px">
            <div v-for="item in dispenseTarget.items" :key="item.id" style="font:500 12.5px var(--font-sans);color:var(--fg2)">
              • {{ item.drug_name }} × {{ item.quantity }}
              <span v-if="item.frequency" style="color:var(--fg3)"> · {{ item.frequency }}</span>
            </div>
          </div>
          <div class="modal__footer">
            <Btn variant="secondary" @click="dispenseTarget=null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" @click="doDispense">{{ t('rx_confirm_dispense') }}</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Delete Confirmation ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget=null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title" style="color:var(--brand-red)">{{ t('rx_del_title') }}</h3>
          <button class="modal__close" @click="deleteTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            {{ t('rx_del_body', { rx: deleteTarget.rx_number }) }}
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="deleteTarget=null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDelete">{{ t('rx_del_confirm') }}</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* print button */
.rx-print-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 12px; border-radius: 7px;
  border: 1.5px solid var(--border);
  background: #fff; color: var(--fg2);
  font: 600 12px var(--font-sans);
  text-decoration: none; cursor: pointer; transition: all .12s;
}
.rx-print-btn:hover { border-color: var(--brand-green); color: var(--brand-green); background: var(--brand-green-light); }
.rx-print-btn--sm { padding: 4px 8px; }
.rx-print-btn--label { border-color: #c7d2fe; color: #4338ca; }
.rx-print-btn--label:hover { border-color: #4338ca; color: #4338ca; background: #eef2ff; }

.flash-ok {
  background: var(--brand-green-light); border: 1px solid var(--brand-green);
  color: var(--brand-green-dark); padding: 10px 16px; border-radius: 8px;
  font: 600 13px var(--font-sans);
}

/* ── Modals & Drawer ── */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 16px;
}
.modal {
  background: #fff; border-radius: 14px; width: 520px;
  max-width: 100%; max-height: 90vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.modal--sm  { width: 420px; }
.modal--xl  { width: 860px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title  { flex:1; font:700 15px var(--font-sans); color:var(--fg1); margin:0; }
.modal__close  {
  width:28px; height:28px; border:0; background:var(--bg-muted);
  border-radius:6px; cursor:pointer; font-size:12px; color:var(--fg2);
  display:grid; place-items:center; flex-shrink:0;
}
.modal__body   { padding: 20px; }
.modal__footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }

/* ── Modal header badge ── */
.rx-modal-title-wrap { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.rx-modal-badge {
  width:34px; height:34px; border-radius:9px; flex-shrink:0;
  background:var(--brand-green); color:#fff;
  font:800 13px var(--font-mono); display:grid; place-items:center;
}
.rx-modal-sub { font:500 11px var(--font-mono); color:var(--fg3); margin:2px 0 0; }

/* ── Sections ── */
.rx-section {
  padding: 16px 0;
  border-bottom: 1px solid var(--bg-muted);
}
.rx-section:last-of-type { border-bottom: none; }
.rx-section__title {
  font:700 10.5px var(--font-sans); letter-spacing:.07em; text-transform:uppercase;
  color:var(--fg3); margin-bottom:12px;
}
.rx-section__title-row {
  display:flex; align-items:center; gap:8px; margin-bottom:12px;
}
.rx-drug-count {
  font:600 11px var(--font-mono); color:var(--brand-green-dark);
  background:var(--brand-green-light); padding:2px 8px; border-radius:999px;
}

/* ── Info grid (Patient + Doctor) ── */
.rx-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

/* ── Field ── */
.req { color: var(--brand-red); }
.rx-field { display:flex; flex-direction:column; gap:4px; }
.rx-field__lbl { font:600 10.5px var(--font-sans); letter-spacing:.04em; text-transform:uppercase; color:var(--fg3); }
.rx-field__lbl--note::after { content:' (pilihan)'; font:400 10px var(--font-sans); text-transform:none; letter-spacing:0; color:var(--fg3); }
.rx-field__err { font:500 11px var(--font-sans); color:var(--brand-red); }

/* ── Drug Card ── */
.rx-drug-card {
  border: 1.5px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 10px;
  background: #fff;
  transition: border-color .15s, box-shadow .15s;
}
.rx-drug-card:focus-within {
  border-color: var(--brand-green);
  box-shadow: 0 0 0 3px rgba(27,138,74,.1);
}
.rx-drug-card__head {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:12px;
}
.rx-drug-card__num {
  font:700 10.5px var(--font-sans); text-transform:uppercase; letter-spacing:.07em;
  color:var(--brand-green-dark); background:var(--brand-green-light);
  padding:3px 10px; border-radius:999px;
}

/* ── Rows inside card ── */
.rx-row { display:grid; gap:10px; margin-bottom:10px; }
.rx-row:last-child { margin-bottom:0; }
.rx-row--2      { grid-template-columns: 1fr 1fr; }
.rx-row--4      { grid-template-columns: 1.4fr 0.8fr 1.5fr 1fr; }
.rx-row--footer { grid-template-columns: 80px 1fr auto auto; align-items:end; }
.rx-row--note   { grid-template-columns: 1fr; }
.rx-item-note   { background:#FFFDF0; border-color:#E8D48A; }
.rx-item-note:focus { border-color:#B08000; box-shadow:0 0 0 3px rgba(176,128,0,.12); }

/* ── Qty/Instr in footer row ── */
.rx-field--qty   { min-width:0; }
.rx-field--instr { min-width:0; }

/* ── PRN / Habiskan toggles ── */
.rx-toggle {
  display:flex; align-items:center; gap:8px; cursor:pointer;
  padding:7px 12px; border:1.5px solid var(--border);
  border-radius:9px; background:var(--bg-soft);
  transition:border-color .12s, background .12s;
  white-space:nowrap;
}
.rx-toggle:has(input:checked) {
  border-color:var(--brand-green); background:var(--brand-green-light);
}
.rx-toggle input[type="checkbox"] { width:15px; height:15px; accent-color:var(--brand-green); flex-shrink:0; cursor:pointer; }
.rx-toggle__txt { display:flex; flex-direction:column; line-height:1.2; }
.rx-toggle__txt strong { font:700 12px var(--font-sans); color:var(--fg1); }
.rx-toggle__txt small  { font:400 10px var(--font-sans); color:var(--fg3); }

/* ── Remove button ── */
.rx-remove-btn {
  width:28px; height:28px; border:1px solid var(--border); border-radius:6px;
  background:#fff; color:var(--fg3); cursor:pointer; font-size:16px;
  display:grid; place-items:center; flex-shrink:0;
  transition:all .12s;
}
.rx-remove-btn:hover:not(:disabled) { background:#FEE2E2; color:#dc2626; border-color:#FECACA; }
.rx-remove-btn:disabled { opacity:.3; cursor:default; }

/* ── Add drug button ── */
.rx-add-btn {
  display:flex; align-items:center; justify-content:center; gap:7px;
  width:100%; margin-top:4px; padding:9px 16px;
  border:1.5px dashed var(--border); border-radius:10px;
  background:transparent; color:var(--brand-green-dark);
  font:600 13px var(--font-sans); cursor:pointer;
  transition:all .12s;
}
.rx-add-btn:hover { background:var(--brand-green-light); border-color:var(--brand-green); }

.input--sm { padding: 7px 10px; font-size: 12.5px; }

/* Patient dropdown */
.patient-dropdown {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0;
  background: #fff; border: 1px solid var(--border); border-radius: 10px;
  box-shadow: var(--shadow-md); z-index: 100; max-height: 260px; overflow-y: auto;
}
.patient-option {
  display: block; width: 100%; text-align: left; padding: 10px 14px;
  border: 0; background: transparent; cursor: pointer;
}
.patient-option:hover { background: var(--bg-soft); }
.patient-option + .patient-option { border-top: 1px solid var(--bg-muted); }

/* Drawer */
.drawer-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.35); display:flex; justify-content:flex-end; z-index:9999; }
.drawer          { width:400px; max-width:100%; background:#fff; height:100%; overflow-y:auto; box-shadow:-8px 0 32px rgba(15,23,42,.12); display:flex; flex-direction:column; }
.drawer__header  { display:flex; align-items:flex-start; gap:12px; padding:20px; border-bottom:1px solid var(--border); position:sticky; top:0; background:#fff; z-index:1; }
.drawer__name    { font:700 16px var(--font-sans); color:var(--fg1); margin:4px 0 0; }
.drawer__body    { padding:18px 20px; flex:1; }
.drow-section-title { font:700 10.5px var(--font-sans); letter-spacing:.06em; text-transform:uppercase; color:var(--fg3); margin-bottom:10px; }
.info-grid  { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.info-row   { display:flex; flex-direction:column; gap:2px; }
.info-label { font:600 10.5px var(--font-sans); letter-spacing:.04em; text-transform:uppercase; color:var(--fg3); }
.info-val   { font:500 13px var(--font-sans); color:var(--fg1); }
.info-val.mono { font-family:var(--font-mono); font-size:12px; }

/* Drug list in drawer */
.drug-list  { display:flex; flex-direction:column; gap:8px; }
.drug-card  {
  border:1px solid var(--border); border-radius:8px; padding:10px 12px;
  background: var(--bg-soft);
}
.drug-card__name   { font:700 13px var(--font-sans); color:var(--fg1); }
.drug-card__meta   { font:500 11.5px var(--font-sans); color:var(--fg3); margin-top:2px; }
.drug-card__footer { display:flex; justify-content:space-between; margin-top:6px; }
.drug-card__qty    { font:700 12px var(--font-mono); color:var(--brand-green-dark); background:var(--brand-green-light); padding:2px 8px; border-radius:999px; }
.drug-card__instr  { font:500 11.5px var(--font-sans); color:var(--fg2); font-style:italic; }
.drug-card__note   {
  display:flex; align-items:flex-start; gap:5px; margin-top:7px;
  padding:6px 8px; background:#FFF9E6; border:1px solid #F0C040;
  border-radius:6px; font:400 11.5px var(--font-sans); color:#7A5A00;
  line-height:1.5; white-space: pre-wrap; word-break: break-word;
}
.drug-card__note svg { flex-shrink:0; margin-top:1px; color:#B08000; }

/* ── Inventory drug search dropdown ── */
.drug-dropdown {
  position:absolute; top:calc(100% + 3px); left:0; right:0; z-index:200;
  background:#fff; border:1px solid var(--border); border-radius:10px;
  box-shadow:var(--shadow-md); max-height:220px; overflow-y:auto;
}
.drug-option {
  display:block; width:100%; text-align:left; padding:8px 12px;
  border:0; background:transparent; cursor:pointer;
}
.drug-option:hover { background:var(--bg-soft); }
.drug-option + .drug-option { border-top:1px solid var(--bg-muted); }
.drug-option--out { opacity:.55; }
.drug-option__name { font:600 12.5px var(--font-sans); color:var(--fg1); }
.drug-option__meta { display:flex; align-items:center; gap:6px; margin-top:2px; font:500 11px var(--font-sans); flex-wrap:wrap; }
.drug-option__stock { font:700 10.5px var(--font-mono); padding:1px 6px; border-radius:999px; background:var(--brand-green-light); color:var(--brand-green-dark); }
.drug-option__stock.low { background:#FEF3C7; color:#92400E; }
.drug-option__stock.out { background:#FEE2E2; color:#991B1B; }
.drug-option__price { font:600 11px var(--font-mono); color:var(--brand-green-dark); margin-left:auto; }

/* Linked drug bar (replaces input once inventory item selected) */
.drug-linked-bar {
  display:flex; align-items:center; gap:6px; min-height:34px;
  padding:5px 10px; border:1.5px solid var(--brand-green);
  border-radius:8px; background:var(--brand-green-light);
}
.drug-linked-name { flex:1; font:600 12.5px var(--font-sans); color:var(--brand-green-dark); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.drug-stock-badge { font:700 10.5px var(--font-mono); padding:1px 7px; border-radius:999px; white-space:nowrap; flex-shrink:0; }
.drug-stock-badge--ok  { background:var(--brand-green-light); color:var(--brand-green-dark); border:1px solid var(--brand-green); }
.drug-stock-badge--low { background:#FEF3C7; color:#92400E; border:1px solid #F59E0B; }
.drug-stock-badge--out { background:#FEE2E2; color:#991B1B; border:1px solid #FECACA; }
.drug-clear-btn {
  width:20px; height:20px; border:0; background:transparent; cursor:pointer;
  color:var(--brand-green-dark); font-size:12px; display:grid; place-items:center; flex-shrink:0;
  border-radius:4px; opacity:.7;
}
.drug-clear-btn:hover { opacity:1; background:rgba(0,0,0,.06); }

/* Allergy alert */
.allergy-alert {
  background:#FEE2E2; border:1px solid #FECACA; border-radius:8px;
  padding:8px 12px; font:600 12.5px var(--font-sans); color:#991B1B;
  margin-bottom:10px;
}

/* Pagination & datatable scroll styles kini global di app.css */

/* ── Tablet (≤ 900px) ── */
@media (max-width: 900px) {
  .modal--xl { width: min(860px, 96vw); }
  .rx-row--4 { grid-template-columns: 1fr 1fr; }
  .rx-row--footer { grid-template-columns: 70px 1fr auto auto; }
}

/* ── Mobile (≤ 640px) ── */
@media (max-width: 640px) {
  /* Modal → bottom sheet */
  .modal-backdrop { align-items: flex-end; padding: 0; }
  .modal, .modal--sm, .modal--xl {
    width: 100%;
    max-width: 100%;
    max-height: 92dvh;
    border-radius: 16px 16px 0 0;
  }

  /* Drawer → full width */
  .drawer { width: 100%; }

  /* Info grid: 1-col */
  .rx-info-grid { grid-template-columns: 1fr; }

  /* Drug card rows: stack */
  .rx-row--2      { grid-template-columns: 1fr; }
  .rx-row--4      { grid-template-columns: 1fr 1fr; }
  .rx-row--footer { grid-template-columns: 70px 1fr; gap: 8px; }

  /* Toggles go to new row on mobile */
  .rx-toggle { width: 100%; }
  .rx-row--footer .rx-toggle:first-of-type { grid-column: 1; }
  .rx-row--footer .rx-toggle:last-of-type  { grid-column: 2; }

  /* Queue/history table rows: stack */
  .table__head { display: none; }
  .table__row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--bg-muted);
  }

  /* AI box */
  .ai-box { flex-direction: column; gap: 8px; }
}
</style>
