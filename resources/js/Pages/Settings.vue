<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  users:      { type: Array,  default: () => [] },
  policies:   { type: Array,  default: () => [] },
  auditLogs:  { type: Object, default: () => ({ data: [], links: [] }) },
})

const tab = ref('users')

// ─── Role helpers ──────────────────────────────────────────────────────────
const roleLabels = {
  doctor:       'Doktor',
  nurse:        'Jururawat',
  pharmacist:   'Farmasi',
  receptionist: 'Resepsionis',
  admin:        'Super Admin',
}

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

// ─── Flash message ─────────────────────────────────────────────────────────
const page = usePage()
const flash = computed(() => page.props.flash?.success)
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-success">{{ flash }}</div>

    <div class="tabs">
      <button :class="['tab', tab==='users'    ? 'active':'']" @click="tab='users'">Pengguna &amp; Peranan</button>
      <button :class="['tab', tab==='security' ? 'active':'']" @click="tab='security'">Keselamatan</button>
      <button :class="['tab', tab==='audit'    ? 'active':'']" @click="tab='audit'">Log Audit</button>
    </div>

    <!-- ── Users Tab ───────────────────────────────────────────────────── -->
    <div v-if="tab==='users'">
      <div class="card" style="overflow:hidden">
        <div class="card__header">
          <h3 class="card__title" style="flex:1">Pengguna Sistem</h3>
          <Btn variant="primary" size="sm" @click="openCreate">+ Tambah Pengguna</Btn>
        </div>
        <div class="table__head" style="grid-template-columns:2fr 1fr 1.6fr 90px 90px 100px">
          <div>Pengguna</div><div>Peranan</div><div>Emel</div><div>MFA</div><div>Status</div><div></div>
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
            <Btn variant="ghost" size="sm" @click="openEdit(u)">Edit</Btn>
            <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="confirmDelete(u)">Padam</Btn>
          </div>
        </div>
        <div v-if="!users.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
          Tiada pengguna dijumpai.
        </div>
      </div>
    </div>

    <!-- ── Security Tab ────────────────────────────────────────────────── -->
    <div v-if="tab==='security'" class="card">
      <div class="card__header">
        <h3 class="card__title" style="flex:1">Dasar Kata Laluan &amp; MFA</h3>
        <Btn variant="primary" size="sm" :disabled="policyForm.processing" @click="savePolicies">Simpan Perubahan</Btn>
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

    <!-- ── Audit Tab ───────────────────────────────────────────────────── -->
    <div v-if="tab==='audit'" class="card" style="overflow:hidden">
      <div class="card__header"><h3 class="card__title">Log Audit Sistem</h3></div>
      <div class="table__head" style="grid-template-columns:140px 160px 160px 1fr 130px 90px">
        <div>Masa</div><div>Pengguna</div><div>Tindakan</div><div>Sumber</div><div>IP</div><div>Hasil</div>
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
        <div><Badge :tone="r.ok?'green':'red'">{{ r.ok ? 'OK' : 'Gagal' }}</Badge></div>
      </div>
      <div v-if="!auditLogs.data?.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        Tiada rekod audit.
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
          <h3 class="modal__title">{{ editingUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h3>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <form @submit.prevent="submitUser" class="modal__body">
          <div class="form-grid">
            <div class="field">
              <label class="field__label">Nama Penuh</label>
              <input v-model="userForm.name" class="input" placeholder="Nama penuh" />
              <span v-if="userForm.errors.name" class="field__error">{{ userForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">Emel</label>
              <input v-model="userForm.email" type="email" class="input" placeholder="emel@alhuda.my" />
              <span v-if="userForm.errors.email" class="field__error">{{ userForm.errors.email }}</span>
            </div>
            <div class="field">
              <label class="field__label">Peranan</label>
              <select v-model="userForm.role" class="select">
                <option value="doctor">Doktor</option>
                <option value="nurse">Jururawat</option>
                <option value="pharmacist">Farmasi</option>
                <option value="receptionist">Resepsionis</option>
                <option value="admin">Super Admin</option>
              </select>
              <span v-if="userForm.errors.role" class="field__error">{{ userForm.errors.role }}</span>
            </div>
            <div class="field" v-if="userForm.role === 'doctor'">
              <label class="field__label">No. MMC</label>
              <input v-model="userForm.mmc_number" class="input" placeholder="MMC-XXXXX" />
            </div>
            <div class="field">
              <label class="field__label">Kata Laluan {{ editingUser ? '(kosongkan jika tidak tukar)' : '' }}</label>
              <input v-model="userForm.password" type="password" class="input" placeholder="Min. 8 aksara" />
              <span v-if="userForm.errors.password" class="field__error">{{ userForm.errors.password }}</span>
            </div>
            <div class="field">
              <label class="field__label">Status</label>
              <select v-model="userForm.status" class="select">
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
              </select>
            </div>
          </div>
          <div class="row" style="gap:10px;margin-top:8px">
            <button type="button" :class="['toggle', userForm.mfa_enabled ? 'on':'']" @click="userForm.mfa_enabled = !userForm.mfa_enabled"></button>
            <span style="font:500 13px var(--font-sans);color:var(--fg2)">Aktifkan MFA (TOTP)</span>
          </div>
          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">Batal</Btn>
            <Btn type="submit" variant="primary" :disabled="userForm.processing">
              {{ editingUser ? 'Kemaskini' : 'Tambah' }}
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
          <h3 class="modal__title" style="color:var(--brand-red)">Padam Pengguna</h3>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            Anda pasti mahu memadam <strong>{{ deleteTarget.name }}</strong>?
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
</style>
