<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { useLocale } from '@/composables/useLocale'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  users:            { type: Array,  default: () => [] },
  policies:         { type: Array,  default: () => [] },
  auditLogs:        { type: Object, default: () => ({ data: [], links: [] }) },
  clinic:           { type: Object, default: () => ({}) },
  lookupCategories: { type: Array,  default: () => [] },
})

const { t } = useLocale()
const tab = ref('users')

// ─── Role helpers ──────────────────────────────────────────────────────────
const roleLabels = computed(() => ({
  doctor:       t('set_role_doctor'),
  nurse:        t('set_role_nurse'),
  pharmacist:   t('set_role_pharmacist'),
  receptionist: t('set_role_receptionist'),
  admin:        t('set_role_admin'),
}))

function roleTone(role) {
  if (role === 'admin')  return 'red'
  if (role === 'doctor') return 'green'
  return 'blue'
}

// ─── User Modal ────────────────────────────────────────────────────────────
const showModal   = ref(false)
const editingUser = ref(null)

const userForm = useForm({
  name:        '',
  email:       '',
  role:        'receptionist',
  mmc_number:  '',
  mfa_enabled: false,
  status:      'active',
  password:    '',
})

function openCreate() {
  editingUser.value = null
  userForm.reset()
  showModal.value = true
}

function openEdit(u) {
  editingUser.value = u
  userForm.name        = u.name
  userForm.email       = u.email
  userForm.role        = u.role
  userForm.mmc_number  = u.mmc_number ?? ''
  userForm.mfa_enabled = u.mfa_enabled
  userForm.status      = u.status
  userForm.password    = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  userForm.clearErrors()
}

function submitUser() {
  if (editingUser.value) {
    userForm.put(route('settings.users.update', editingUser.value.id), {
      onSuccess: () => closeModal(),
    })
  } else {
    userForm.post(route('settings.users.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

// ─── Delete ────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)

function confirmDelete(u) { deleteTarget.value = u }

function doDelete() {
  router.delete(route('settings.users.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
  })
}

// ─── Security Policies ─────────────────────────────────────────────────────
const policyState = ref(props.policies.map(p => ({ ...p })))

watch(() => props.policies, (val) => { policyState.value = val.map(p => ({ ...p })) })

const policyForm = useForm({ policies: [] })

function savePolicies() {
  policyForm.policies = policyState.value.map(p => ({ id: p.id, enabled: p.enabled }))
  policyForm.put(route('settings.policies.update'), { preserveScroll: true })
}

// ─── Clinic Profile ────────────────────────────────────────────────────────
const clinicForm = useForm({
  name:       props.clinic.name       ?? '',
  tagline:    props.clinic.tagline    ?? '',
  reg_number: props.clinic.reg_number ?? '',
  address:    props.clinic.address    ?? '',
  postcode:   props.clinic.postcode   ?? '',
  city:       props.clinic.city       ?? '',
  state:      props.clinic.state      ?? '',
  phone:      props.clinic.phone      ?? '',
  fax:        props.clinic.fax        ?? '',
  email:      props.clinic.email      ?? '',
  website:    props.clinic.website    ?? '',
  logo:       null,
})

const logoPreview = ref(props.clinic.logo_url ?? null)

function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  clinicForm.logo = file
  logoPreview.value = URL.createObjectURL(file)
}

function saveClinic() {
  clinicForm.post(route('settings.clinic.update'), { preserveScroll: true })
}

// ─── Flash message ─────────────────────────────────────────────────────────
const page = usePage()
const flash = computed(() => page.props.flash?.success)

// ─── Lookup Parameters ─────────────────────────────────────────────────────
const groupLabels = computed(() => ({
  patient:     t('lkp_group_patient'),
  appointment: t('lkp_group_appointment'),
  pharmacy:    t('lkp_group_pharmacy'),
  inventory:   t('lkp_group_inventory'),
  billing:     t('lkp_group_billing'),
  referral:    t('lkp_group_referral'),
  user:        t('lkp_group_user'),
}))

const groupedCategories = computed(() => {
  const groups = {}
  for (const cat of props.lookupCategories) {
    if (!groups[cat.group]) groups[cat.group] = []
    groups[cat.group].push(cat)
  }
  return groups
})

const selectedCategoryId = ref(null)
const selectedCategory = computed(() =>
  props.lookupCategories.find(c => c.id === selectedCategoryId.value) ?? null
)

function selectCategory(cat) {
  selectedCategoryId.value = cat.id
  lkpValueForm.reset()
  lkpEditingValue.value = null
  lkpShowModal.value = false
}

// Lookup value modal
const lkpShowModal   = ref(false)
const lkpEditingValue = ref(null)
const lkpDeleteTarget = ref(null)

const lkpValueForm = useForm({
  code:      '',
  label_ms:  '',
  label_en:  '',
  sort_order: null,
})

function lkpOpenCreate() {
  lkpEditingValue.value = null
  lkpValueForm.reset()
  lkpShowModal.value = true
}

function lkpOpenEdit(v) {
  lkpEditingValue.value = v
  lkpValueForm.code      = v.code
  lkpValueForm.label_ms  = v.label_ms
  lkpValueForm.label_en  = v.label_en
  lkpValueForm.sort_order = v.sort_order
  lkpShowModal.value = true
}

function lkpCloseModal() {
  lkpShowModal.value = false
  lkpValueForm.clearErrors()
}

function lkpSubmitValue() {
  if (!selectedCategory.value) return
  if (lkpEditingValue.value) {
    lkpValueForm.put(
      route('lookup.values.update', { category: selectedCategory.value.id, value: lkpEditingValue.value.id }),
      { onSuccess: () => lkpCloseModal(), preserveScroll: true }
    )
  } else {
    lkpValueForm.post(
      route('lookup.values.store', { category: selectedCategory.value.id }),
      { onSuccess: () => lkpCloseModal(), preserveScroll: true }
    )
  }
}

function lkpToggle(v) {
  router.patch(
    route('lookup.values.toggle', { category: selectedCategory.value.id, value: v.id }),
    {}, { preserveScroll: true }
  )
}

function lkpConfirmDelete(v) { lkpDeleteTarget.value = v }

function lkpDoDelete() {
  router.delete(
    route('lookup.values.destroy', { category: selectedCategory.value.id, value: lkpDeleteTarget.value.id }),
    { onSuccess: () => { lkpDeleteTarget.value = null }, preserveScroll: true }
  )
}
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-success">{{ flash }}</div>

    <div class="tabs">
      <button :class="['tab', tab==='clinic'   ? 'active':'']" @click="tab='clinic'">{{ t('set_tab_clinic') }}</button>
      <button :class="['tab', tab==='users'    ? 'active':'']" @click="tab='users'">{{ t('set_tab_users') }}</button>
      <button :class="['tab', tab==='security' ? 'active':'']" @click="tab='security'">{{ t('set_tab_security') }}</button>
      <button :class="['tab', tab==='lookup'   ? 'active':'']" @click="tab='lookup'">{{ t('set_tab_lookup') }}</button>
      <button :class="['tab', tab==='audit'    ? 'active':'']" @click="tab='audit'">{{ t('set_tab_audit') }}</button>
    </div>

    <!-- ── Clinic Profile Tab ───────────────────────────────────────────── -->
    <div v-if="tab==='clinic'">
      <div class="card">
        <div class="card__header">
          <h3 class="card__title" style="flex:1">{{ t('clinic_title') }}</h3>
        </div>
        <div class="card__body">
          <p style="font:400 12px var(--font-sans);color:var(--fg3);margin-bottom:20px">{{ t('clinic_subtitle') }}</p>

          <!-- Logo section -->
          <div class="clinic-logo-section">
            <div class="clinic-logo-preview">
              <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="clinic-logo-img" />
              <div v-else class="clinic-logo-empty">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--border)"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            </div>
            <div>
              <div style="font:600 12px var(--font-sans);color:var(--fg1);margin-bottom:4px">{{ t('clinic_lbl_logo') }}</div>
              <div style="font:400 11px var(--font-sans);color:var(--fg3);margin-bottom:8px">{{ t('clinic_logo_hint') }}</div>
              <label class="logo-upload-btn">
                <input type="file" accept="image/png,image/jpeg,image/jpg" style="display:none" @change="onLogoChange" />
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                {{ logoPreview ? t('clinic_logo_change') : t('clinic_logo_upload') }}
              </label>
              <span v-if="clinicForm.errors.logo" class="field__error" style="display:block;margin-top:4px">{{ clinicForm.errors.logo }}</span>
            </div>
          </div>

          <div class="clinic-form-grid">
            <!-- Name + Tagline -->
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('clinic_lbl_name') }} *</label>
              <input v-model="clinicForm.name" class="input" :placeholder="t('clinic_lbl_name')" />
              <span v-if="clinicForm.errors.name" class="field__error">{{ clinicForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_tagline') }}</label>
              <input v-model="clinicForm.tagline" class="input" placeholder="Klinik Perubatan Berdaftar" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_reg') }}</label>
              <input v-model="clinicForm.reg_number" class="input" placeholder="KKM/PPMD/..." />
            </div>

            <!-- Address -->
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('clinic_lbl_address') }} *</label>
              <input v-model="clinicForm.address" class="input" placeholder="No. 1, Jalan Al-Huda, Taman Harmoni" />
              <span v-if="clinicForm.errors.address" class="field__error">{{ clinicForm.errors.address }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_postcode') }} *</label>
              <input v-model="clinicForm.postcode" class="input" placeholder="47500" maxlength="10" />
              <span v-if="clinicForm.errors.postcode" class="field__error">{{ clinicForm.errors.postcode }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_city') }} *</label>
              <input v-model="clinicForm.city" class="input" placeholder="Subang Jaya" />
              <span v-if="clinicForm.errors.city" class="field__error">{{ clinicForm.errors.city }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_state') }} *</label>
              <select v-model="clinicForm.state" class="select">
                <option value="Johor">Johor</option>
                <option value="Kedah">Kedah</option>
                <option value="Kelantan">Kelantan</option>
                <option value="Melaka">Melaka</option>
                <option value="Negeri Sembilan">Negeri Sembilan</option>
                <option value="Pahang">Pahang</option>
                <option value="Perak">Perak</option>
                <option value="Perlis">Perlis</option>
                <option value="Pulau Pinang">Pulau Pinang</option>
                <option value="Sabah">Sabah</option>
                <option value="Sarawak">Sarawak</option>
                <option value="Selangor">Selangor</option>
                <option value="Terengganu">Terengganu</option>
                <option value="W.P. Kuala Lumpur">W.P. Kuala Lumpur</option>
                <option value="W.P. Labuan">W.P. Labuan</option>
                <option value="W.P. Putrajaya">W.P. Putrajaya</option>
              </select>
            </div>

            <!-- Contact -->
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_phone') }} *</label>
              <input v-model="clinicForm.phone" class="input" placeholder="03-8888 0000" />
              <span v-if="clinicForm.errors.phone" class="field__error">{{ clinicForm.errors.phone }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_fax') }}</label>
              <input v-model="clinicForm.fax" class="input" placeholder="03-8888 0001" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_email') }}</label>
              <input v-model="clinicForm.email" type="email" class="input" placeholder="klinik@alhuda.my" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('clinic_lbl_website') }}</label>
              <input v-model="clinicForm.website" class="input" placeholder="www.alhuda.my" />
            </div>
          </div>

          <div style="margin-top:20px;display:flex;justify-content:flex-end">
            <Btn variant="primary" :disabled="clinicForm.processing" @click="saveClinic">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              {{ clinicForm.processing ? t('clinic_saving') : t('clinic_save') }}
            </Btn>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Users Tab ───────────────────────────────────────────────────── -->
    <div v-if="tab==='users'">
      <div class="card" style="overflow:hidden">
        <div class="card__header">
          <h3 class="card__title" style="flex:1">{{ t('set_users_title') }}</h3>
          <Btn variant="primary" size="sm" @click="openCreate">{{ t('set_new_user') }}</Btn>
        </div>
        <div class="table__head" style="grid-template-columns:2fr 1fr 1.6fr 90px 90px 100px">
          <div>{{ t('set_col_user') }}</div><div>{{ t('set_col_role') }}</div><div>{{ t('set_col_email') }}</div><div>{{ t('set_col_mfa') }}</div><div>{{ t('set_col_status') }}</div><div></div>
        </div>
        <div
          v-for="u in users" :key="u.id"
          class="table__row"
          style="grid-template-columns:2fr 1fr 1.6fr 90px 90px 100px"
        >
          <div class="row">
            <Avatar :name="u.name" />
            <div>
              <div style="font:600 13px var(--font-sans)">{{ u.name }}</div>
              <div v-if="u.mmc_number" style="font:500 11px var(--font-mono);color:var(--fg3)">{{ u.mmc_number }}</div>
            </div>
          </div>
          <div><Badge :tone="roleTone(u.role)">{{ roleLabels[u.role] ?? u.role }}</Badge></div>
          <div class="mono" style="font:500 12px var(--font-mono);color:var(--fg3)">{{ u.email }}</div>
          <div><Badge :tone="u.mfa_enabled?'green':'orange'">{{ u.mfa_enabled ? '✓ TOTP' : 'Off' }}</Badge></div>
          <div><Badge :tone="u.status==='active'?'green':'neutral'">{{ u.status }}</Badge></div>
          <div class="row" style="gap:4px">
            <Btn variant="ghost" size="sm" @click="openEdit(u)">{{ t('btn_edit') }}</Btn>
            <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="confirmDelete(u)">{{ t('btn_delete') }}</Btn>
          </div>
        </div>
        <div v-if="!users.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
          {{ t('set_no_users') }}
        </div>
      </div>
    </div>

    <!-- ── Security Tab ────────────────────────────────────────────────── -->
    <div v-if="tab==='security'" class="card">
      <div class="card__header">
        <h3 class="card__title" style="flex:1">{{ t('set_policy_title') }}</h3>
        <Btn variant="primary" size="sm" :disabled="policyForm.processing" @click="savePolicies">{{ t('set_save_policy') }}</Btn>
      </div>
      <div class="card__body" style="display:flex;flex-direction:column;gap:14px">
        <div v-for="p in policyState" :key="p.id" class="row" style="gap:12px">
          <button
            :class="['toggle', p.enabled ? 'on' : '']"
            @click="p.enabled = !p.enabled"
            :aria-label="p.label"
          ></button>
          <div style="font:500 13px var(--font-sans);color:var(--fg2)">{{ p.label }}</div>
        </div>
      </div>
    </div>

    <!-- ── Lookup Parameters Tab ─────────────────────────────────────── -->
    <div v-if="tab==='lookup'" class="lookup-layout">
      <!-- Left: Category list -->
      <aside class="lookup-sidebar">
        <div class="lookup-sidebar__header">{{ t('lkp_title') }}</div>
        <template v-for="(cats, group) in groupedCategories" :key="group">
          <div class="lookup-group-label">{{ groupLabels[group] ?? group }}</div>
          <button
            v-for="cat in cats" :key="cat.id"
            :class="['lookup-cat-item', selectedCategoryId === cat.id ? 'active' : '']"
            @click="selectCategory(cat)"
          >
            <span class="lookup-cat-name">{{ cat.name_ms }}</span>
            <span class="lookup-cat-count">{{ cat.values.length }}</span>
          </button>
        </template>
      </aside>

      <!-- Right: Values panel -->
      <div class="lookup-panel">
        <!-- Empty state -->
        <div v-if="!selectedCategory" class="lookup-empty">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--border)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          <p>{{ t('lkp_select_category') }}</p>
        </div>

        <!-- Values table -->
        <div v-else class="card" style="overflow:hidden">
          <div class="card__header">
            <div style="flex:1">
              <h3 class="card__title">{{ selectedCategory.name_ms }}</h3>
              <p v-if="selectedCategory.description_ms" style="font:400 11.5px var(--font-sans);color:var(--fg3);margin:2px 0 0">{{ selectedCategory.description_ms }}</p>
            </div>
            <Btn variant="primary" size="sm" @click="lkpOpenCreate">{{ t('lkp_add_value') }}</Btn>
          </div>

          <div class="table__head" style="grid-template-columns:130px 1fr 1fr 80px 110px">
            <div>{{ t('lkp_col_code') }}</div>
            <div>{{ t('lkp_col_label_ms') }}</div>
            <div>{{ t('lkp_col_label_en') }}</div>
            <div>{{ t('lkp_col_status') }}</div>
            <div>{{ t('lkp_col_actions') }}</div>
          </div>

          <div
            v-for="v in selectedCategory.values" :key="v.id"
            class="table__row"
            style="grid-template-columns:130px 1fr 1fr 80px 110px"
            :style="{ opacity: v.is_active ? 1 : 0.5 }"
          >
            <div style="display:flex;align-items:center;gap:6px">
              <span class="mono" style="font:600 12px var(--font-mono);color:var(--fg1)">{{ v.code }}</span>
              <Badge v-if="v.is_system" tone="neutral" style="font-size:9px;padding:1px 5px">{{ t('lkp_system_badge') }}</Badge>
            </div>
            <div style="font:500 13px var(--font-sans)">{{ v.label_ms }}</div>
            <div style="font:400 12.5px var(--font-sans);color:var(--fg3)">{{ v.label_en }}</div>
            <div>
              <Badge :tone="v.is_active ? 'green' : 'neutral'">
                {{ v.is_active ? t('lkp_active') : t('lkp_inactive') }}
              </Badge>
            </div>
            <div class="row" style="gap:4px">
              <Btn variant="ghost" size="sm" @click="lkpOpenEdit(v)">{{ t('btn_edit') }}</Btn>
              <button
                class="lookup-toggle-btn"
                :title="v.is_active ? t('lkp_inactive') : t('lkp_active')"
                @click="lkpToggle(v)"
              >
                <svg v-if="v.is_active" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
              <button
                v-if="!v.is_system"
                class="lookup-delete-btn"
                @click="lkpConfirmDelete(v)"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </div>
          </div>

          <div v-if="!selectedCategory.values.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
            {{ t('lkp_no_values') }}
          </div>
        </div>
      </div>
    </div>

    <!-- ── Audit Tab ───────────────────────────────────────────────────── -->
    <div v-if="tab==='audit'" class="card" style="overflow:hidden">
      <div class="card__header"><h3 class="card__title">{{ t('set_audit_title') }}</h3></div>
      <div class="table__head" style="grid-template-columns:140px 160px 160px 1fr 130px 90px">
        <div>{{ t('set_col_time') }}</div><div>{{ t('set_col_user') }}</div><div>{{ t('set_col_action') }}</div><div>{{ t('set_col_resource') }}</div><div>{{ t('set_col_ip') }}</div><div>{{ t('set_col_result') }}</div>
      </div>
      <div
        v-for="r in auditLogs.data" :key="r.id"
        class="table__row"
        style="grid-template-columns:140px 160px 160px 1fr 130px 90px"
      >
        <div class="mono" style="font:500 11.5px var(--font-mono);color:var(--fg3)">{{ r.ts }}</div>
        <div style="font:600 12.5px var(--font-sans)">{{ r.user }}</div>
        <div class="mono" :style="{ font:'600 11.5px var(--font-mono)', color: r.ok?'var(--brand-green-dark)':'var(--brand-red)' }">{{ r.act }}</div>
        <div style="font:400 12px var(--font-sans);color:var(--fg2)">{{ r.res }}</div>
        <div class="mono" style="font:500 11.5px var(--font-mono);color:var(--fg3)">{{ r.ip }}</div>
        <div><Badge :tone="r.ok?'green':'red'">{{ r.ok ? 'OK' : t('set_result_fail') }}</Badge></div>
      </div>
      <div v-if="!auditLogs.data?.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        {{ t('set_no_audit') }}
      </div>
      <!-- Pagination -->
      <div v-if="auditLogs.last_page > 1" class="audit-pagination">
        <button
          v-for="link in auditLogs.links" :key="link.label"
          :disabled="!link.url"
          :class="['page-btn', link.active ? 'active':'']"
          @click="link.url && router.get(link.url, {}, { preserveState:true })"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>

  <!-- ── Add / Edit User Modal ──────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <div class="modal__header">
          <h3 class="modal__title">{{ editingUser ? t('set_modal_edit') : t('set_modal_create') }}</h3>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <form @submit.prevent="submitUser" class="modal__body">
          <div class="form-grid">
            <div class="field">
              <label class="field__label">{{ t('set_lbl_name') }}</label>
              <input v-model="userForm.name" class="input" :placeholder="t('set_ph_name')" />
              <span v-if="userForm.errors.name" class="field__error">{{ userForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('set_lbl_email') }}</label>
              <input v-model="userForm.email" type="email" class="input" placeholder="emel@alhuda.my" />
              <span v-if="userForm.errors.email" class="field__error">{{ userForm.errors.email }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('set_lbl_role') }}</label>
              <select v-model="userForm.role" class="select">
                <option value="doctor">{{ t('set_role_doctor') }}</option>
                <option value="nurse">{{ t('set_role_nurse') }}</option>
                <option value="pharmacist">{{ t('set_role_pharmacist') }}</option>
                <option value="receptionist">{{ t('set_role_receptionist') }}</option>
                <option value="admin">{{ t('set_role_admin') }}</option>
              </select>
              <span v-if="userForm.errors.role" class="field__error">{{ userForm.errors.role }}</span>
            </div>
            <div class="field" v-if="userForm.role === 'doctor'">
              <label class="field__label">{{ t('set_lbl_mmc') }}</label>
              <input v-model="userForm.mmc_number" class="input" placeholder="MMC-XXXXX" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('set_lbl_password') }} {{ editingUser ? t('set_pw_change_note') : '' }}</label>
              <input v-model="userForm.password" type="password" class="input" :placeholder="t('set_ph_password')" />
              <span v-if="userForm.errors.password" class="field__error">{{ userForm.errors.password }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('set_lbl_status') }}</label>
              <select v-model="userForm.status" class="select">
                <option value="active">{{ t('status_active') }}</option>
                <option value="inactive">{{ t('status_inactive') }}</option>
              </select>
            </div>
          </div>
          <div class="row" style="gap:10px;margin-top:8px">
            <button type="button" :class="['toggle', userForm.mfa_enabled ? 'on':'']" @click="userForm.mfa_enabled = !userForm.mfa_enabled"></button>
            <span style="font:500 13px var(--font-sans);color:var(--fg2)">{{ t('set_mfa_label') }}</span>
          </div>
          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="userForm.processing">
              {{ editingUser ? t('btn_update') : t('btn_add') }}
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
          <h3 class="modal__title" style="color:var(--brand-red)">{{ t('set_del_title') }}</h3>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            {{ t('set_del_body', { name: deleteTarget.name }) }}
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="deleteTarget = null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDelete">{{ t('set_del_confirm') }}</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Lookup Value Add/Edit Modal ───────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="lkpShowModal" class="modal-backdrop" @click.self="lkpCloseModal">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title">{{ lkpEditingValue ? t('lkp_edit_value') : t('lkp_new_value') }}</h3>
          <button class="modal__close" @click="lkpCloseModal">✕</button>
        </div>
        <form @submit.prevent="lkpSubmitValue" class="modal__body">
          <div style="display:flex;flex-direction:column;gap:14px">
            <div class="field">
              <label class="field__label">{{ t('lkp_lbl_code') }} *</label>
              <input v-model="lkpValueForm.code" class="input" :placeholder="t('lkp_ph_code')" :disabled="!!lkpEditingValue?.is_system" />
              <span v-if="lkpValueForm.errors.code" class="field__error">{{ lkpValueForm.errors.code }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('lkp_lbl_label_ms') }} *</label>
              <input v-model="lkpValueForm.label_ms" class="input" :placeholder="t('lkp_ph_label_ms')" />
              <span v-if="lkpValueForm.errors.label_ms" class="field__error">{{ lkpValueForm.errors.label_ms }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('lkp_lbl_label_en') }} *</label>
              <input v-model="lkpValueForm.label_en" class="input" :placeholder="t('lkp_ph_label_en')" />
              <span v-if="lkpValueForm.errors.label_en" class="field__error">{{ lkpValueForm.errors.label_en }}</span>
            </div>
          </div>
          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="lkpCloseModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="lkpValueForm.processing">
              {{ lkpEditingValue ? t('btn_update') : t('btn_add') }}
            </Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Lookup Value Delete Confirmation ──────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="lkpDeleteTarget" class="modal-backdrop" @click.self="lkpDeleteTarget = null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title" style="color:var(--brand-red)">{{ t('lkp_del_confirm') }}</h3>
          <button class="modal__close" @click="lkpDeleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            {{ t('lkp_del_body', { label: lkpDeleteTarget.label_ms }) }}
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="lkpDeleteTarget = null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="lkpDoDelete">{{ t('lkp_del_yes') }}</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.flash-success {
  background: var(--brand-green-light);
  border: 1px solid var(--brand-green);
  color: var(--brand-green-dark);
  padding: 10px 16px;
  border-radius: 8px;
  font: 600 13px var(--font-sans);
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal {
  background: #fff;
  border-radius: 14px;
  width: 520px;
  max-width: calc(100vw - 32px);
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}

.modal--sm { width: 380px; }

.modal__header {
  display: flex;
  align-items: center;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--border);
}

.modal__title {
  flex: 1;
  font: 700 15px var(--font-sans);
  color: var(--fg1);
  margin: 0;
}

.modal__close {
  width: 28px;
  height: 28px;
  border: 0;
  background: var(--bg-muted);
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  color: var(--fg2);
  display: grid;
  place-items: center;
}

.modal__body { padding: 20px; }

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-grid .field:first-child,
.form-grid .field:nth-child(5) {
  grid-column: 1 / -1;
}

.field__error {
  font: 500 11px var(--font-sans);
  color: var(--brand-red);
  margin-top: 2px;
}

.audit-pagination {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
  border-top: 1px solid var(--border);
  justify-content: center;
}

.page-btn {
  min-width: 32px;
  height: 32px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: #fff;
  color: var(--fg2);
  font: 500 12px var(--font-sans);
  cursor: pointer;
  padding: 0 8px;
}

.page-btn.active {
  background: var(--brand-green);
  border-color: var(--brand-green);
  color: #fff;
  font-weight: 700;
}

.page-btn:disabled { opacity: .4; cursor: default; }

/* ── Clinic Profile ── */
.clinic-logo-section {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
  border-radius: 10px;
  margin-bottom: 20px;
}
.clinic-logo-preview {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  border: 1px solid var(--border);
  overflow: hidden;
  flex-shrink: 0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.clinic-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.clinic-logo-empty {
  display: flex;
  align-items: center;
  justify-content: center;
}
.logo-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 7px;
  font: 600 12px var(--font-sans);
  color: var(--fg2);
  cursor: pointer;
}
.logo-upload-btn:hover {
  border-color: var(--brand-green);
  color: var(--brand-green-dark);
}
.clinic-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

/* ── Lookup Parameters ── */
.lookup-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 16px;
  align-items: start;
}

.lookup-sidebar {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}

.lookup-sidebar__header {
  font: 700 12px var(--font-sans);
  color: var(--fg3);
  text-transform: uppercase;
  letter-spacing: .04em;
  padding: 12px 14px 8px;
  border-bottom: 1px solid var(--border);
}

.lookup-group-label {
  font: 700 10.5px var(--font-sans);
  color: var(--fg3);
  text-transform: uppercase;
  letter-spacing: .06em;
  padding: 10px 14px 4px;
}

.lookup-cat-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 7px 14px;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  font: 500 13px var(--font-sans);
  color: var(--fg2);
  transition: background .12s;
}

.lookup-cat-item:hover { background: var(--bg-soft); }

.lookup-cat-item.active {
  background: var(--brand-green-light);
  color: var(--brand-green-dark);
  font-weight: 600;
}

.lookup-cat-count {
  font: 500 11px var(--font-mono);
  color: var(--fg3);
  background: var(--bg-muted);
  border-radius: 10px;
  padding: 1px 7px;
  min-width: 20px;
  text-align: center;
}

.lookup-cat-item.active .lookup-cat-count {
  background: var(--brand-green);
  color: #fff;
}

.lookup-panel {
  min-height: 300px;
}

.lookup-empty {
  height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--fg3);
  font: 500 13px var(--font-sans);
}

.lookup-toggle-btn,
.lookup-delete-btn {
  width: 26px;
  height: 26px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: grid;
  place-items: center;
  color: var(--fg2);
  transition: border-color .12s, color .12s;
}

.lookup-toggle-btn:hover {
  border-color: var(--brand-green);
  color: var(--brand-green-dark);
}

.lookup-delete-btn:hover {
  border-color: var(--brand-red);
  color: var(--brand-red);
}
</style>
