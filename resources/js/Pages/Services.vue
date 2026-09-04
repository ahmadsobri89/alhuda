<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'
import { useLocale } from '@/composables/useLocale'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  services: { type: Object, default: () => ({ data: [], links: [] }) },
  kpis:     { type: Object, default: () => ({}) },
  filters:  { type: Object, default: () => ({}) },
})

const page  = usePage()
const flash = computed(() => page.props.flash?.success)
const { t } = useLocale()

// ─── Search & pagination ────────────────────────────────────────────────────
const search  = ref(props.filters.search ?? '')
const perPage = ref(props.filters.per_page ?? 20)
const PER_PAGE_OPTIONS = [20, 50, 100]
let searchTimer = null

function queryParams(extra = {}) {
  return {
    search: search.value || undefined,
    per_page: perPage.value !== 20 ? perPage.value : undefined,
    ...extra,
  }
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('services'), queryParams(), { preserveState: true, replace: true })
  }, 350)
})

function setPerPage() {
  router.get(route('services'), queryParams(), { preserveState: true, replace: true })
}

function goToPage(url) {
  if (url) router.get(url, queryParams(), { preserveState: true, preserveScroll: true })
}

// ─── Add / Edit Modal ───────────────────────────────────────────────────────
const showModal    = ref(false)
const editingService = ref(null)

const serviceForm = useForm({
  code: '', name: '', category: '', price: 0, notes: '', status: 'active',
})

function openCreate() {
  editingService.value = null
  serviceForm.reset()
  serviceForm.status = 'active'
  serviceForm.clearErrors()
  showModal.value = true
}

function openEdit(service) {
  editingService.value  = service
  serviceForm.code      = service.code ?? ''
  serviceForm.name      = service.name
  serviceForm.category  = service.category ?? ''
  serviceForm.price     = service.price
  serviceForm.notes     = service.notes ?? ''
  serviceForm.status    = service.status
  serviceForm.clearErrors()
  showModal.value = true
}

function closeModal() { showModal.value = false }

function submitService() {
  if (editingService.value) {
    serviceForm.put(route('services.update', editingService.value.id), { onSuccess: closeModal })
  } else {
    serviceForm.post(route('services.store'), { onSuccess: closeModal })
  }
}

// ─── Deactivate confirmation ────────────────────────────────────────────────
const discTarget = ref(null)
function doDiscontinue() {
  router.delete(route('services.destroy', discTarget.value.id), {
    onSuccess: () => { discTarget.value = null },
  })
}
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-ok">{{ flash }}</div>

    <!-- KPI -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="kpi__label">{{ t('svc_kpi_total') }}</div>
        <div class="kpi__value">{{ kpis.total_services }}</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="row svc-toolbar">
      <div class="svc-toolbar__search" style="position:relative;flex:1;max-width:340px">
        <input v-model="search" class="input" :placeholder="t('svc_search')" style="padding-left:36px" />
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--fg3)">
          <Icon name="search" :size="15" />
        </span>
      </div>
      <div class="spacer"></div>
      <span style="font:500 12px var(--font-sans);color:var(--fg3)">{{ services.total }} {{ t('svc_kpi_total').toLowerCase() }}</span>
      <Btn variant="primary" @click="openCreate"><Icon name="plus" :size="14" /> {{ t('svc_new') }}</Btn>
    </div>

    <!-- Table -->
    <div class="card table-card">
      <div class="table-scroll">
      <div class="table__head" style="grid-template-columns:120px 2fr 1fr 130px 100px 130px">
        <div>{{ t('svc_col_code') }}</div><div>{{ t('svc_col_name') }}</div><div>{{ t('svc_col_category') }}</div><div>{{ t('svc_col_price') }}</div><div>{{ t('svc_col_status') }}</div><div></div>
      </div>

      <div
        v-for="service in services.data" :key="service.id"
        class="table__row"
        style="grid-template-columns:120px 2fr 1fr 130px 100px 130px"
        :class="{ 'row--dim': service.status === 'discontinued' }"
      >
        <div class="mono" style="font:600 11.5px var(--font-mono);color:var(--fg3)">{{ service.code ?? '—' }}</div>
        <div style="font:600 13px var(--font-sans);color:var(--fg1)">{{ service.name }}</div>
        <div style="font:500 12.5px var(--font-sans);color:var(--fg2)">{{ service.category ?? '—' }}</div>
        <div class="mono" style="font:700 12.5px var(--font-mono);color:var(--brand-green-dark)">RM {{ service.price.toFixed(2) }}</div>
        <div>
          <Badge :tone="service.status === 'active' ? 'green' : 'neutral'">
            {{ service.status === 'active' ? t('status_active') : t('status_discontinued') }}
          </Badge>
        </div>
        <div class="row" style="gap:3px;justify-content:flex-end">
          <Btn variant="ghost" size="sm" @click="openEdit(service)">{{ t('btn_edit') }}</Btn>
          <Btn v-if="service.status === 'active'" variant="ghost" size="sm" style="color:var(--brand-red)" @click="discTarget=service">⊗</Btn>
        </div>
      </div>

      <div v-if="!services.data?.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        {{ t('svc_no_items') }}
      </div>
      </div><!-- /.table-scroll -->
    </div>

    <!-- Pagination -->
    <div v-if="services.data?.length" class="pagination">
      <div class="pagination__info">
        {{ t('pg_show') }}
        <select v-model.number="perPage" @change="setPerPage" class="per-page-select">
          <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
        </select>
        / {{ t('pg_per_page') }} · {{ services.from }}–{{ services.to }} {{ t('pg_of') }} {{ services.total }}
      </div>
      <div v-if="services.last_page > 1" class="pagination__pages">
        <button
          v-for="link in services.links" :key="link.label"
          :disabled="!link.url"
          :class="['page-btn', link.active ? 'active':'']"
          @click="goToPage(link.url)"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>

  <!-- ── Add / Edit Modal ────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <div class="modal__header">
          <h3 class="modal__title">{{ editingService ? t('svc_modal_edit') + ' · ' + editingService.name : t('svc_modal_create') }}</h3>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <form @submit.prevent="submitService" class="modal__body">
          <div class="form-grid-3" style="margin-bottom:14px">
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('svc_lbl_name') }} <span class="req">*</span></label>
              <input v-model="serviceForm.name" class="input" placeholder="Konsultasi GP" />
              <span v-if="serviceForm.errors.name" class="field__error">{{ serviceForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('svc_lbl_code') }}</label>
              <input v-model="serviceForm.code" class="input" :placeholder="t('svc_ph_code')" />
              <span v-if="serviceForm.errors.code" class="field__error">{{ serviceForm.errors.code }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('svc_lbl_category') }}</label>
              <input v-model="serviceForm.category" class="input" :placeholder="t('svc_ph_category')" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('svc_lbl_price') }} <span class="req">*</span></label>
              <input v-model.number="serviceForm.price" type="number" min="0" step="0.01" class="input" />
              <span v-if="serviceForm.errors.price" class="field__error">{{ serviceForm.errors.price }}</span>
            </div>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label class="field__label">{{ t('svc_lbl_notes') }}</label>
            <textarea v-model="serviceForm.notes" class="input" rows="2" :placeholder="t('svc_ph_notes')" style="resize:vertical"></textarea>
          </div>

          <div v-if="editingService" class="field" style="max-width:220px;margin-bottom:14px">
            <label class="field__label">{{ t('svc_lbl_status') }}</label>
            <select v-model="serviceForm.status" class="select">
              <option value="active">{{ t('status_active') }}</option>
              <option value="discontinued">{{ t('status_discontinued') }}</option>
            </select>
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="serviceForm.processing">
              {{ editingService ? t('btn_update') : t('svc_new') }}
            </Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Deactivate Confirmation ─────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="discTarget" class="modal-backdrop" @click.self="discTarget=null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title" style="color:var(--brand-red)">{{ t('svc_disc_title') }}</h3>
          <button class="modal__close" @click="discTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            {{ t('svc_disc_body', { name: discTarget.name }) }}
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="discTarget=null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDiscontinue">{{ t('svc_disc_confirm') }}</Btn>
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

.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
.kpi-grid .kpi:first-child { grid-column: span 1; }

.row--dim { opacity: .55; }

.modal-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 16px;
}
.modal {
  background: #fff; border-radius: 14px; width: 560px;
  max-width: 100%; max-height: 92vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.modal--sm { width: 420px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title  { flex:1; font:700 15px var(--font-sans); color:var(--fg1); margin:0; }
.modal__close  { width:28px; height:28px; border:0; background:var(--bg-muted); border-radius:6px; cursor:pointer; font-size:12px; color:var(--fg2); display:grid; place-items:center; flex-shrink:0; }
.modal__body   { padding: 20px; }
.modal__footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
.req { color: var(--brand-red); }

.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.field { display:flex; flex-direction:column; gap:5px; }
.field__label { font:600 11px var(--font-sans); color:var(--fg2); }
.field__error { font:500 11px var(--font-sans); color:var(--brand-red); }

@media (max-width: 900px) {
  .kpi-grid { grid-template-columns: repeat(2,1fr); }
  .form-grid-3 { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 560px) {
  .kpi-grid { grid-template-columns: 1fr; }
  .form-grid-3 { grid-template-columns: 1fr; }
  .svc-toolbar { flex-wrap: wrap; }
  .svc-toolbar__search { flex: 1 1 100% !important; max-width: none !important; }
}
</style>
