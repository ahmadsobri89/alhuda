<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  patients: { type: Object, default: () => ({ data: [], links: [] }) },
  filters:  { type: Object, default: () => ({}) },
})

const page = usePage()
const flash = computed(() => page.props.flash?.success)

// ─── Search ────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
let searchTimeout = null

watch(search, (val) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('patients'), { search: val || undefined }, { preserveState: true, replace: true })
  }, 350)
})

// ─── Malaysian states ──────────────────────────────────────────────────────
const states = [
  'Johor','Kedah','Kelantan','Melaka','Negeri Sembilan',
  'Pahang','Perak','Perlis','Pulau Pinang','Sabah','Sarawak',
  'Selangor','Terengganu','Kuala Lumpur','Labuan','Putrajaya',
]

const bloodTypes = ['A+','A-','B+','B-','O+','O-','AB+','AB-','Unknown']

const COMMON_CONDITIONS = [
  'HTN','T2DM','CAD','Dyslipidemia','Asthma','COPD','CKD',
  'Hypothyroid','Hyperthyroid','Gout','Osteoarthritis','Eczema',
  'Depression','Anxiety','Pregnancy',
]

// ─── Add / Edit Modal ──────────────────────────────────────────────────────
const showModal   = ref(false)
const editingPatient = ref(null)
const conditionInput = ref('')

const emptyForm = () => ({
  name: '', ic_number: '', date_of_birth: '', gender: 'male',
  phone: '', email: '', address: '', postcode: '', city: '', state: '',
  blood_type: '', allergies: '', conditions: [],
  emergency_contact_name: '', emergency_contact_phone: '',
  status: 'active',
})

const patientForm = useForm(emptyForm())

function openCreate() {
  editingPatient.value = null
  Object.assign(patientForm, emptyForm())
  patientForm.clearErrors()
  showModal.value = true
}

function openEdit(p) {
  editingPatient.value = p
  patientForm.name                    = p.name
  patientForm.ic_number               = p.ic_number
  patientForm.date_of_birth           = p.date_of_birth
  patientForm.gender                  = p.gender
  patientForm.phone                   = p.phone ?? ''
  patientForm.email                   = p.email ?? ''
  patientForm.address                 = p.address ?? ''
  patientForm.postcode                = p.postcode ?? ''
  patientForm.city                    = p.city ?? ''
  patientForm.state                   = p.state ?? ''
  patientForm.blood_type              = p.blood_type ?? ''
  patientForm.allergies               = p.allergies ?? ''
  patientForm.conditions              = [...(p.conditions ?? [])]
  patientForm.emergency_contact_name  = p.emergency_contact_name ?? ''
  patientForm.emergency_contact_phone = p.emergency_contact_phone ?? ''
  patientForm.status                  = p.status
  patientForm.clearErrors()
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function submitPatient() {
  if (editingPatient.value) {
    patientForm.put(route('patients.update', editingPatient.value.id), { onSuccess: closeModal })
  } else {
    patientForm.post(route('patients.store'), { onSuccess: closeModal })
  }
}

// IC → DOB auto-fill
function onIcBlur() {
  const ic = patientForm.ic_number.replace(/\D/g, '')
  if (ic.length < 6) return
  const yy = parseInt(ic.substring(0, 2))
  const mm = ic.substring(2, 4)
  const dd = ic.substring(4, 6)
  const year = yy > new Date().getFullYear() % 100 ? 1900 + yy : 2000 + yy
  const dob = `${year}-${mm}-${dd}`
  if (!patientForm.date_of_birth) patientForm.date_of_birth = dob
  // gender from last digit
  if (ic.length >= 12) {
    const lastDigit = parseInt(ic[11])
    if (!patientForm.gender || patientForm.gender === 'male') {
      patientForm.gender = lastDigit % 2 === 1 ? 'male' : 'female'
    }
  }
}

// Condition tag management
function addCondition(cond) {
  const c = cond.trim()
  if (c && !patientForm.conditions.includes(c)) {
    patientForm.conditions.push(c)
  }
  conditionInput.value = ''
}

function removeCondition(i) {
  patientForm.conditions.splice(i, 1)
}

function onConditionKey(e) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addCondition(conditionInput.value)
  }
}

// ─── View Drawer ───────────────────────────────────────────────────────────
const viewPatient = ref(null)

// ─── Delete ────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)

function doDelete() {
  router.delete(route('patients.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
  })
}

// ─── Helpers ───────────────────────────────────────────────────────────────
function genderLabel(g) { return g === 'male' ? 'Lelaki' : 'Perempuan' }
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-ok">{{ flash }}</div>

    <!-- Toolbar -->
    <div class="row">
      <div style="position:relative;flex:1;max-width:360px">
        <input v-model="search" class="input" placeholder="Cari nama, IC, atau ID pesakit…" style="padding-left:36px" />
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--fg3)">
          <Icon name="search" :size="15" />
        </span>
      </div>
      <div class="spacer"></div>
      <span style="font:500 12px var(--font-sans);color:var(--fg3)">{{ patients.total }} pesakit</span>
      <Btn variant="primary" @click="openCreate"><Icon name="plus" :size="14" /> Daftar Pesakit</Btn>
    </div>

    <!-- Table -->
    <div class="card" style="overflow:hidden">
      <div class="table__head" style="grid-template-columns:130px 2fr 1.3fr 70px 1.5fr 100px 110px 110px">
        <div>ID</div><div>Pesakit</div><div>IC / Umur</div><div>Lawatan</div><div>Diagnosis</div><div>Alahan</div><div>Lawatan Akhir</div><div></div>
      </div>

      <div
        v-for="p in patients.data" :key="p.id"
        class="table__row"
        style="grid-template-columns:130px 2fr 1.3fr 70px 1.5fr 100px 110px 110px"
      >
        <div class="mono" style="font:600 11px var(--font-mono);color:var(--fg3)">{{ p.patient_id }}</div>

        <div class="row" style="gap:8px">
          <Avatar :name="p.name" size="sm" />
          <div>
            <div style="font:600 13px var(--font-sans)">{{ p.name }}</div>
            <div v-if="p.status==='inactive'" style="font:500 10.5px var(--font-sans);color:var(--fg3)">Tidak aktif</div>
          </div>
        </div>

        <div>
          <div class="mono" style="font:500 11px var(--font-mono);color:var(--fg3)">{{ p.ic_number }}</div>
          <div style="font:500 11.5px var(--font-sans);color:var(--fg2)">{{ p.age_gender }}</div>
        </div>

        <div class="mono" style="font:700 13px var(--font-mono);color:var(--fg1)">{{ p.visit_count }}</div>

        <div style="display:flex;gap:4px;flex-wrap:wrap">
          <Badge v-for="t in (p.conditions||[])" :key="t" tone="neutral">{{ t }}</Badge>
          <span v-if="!p.conditions?.length" style="font:500 11.5px var(--font-sans);color:var(--fg3)">—</span>
        </div>

        <div>
          <Badge v-if="p.allergies" tone="red">⚠ {{ p.allergies }}</Badge>
          <span v-else style="font:500 11.5px var(--font-sans);color:var(--fg3)">—</span>
        </div>

        <div style="font:500 11.5px var(--font-sans);color:var(--fg3)">{{ p.last_visit_at ?? '—' }}</div>

        <div class="row" style="gap:4px">
          <Btn variant="ghost" size="sm" @click="viewPatient = p">Lihat</Btn>
          <Btn variant="ghost" size="sm" @click="openEdit(p)">Edit</Btn>
          <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="deleteTarget = p">⊗</Btn>
        </div>
      </div>

      <div v-if="!patients.data?.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        Tiada rekod pesakit dijumpai.
      </div>

      <!-- Pagination -->
      <div v-if="patients.last_page > 1" class="pagination">
        <button
          v-for="link in patients.links" :key="link.label"
          :disabled="!link.url"
          :class="['page-btn', link.active ? 'active':'']"
          @click="link.url && router.get(link.url, {search: search||undefined}, {preserveState:true})"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>

  <!-- ── View Drawer ──────────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="viewPatient" class="drawer-backdrop" @click.self="viewPatient = null">
      <div class="drawer">
        <div class="drawer__header">
          <Avatar :name="viewPatient.name" size="lg" />
          <div style="flex:1;min-width:0">
            <h2 class="drawer__name">{{ viewPatient.name }}</h2>
            <div style="font:500 12px var(--font-mono);color:var(--fg3)">{{ viewPatient.patient_id }}</div>
          </div>
          <button class="modal__close" @click="viewPatient = null">✕</button>
        </div>

        <div class="drawer__body">
          <!-- Tags row -->
          <div class="row" style="flex-wrap:wrap;gap:6px;margin-bottom:14px">
            <Badge v-for="t in (viewPatient.conditions||[])" :key="t" tone="neutral">{{ t }}</Badge>
            <Badge v-if="viewPatient.allergies" tone="red">⚠ {{ viewPatient.allergies }}</Badge>
            <Badge v-if="viewPatient.blood_type" tone="blue">{{ viewPatient.blood_type }}</Badge>
            <Badge :tone="viewPatient.status==='active'?'green':'neutral'">{{ viewPatient.status }}</Badge>
          </div>

          <!-- Stats row -->
          <div class="drawer-stats">
            <div class="vital">
              <div class="vital__label">Lawatan</div>
              <div class="vital__val">{{ viewPatient.visit_count }}</div>
            </div>
            <div class="vital">
              <div class="vital__label">Umur / Jantina</div>
              <div class="vital__val">{{ viewPatient.age_gender }}</div>
            </div>
            <div class="vital" style="min-width:110px">
              <div class="vital__label">Kumpulan Darah</div>
              <div class="vital__val">{{ viewPatient.blood_type || '—' }}</div>
            </div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Personal info -->
          <div class="drow-section-title">Maklumat Peribadi</div>
          <div class="info-grid">
            <div class="info-row"><span class="info-label">No. IC</span><span class="info-val mono">{{ viewPatient.ic_number }}</span></div>
            <div class="info-row"><span class="info-label">Tarikh Lahir</span><span class="info-val">{{ viewPatient.date_of_birth }}</span></div>
            <div class="info-row"><span class="info-label">Jantina</span><span class="info-val">{{ genderLabel(viewPatient.gender) }}</span></div>
            <div class="info-row"><span class="info-label">Telefon</span><span class="info-val">{{ viewPatient.phone || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Emel</span><span class="info-val">{{ viewPatient.email || '—' }}</span></div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Address -->
          <div class="drow-section-title">Alamat</div>
          <div class="info-grid">
            <div class="info-row" style="grid-column:1/-1"><span class="info-label">Alamat</span><span class="info-val">{{ viewPatient.address || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Poskod</span><span class="info-val mono">{{ viewPatient.postcode || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Bandar</span><span class="info-val">{{ viewPatient.city || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Negeri</span><span class="info-val">{{ viewPatient.state || '—' }}</span></div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Emergency contact -->
          <div class="drow-section-title">Kenalan Kecemasan</div>
          <div class="info-grid">
            <div class="info-row"><span class="info-label">Nama</span><span class="info-val">{{ viewPatient.emergency_contact_name || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Telefon</span><span class="info-val">{{ viewPatient.emergency_contact_phone || '—' }}</span></div>
          </div>

          <div class="hr" style="margin:16px 0"></div>
          <div class="row" style="gap:8px">
            <Btn variant="secondary" style="flex:1" @click="viewPatient=null; openEdit(viewPatient)">Edit Rekod</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Add / Edit Patient Modal ─────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal modal--lg">
        <div class="modal__header">
          <h3 class="modal__title">{{ editingPatient ? 'Kemaskini Rekod Pesakit' : 'Daftar Pesakit Baru' }}</h3>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="submitPatient" class="modal__body">

          <!-- Section: Maklumat Peribadi -->
          <div class="modal-section-title">Maklumat Peribadi</div>
          <div class="form-grid-3">
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">Nama Penuh <span class="req">*</span></label>
              <input v-model="patientForm.name" class="input" placeholder="Nama penuh seperti dalam IC" />
              <span v-if="patientForm.errors.name" class="field__error">{{ patientForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">No. Kad Pengenalan <span class="req">*</span></label>
              <input v-model="patientForm.ic_number" class="input" placeholder="780229-08-5234" @blur="onIcBlur" />
              <span v-if="patientForm.errors.ic_number" class="field__error">{{ patientForm.errors.ic_number }}</span>
            </div>
            <div class="field">
              <label class="field__label">Tarikh Lahir <span class="req">*</span></label>
              <input v-model="patientForm.date_of_birth" type="date" class="input" />
              <span v-if="patientForm.errors.date_of_birth" class="field__error">{{ patientForm.errors.date_of_birth }}</span>
            </div>
            <div class="field">
              <label class="field__label">Jantina <span class="req">*</span></label>
              <select v-model="patientForm.gender" class="select">
                <option value="male">Lelaki</option>
                <option value="female">Perempuan</option>
              </select>
            </div>
          </div>

          <!-- Section: Hubungi -->
          <div class="modal-section-title" style="margin-top:18px">Hubungi &amp; Alamat</div>
          <div class="form-grid-3">
            <div class="field">
              <label class="field__label">No. Telefon</label>
              <input v-model="patientForm.phone" class="input" placeholder="012-3456789" />
            </div>
            <div class="field">
              <label class="field__label">Emel</label>
              <input v-model="patientForm.email" type="email" class="input" placeholder="pesakit@emel.com" />
              <span v-if="patientForm.errors.email" class="field__error">{{ patientForm.errors.email }}</span>
            </div>
            <div class="field">
              <label class="field__label">Poskod</label>
              <input v-model="patientForm.postcode" class="input" placeholder="43000" maxlength="10" />
            </div>
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">Alamat</label>
              <input v-model="patientForm.address" class="input" placeholder="No. rumah, jalan, taman…" />
            </div>
            <div class="field">
              <label class="field__label">Bandar</label>
              <input v-model="patientForm.city" class="input" placeholder="Kajang" />
            </div>
            <div class="field">
              <label class="field__label">Negeri</label>
              <select v-model="patientForm.state" class="select">
                <option value="">— Pilih negeri —</option>
                <option v-for="s in states" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
          </div>

          <!-- Section: Perubatan -->
          <div class="modal-section-title" style="margin-top:18px">Maklumat Perubatan</div>
          <div class="form-grid-3">
            <div class="field">
              <label class="field__label">Kumpulan Darah</label>
              <select v-model="patientForm.blood_type" class="select">
                <option value="">— Tidak diketahui —</option>
                <option v-for="bt in bloodTypes" :key="bt" :value="bt">{{ bt }}</option>
              </select>
            </div>
            <div class="field" style="grid-column:2/-1">
              <label class="field__label">Alahan</label>
              <input v-model="patientForm.allergies" class="input" placeholder="cth: Penicillin, Aspirin" />
            </div>
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">Diagnosis / Penyakit Kronik</label>
              <div class="tag-input-wrap">
                <span v-for="(c,i) in patientForm.conditions" :key="i" class="cond-tag">
                  {{ c }} <button type="button" @click="removeCondition(i)">×</button>
                </span>
                <input
                  v-model="conditionInput"
                  class="tag-input"
                  placeholder="Taip &amp; tekan Enter…"
                  @keydown="onConditionKey"
                  @blur="conditionInput && addCondition(conditionInput)"
                />
              </div>
              <div class="cond-suggestions">
                <button
                  v-for="c in COMMON_CONDITIONS" :key="c"
                  type="button"
                  :class="['cond-chip', patientForm.conditions.includes(c) ? 'selected':'']"
                  @click="patientForm.conditions.includes(c) ? removeCondition(patientForm.conditions.indexOf(c)) : addCondition(c)"
                >{{ c }}</button>
              </div>
            </div>
          </div>

          <!-- Section: Kecemasan -->
          <div class="modal-section-title" style="margin-top:18px">Kenalan Kecemasan</div>
          <div class="form-grid-3">
            <div class="field" style="grid-column:1/3">
              <label class="field__label">Nama Kenalan</label>
              <input v-model="patientForm.emergency_contact_name" class="input" placeholder="Nama ahli keluarga" />
            </div>
            <div class="field">
              <label class="field__label">No. Telefon</label>
              <input v-model="patientForm.emergency_contact_phone" class="input" placeholder="013-XXXXXXX" />
            </div>
          </div>

          <!-- Status (edit only) -->
          <div v-if="editingPatient" style="margin-top:18px">
            <div class="field" style="max-width:200px">
              <label class="field__label">Status Rekod</label>
              <select v-model="patientForm.status" class="select">
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
              </select>
            </div>
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">Batal</Btn>
            <Btn type="submit" variant="primary" :disabled="patientForm.processing">
              {{ editingPatient ? 'Kemaskini' : 'Daftar Pesakit' }}
            </Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Delete Confirmation ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title" style="color:var(--brand-red)">Padam Rekod Pesakit</h3>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            Anda pasti mahu memadam rekod <strong>{{ deleteTarget.name }}</strong>
            (<span class="mono" style="font-size:12px">{{ deleteTarget.patient_id }}</span>)?
            Tindakan ini tidak boleh dibatalkan.
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="deleteTarget = null">Batal</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDelete">Ya, Padam</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.flash-ok {
  background: var(--brand-green-light);
  border: 1px solid var(--brand-green);
  color: var(--brand-green-dark);
  padding: 10px 16px;
  border-radius: 8px;
  font: 600 13px var(--font-sans);
}

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 16px;
}
.modal {
  background: #fff; border-radius: 14px;
  width: 560px; max-width: 100%; max-height: 90vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.modal--lg { width: 720px; }
.modal--sm { width: 400px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title { flex: 1; font: 700 15px var(--font-sans); color: var(--fg1); margin: 0; }
.modal__close {
  width: 28px; height: 28px; border: 0; background: var(--bg-muted);
  border-radius: 6px; cursor: pointer; font-size: 12px; color: var(--fg2);
  display: grid; place-items: center; flex-shrink: 0;
}
.modal__body { padding: 20px; }
.modal__footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }

.modal-section-title {
  font: 700 11px var(--font-sans); letter-spacing: .06em; text-transform: uppercase;
  color: var(--fg3); margin-bottom: 10px;
}
.req { color: var(--brand-red); }

.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

.field { display: flex; flex-direction: column; gap: 5px; }
.field__label { font: 600 11px var(--font-sans); color: var(--fg2); }
.field__error { font: 500 11px var(--font-sans); color: var(--brand-red); }

/* Condition tag input */
.tag-input-wrap {
  min-height: 40px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
  padding: 6px 10px; border: 1px solid var(--border); border-radius: 8px;
  background: #fff; cursor: text;
}
.cond-tag {
  display: inline-flex; align-items: center; gap: 4px;
  background: var(--bg-muted); color: var(--fg2);
  font: 600 11px var(--font-sans); padding: 3px 8px; border-radius: 999px;
}
.cond-tag button {
  border: 0; background: none; cursor: pointer; color: var(--fg3);
  font-size: 13px; line-height: 1; padding: 0;
}
.tag-input {
  border: 0; outline: none; font: 400 13px var(--font-sans);
  min-width: 120px; flex: 1;
}
.cond-suggestions { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
.cond-chip {
  padding: 4px 10px; border: 1px solid var(--border); border-radius: 999px;
  background: #fff; color: var(--fg3); font: 500 11.5px var(--font-sans); cursor: pointer;
}
.cond-chip:hover { border-color: var(--brand-green); color: var(--brand-green-dark); }
.cond-chip.selected { background: var(--brand-green-light); border-color: var(--brand-green); color: var(--brand-green-dark); font-weight: 700; }

/* ── Drawer ── */
.drawer-backdrop {
  position: fixed; inset: 0;
  background: rgba(15,23,42,.35);
  display: flex; justify-content: flex-end;
  z-index: 9999;
}
.drawer {
  width: 420px; max-width: 100%;
  background: #fff; height: 100%; overflow-y: auto;
  box-shadow: -8px 0 32px rgba(15,23,42,.12);
  display: flex; flex-direction: column;
}
.drawer__header {
  display: flex; align-items: center; gap: 12px;
  padding: 20px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.drawer__name { font: 700 16px var(--font-sans); color: var(--fg1); margin: 0; }
.drawer__body { padding: 18px 20px; flex: 1; }
.drawer-stats { display: flex; gap: 10px; }

.drow-section-title {
  font: 700 10.5px var(--font-sans); letter-spacing: .06em;
  text-transform: uppercase; color: var(--fg3); margin-bottom: 10px;
}
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.info-row { display: flex; flex-direction: column; gap: 2px; }
.info-label { font: 600 10.5px var(--font-sans); letter-spacing: .04em; text-transform: uppercase; color: var(--fg3); }
.info-val { font: 500 13px var(--font-sans); color: var(--fg1); }
.info-val.mono { font-family: var(--font-mono); font-size: 12px; }

/* ── Pagination ── */
.pagination { display: flex; gap: 4px; padding: 12px 16px; border-top: 1px solid var(--border); justify-content: center; }
.page-btn {
  min-width: 32px; height: 32px; border: 1px solid var(--border); border-radius: 6px;
  background: #fff; color: var(--fg2); font: 500 12px var(--font-sans); cursor: pointer; padding: 0 8px;
}
.page-btn.active { background: var(--brand-green); border-color: var(--brand-green); color: #fff; font-weight: 700; }
.page-btn:disabled { opacity: .4; cursor: default; }
</style>
