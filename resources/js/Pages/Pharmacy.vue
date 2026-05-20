<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
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
})

const page  = usePage()
const flash = computed(() => page.props.flash?.success)
const tab   = ref('queue')

// ─── History search ────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
let searchTimer = null
watch(search, v => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('pharmacy'), { search: v || undefined }, { preserveState: true, replace: true })
  }, 350)
})

// ─── Status helpers ────────────────────────────────────────────────────────
const STATUS_TONE = { pending: 'orange', verifying: 'blue', ready: 'green', dispensed: 'neutral', cancelled: 'neutral' }
const STATUS_LABEL = { pending: 'Menunggu', verifying: 'Semakan', ready: 'Sedia', dispensed: 'Dispensed', cancelled: 'Dibatal' }

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
  return { drug_name: '', dosage: '', frequency: '', duration: '', quantity: 1, instructions: '' }
}

const rxForm = useForm({
  patient_id:         '',
  prescribing_doctor: 'Dr. Aiman Rashid',
  notes:              '',
  items:              [emptyItem()],
})

function openCreate() {
  editingRx.value = null
  selectedPatient.value = null
  patientSearch.value = ''
  rxForm.reset()
  rxForm.items = [emptyItem()]
  rxForm.prescribing_doctor = 'Dr. Aiman Rashid'
  rxForm.clearErrors()
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
    drug_name: i.drug_name, dosage: i.dosage ?? '', frequency: i.frequency ?? '',
    duration: i.duration ?? '', quantity: i.quantity, instructions: i.instructions ?? '',
  }))
  rxForm.clearErrors()
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

function addItem()     { rxForm.items.push(emptyItem()) }
function removeItem(i) { rxForm.items.splice(i, 1) }

// Common drugs for quick-fill
const COMMON_DRUGS = [
  'Metformin 500mg', 'Amlodipine 5mg', 'Atorvastatin 20mg', 'Losartan 50mg',
  'Aspirin 75mg', 'Paracetamol 500mg', 'Ibuprofen 400mg', 'Omeprazole 20mg',
  'Salbutamol Inhaler 100mcg', 'Azithromycin 500mg', 'Amoxicillin 500mg',
  'Cetirizine 10mg', 'Levothyroxine 50mcg', 'Montelukast 10mg',
]

const FREQUENCIES = ['OD - 1x sehari','BD - 2x sehari','TDS - 3x sehari','QID - 4x sehari','ON - malam','PRN - bila perlu']
const INSTRUCTIONS = ['Selepas makan','Sebelum makan','Waktu pagi','Sebelum tidur','Bila perlu','30 min sebelum sarapan']

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
          Semakan AI Ubat ·
          <span v-if="allergiesInQueue.length">
            Alahan dikesan: {{ allergiesInQueue.join(', ') }} — preskripsi telah disemak.
          </span>
          <span v-else>Tiada interaksi kritikal dikesan.</span>
        </div>
        <div class="ai-box__body">
          {{ queue.length }} preskripsi dalam antrian · {{ queue.filter(r=>r.status==='ready').length }} sedia untuk dispens.
        </div>
      </div>
    </div>

    <!-- Tabs + New Rx button -->
    <div class="row">
      <div class="tabs">
        <button :class="['tab', tab==='queue'   ? 'active':'']" @click="tab='queue'">Antrian Ubat ({{ queue.length }})</button>
        <button :class="['tab', tab==='history' ? 'active':'']" @click="tab='history'">Sejarah</button>
      </div>
      <div class="spacer"></div>
      <Btn variant="primary" @click="openCreate"><Icon name="plus" :size="14" /> Preskripsi Baru</Btn>
    </div>

    <!-- ── Queue Tab ────────────────────────────────────────────────────── -->
    <div v-if="tab==='queue'" class="card" style="overflow:hidden">
      <div class="card__header">
        <h3 class="card__title">Antrian Dispensing</h3>
        <p class="card__sub">{{ queue.length }} preskripsi menunggu</p>
      </div>
      <div class="table__head" style="grid-template-columns:130px 1.8fr 60px 1.2fr 110px 110px 190px">
        <div>No. Rx</div><div>Pesakit</div><div>Ubat</div><div>Doktor</div><div>Masa Tunggu</div><div>Status</div><div></div>
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
          <Btn variant="ghost" size="sm" @click="viewRx = rx">Lihat</Btn>
          <Btn v-if="rx.status==='pending'"    variant="ghost"   size="sm" @click="setStatus(rx,'verifying')">Semak</Btn>
          <Btn v-if="rx.status==='verifying'"  variant="ghost"   size="sm" @click="setStatus(rx,'ready')">Sedia</Btn>
          <Btn v-if="rx.status==='ready'"      variant="primary" size="sm" @click="confirmDispense(rx)">Dispens →</Btn>
          <Btn v-if="rx.status!=='dispensed'"  variant="ghost"   size="sm" style="color:var(--fg3)" @click="setStatus(rx,'cancelled')">Batal</Btn>
        </div>
      </div>
      <div v-if="!queue.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        Antrian kosong — tiada preskripsi menunggu.
      </div>
    </div>

    <!-- ── History Tab ──────────────────────────────────────────────────── -->
    <div v-if="tab==='history'">
      <div class="row">
        <div style="position:relative;flex:1;max-width:360px">
          <input v-model="search" class="input" placeholder="Cari No. Rx atau nama pesakit…" style="padding-left:36px" />
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--fg3)">
            <Icon name="search" :size="15" />
          </span>
        </div>
      </div>
      <div class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns:130px 1.8fr 60px 1.2fr 150px 100px 120px">
          <div>No. Rx</div><div>Pesakit</div><div>Ubat</div><div>Doktor</div><div>Masa</div><div>Status</div><div></div>
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
            <Btn variant="ghost" size="sm" @click="viewRx = rx">Lihat</Btn>
            <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="deleteTarget = rx">⊗</Btn>
          </div>
        </div>
        <div v-if="!history.data?.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
          Tiada rekod sejarah.
        </div>
        <!-- Pagination -->
        <div v-if="history.last_page > 1" class="pagination">
          <button
            v-for="link in history.links" :key="link.label"
            :disabled="!link.url"
            :class="['page-btn', link.active ? 'active':'']"
            @click="link.url && router.get(link.url, {search: search||undefined}, {preserveState:true})"
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
            <div class="info-row"><span class="info-label">Doktor</span><span class="info-val">{{ viewRx.prescribing_doctor }}</span></div>
            <div class="info-row"><span class="info-label">Masa</span><span class="info-val mono">{{ viewRx.created_at }}</span></div>
            <div v-if="viewRx.dispensed_by" class="info-row"><span class="info-label">Dispens oleh</span><span class="info-val">{{ viewRx.dispensed_by }}</span></div>
            <div v-if="viewRx.dispensed_at" class="info-row"><span class="info-label">Masa Dispens</span><span class="info-val mono">{{ viewRx.dispensed_at }}</span></div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Drug items -->
          <div class="drow-section-title">Senarai Ubat ({{ viewRx.items.length }} item)</div>
          <div class="drug-list">
            <div v-for="(item, i) in viewRx.items" :key="i" class="drug-card">
              <div class="drug-card__name">{{ item.drug_name }}</div>
              <div class="drug-card__meta">
                <span v-if="item.dosage">{{ item.dosage }}</span>
                <span v-if="item.frequency"> · {{ item.frequency }}</span>
                <span v-if="item.duration"> · {{ item.duration }}</span>
              </div>
              <div class="drug-card__footer">
                <span class="drug-card__qty">Qty: {{ item.quantity }}</span>
                <span v-if="item.instructions" class="drug-card__instr">{{ item.instructions }}</span>
              </div>
            </div>
          </div>

          <div v-if="viewRx.notes" style="margin-top:14px">
            <div class="drow-section-title">Nota</div>
            <p style="font:400 12.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0">{{ viewRx.notes }}</p>
          </div>

          <div class="hr" style="margin:16px 0"></div>

          <!-- Actions -->
          <div class="row" style="gap:8px;flex-wrap:wrap">
            <template v-if="viewRx.status === 'pending'">
              <Btn variant="secondary" style="flex:1" @click="setStatus(viewRx,'verifying');viewRx=null">Semak Preskripsi</Btn>
              <Btn variant="ghost" size="sm" @click="openEdit(viewRx);viewRx=null">Edit</Btn>
            </template>
            <template v-if="viewRx.status === 'verifying'">
              <Btn variant="secondary" style="flex:1" @click="setStatus(viewRx,'ready');viewRx=null">Tandakan Sedia</Btn>
            </template>
            <template v-if="viewRx.status === 'ready'">
              <Btn variant="primary" style="flex:1" @click="confirmDispense(viewRx);viewRx=null">Dispens →</Btn>
            </template>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Create / Edit Rx Modal ──────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal modal--xl">
        <div class="modal__header">
          <h3 class="modal__title">{{ editingRx ? 'Edit Preskripsi · ' + editingRx.rx_number : 'Preskripsi Baru' }}</h3>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <form @submit.prevent="submitRx" class="modal__body">

          <!-- Patient + Doctor -->
          <div class="modal-section-title">Maklumat Preskripsi</div>
          <div class="form-grid-2" style="margin-bottom:14px">
            <div class="field">
              <label class="field__label">Pesakit <span class="req">*</span></label>
              <div style="position:relative">
                <input
                  v-model="patientSearch"
                  class="input"
                  placeholder="Cari nama, IC, atau ID pesakit…"
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
              <span v-if="rxForm.errors.patient_id" class="field__error">{{ rxForm.errors.patient_id }}</span>
            </div>
            <div class="field">
              <label class="field__label">Nama Doktor <span class="req">*</span></label>
              <input v-model="rxForm.prescribing_doctor" class="input" placeholder="Dr. Nama" />
              <span v-if="rxForm.errors.prescribing_doctor" class="field__error">{{ rxForm.errors.prescribing_doctor }}</span>
            </div>
          </div>

          <!-- Drug items -->
          <div class="modal-section-title">Senarai Ubat</div>
          <div class="drug-items-header">
            <span style="flex:2">Nama Ubat</span>
            <span style="flex:1">Dos</span>
            <span style="flex:1.5">Kekerapan</span>
            <span style="flex:1">Tempoh</span>
            <span style="width:60px">Kuantiti</span>
            <span style="flex:1.2">Arahan</span>
            <span style="width:32px"></span>
          </div>
          <div v-for="(item, i) in rxForm.items" :key="i" class="drug-item-row">
            <div style="flex:2;position:relative">
              <input v-model="item.drug_name" class="input input--sm" placeholder="Nama ubat" list="drug-list" />
              <datalist id="drug-list">
                <option v-for="d in COMMON_DRUGS" :key="d" :value="d" />
              </datalist>
              <span v-if="rxForm.errors[`items.${i}.drug_name`]" class="field__error">Wajib diisi</span>
            </div>
            <input v-model="item.dosage"       class="input input--sm" style="flex:1"   placeholder="500mg" />
            <div style="flex:1.5">
              <input v-model="item.frequency" class="input input--sm" placeholder="OD / BD / TDS" list="freq-list" />
              <datalist id="freq-list">
                <option v-for="f in FREQUENCIES" :key="f" :value="f" />
              </datalist>
            </div>
            <input v-model="item.duration"     class="input input--sm" style="flex:1"   placeholder="7 hari" />
            <input v-model.number="item.quantity" type="number" min="1" class="input input--sm" style="width:60px;text-align:center" />
            <div style="flex:1.2">
              <input v-model="item.instructions" class="input input--sm" placeholder="Selepas makan" list="instr-list" />
              <datalist id="instr-list">
                <option v-for="ins in INSTRUCTIONS" :key="ins" :value="ins" />
              </datalist>
            </div>
            <button type="button" class="remove-btn" @click="removeItem(i)" :disabled="rxForm.items.length===1">×</button>
          </div>
          <span v-if="rxForm.errors.items" class="field__error">{{ rxForm.errors.items }}</span>

          <button type="button" class="add-item-btn" @click="addItem">+ Tambah Ubat</button>

          <!-- Notes -->
          <div class="field" style="margin-top:14px">
            <label class="field__label">Nota Tambahan</label>
            <textarea v-model="rxForm.notes" class="input" rows="2" placeholder="Nota untuk farmasis…" style="resize:vertical"></textarea>
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">Batal</Btn>
            <Btn type="submit" variant="primary" :disabled="rxForm.processing">
              {{ editingRx ? 'Kemaskini Preskripsi' : 'Cipta Preskripsi' }}
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
          <h3 class="modal__title">Sahkan Dispensing</h3>
          <button class="modal__close" @click="dispenseTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <div style="font:500 13px var(--font-sans);color:var(--fg2);line-height:1.6;margin-bottom:14px">
            Dispens preskripsi <strong class="mono">{{ dispenseTarget.rx_number }}</strong>
            kepada <strong>{{ dispenseTarget.patient_name }}</strong>?
            <br/>{{ dispenseTarget.items.length }} item ubat akan ditanda sebagai telah diserahkan.
          </div>
          <div v-if="dispenseTarget.patient_allergies" class="allergy-alert" style="margin-bottom:14px">
            ⚠ Alahan pesakit: <strong>{{ dispenseTarget.patient_allergies }}</strong>
          </div>
          <!-- Drug summary -->
          <div style="background:var(--bg-soft);border-radius:8px;padding:10px 12px;display:flex;flex-direction:column;gap:6px">
            <div v-for="item in dispenseTarget.items" :key="item.id" style="font:500 12.5px var(--font-sans);color:var(--fg2)">
              • {{ item.drug_name }} × {{ item.quantity }}
              <span v-if="item.frequency" style="color:var(--fg3)"> · {{ item.frequency }}</span>
            </div>
          </div>
          <div class="modal__footer">
            <Btn variant="secondary" @click="dispenseTarget=null">Batal</Btn>
            <Btn variant="primary" @click="doDispense">Sahkan Dispens</Btn>
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
          <h3 class="modal__title" style="color:var(--brand-red)">Padam Preskripsi</h3>
          <button class="modal__close" @click="deleteTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            Padam preskripsi <strong class="mono">{{ deleteTarget.rx_number }}</strong>? Tindakan ini tidak boleh dibatalkan.
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="deleteTarget=null">Batal</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDelete">Ya, Padam</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
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

.modal-section-title {
  font:700 11px var(--font-sans); letter-spacing:.06em; text-transform:uppercase;
  color:var(--fg3); margin-bottom:10px;
}
.req { color: var(--brand-red); }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.field { display:flex; flex-direction:column; gap:5px; }
.field__label { font:600 11px var(--font-sans); color:var(--fg2); }
.field__error { font:500 11px var(--font-sans); color:var(--brand-red); }

/* Drug item rows */
.drug-items-header {
  display: flex; gap: 8px; align-items: center;
  padding: 0 0 6px; margin-bottom: 4px;
  border-bottom: 1px solid var(--border);
  font: 600 10.5px var(--font-sans); letter-spacing:.06em;
  text-transform: uppercase; color: var(--fg3);
}
.drug-item-row {
  display: flex; gap: 8px; align-items: flex-start;
  padding: 6px 0; border-bottom: 1px solid var(--bg-muted);
}
.input--sm { padding: 7px 10px; font-size: 12.5px; }
.remove-btn {
  width:32px; height:34px; border:1px solid var(--border); border-radius:6px;
  background:#fff; color:var(--fg3); cursor:pointer; font-size:16px;
  display:grid; place-items:center; flex-shrink:0;
}
.remove-btn:hover:not(:disabled) { background:var(--bg-muted); color:var(--brand-red); }
.remove-btn:disabled { opacity:.3; cursor:default; }
.add-item-btn {
  margin-top: 10px; padding: 7px 14px; border: 1px dashed var(--border);
  border-radius: 8px; background: transparent; color: var(--brand-green-dark);
  font: 600 12.5px var(--font-sans); cursor: pointer; width: 100%;
}
.add-item-btn:hover { background: var(--brand-green-light); }

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

/* Allergy alert */
.allergy-alert {
  background:#FEE2E2; border:1px solid #FECACA; border-radius:8px;
  padding:8px 12px; font:600 12.5px var(--font-sans); color:#991B1B;
  margin-bottom:10px;
}

/* Pagination */
.pagination { display:flex; gap:4px; padding:12px 16px; border-top:1px solid var(--border); justify-content:center; }
.page-btn { min-width:32px; height:32px; border:1px solid var(--border); border-radius:6px; background:#fff; color:var(--fg2); font:500 12px var(--font-sans); cursor:pointer; padding:0 8px; }
.page-btn.active { background:var(--brand-green); border-color:var(--brand-green); color:#fff; font-weight:700; }
.page-btn:disabled { opacity:.4; cursor:default; }
</style>
