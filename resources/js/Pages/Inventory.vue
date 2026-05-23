<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import { useLocale } from '@/composables/useLocale'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  items:   { type: Object, default: () => ({ data: [], links: [] }) },
  kpis:    { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  lookups: { type: Object, default: () => ({}) },
})

const page  = usePage()
const flash = computed(() => page.props.flash?.success)
const { t } = useLocale()

// ─── Search & filter ───────────────────────────────────────────────────────
const search     = ref(props.filters.search ?? '')
const activeFilter = ref(props.filters.filter ?? 'all')
let searchTimer  = null

function applyFilters() {
  router.get(route('inventory'), {
    search: search.value || undefined,
    filter: activeFilter.value !== 'all' ? activeFilter.value : undefined,
  }, { preserveState: true, replace: true })
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})

function setFilter(f) {
  activeFilter.value = f
  applyFilters()
}

// ─── Flag helpers ──────────────────────────────────────────────────────────
const FLAG_CONFIG = computed(() => ({
  low:        { tone: 'orange', label: t('inv_flag_low') },
  expiring:   { tone: 'red',    label: t('inv_flag_expiring') },
  expired:    { tone: 'red',    label: t('inv_flag_expired') },
  poison_b:   { tone: 'red',    label: t('inv_flag_poison_b') },
  poison_c:   { tone: 'red',    label: t('inv_flag_poison_c') },
  controlled: { tone: 'red',    label: t('inv_flag_controlled') },
}))

const CLASSIFICATION_LABEL = computed(() => ({
  general: t('inv_class_general'), poison_b: t('inv_class_poison_b'),
  poison_c: t('inv_class_poison_c'), controlled: t('inv_class_controlled'),
}))

// ─── Lookups from DB ───────────────────────────────────────────────────────
const DRUG_FORMS     = computed(() => (props.lookups?.bentuk_ubat    ?? []).map(v => v.code))
const CATEGORIES     = computed(() => (props.lookups?.kategori_ubat  ?? []).map(v => v.code))
const CLASSIFICATIONS = computed(() => (props.lookups?.klasifikasi_ubat ?? []).map(v => ({ value: v.code, label: v.label_ms })))

// ─── Add/Edit Item Modal ───────────────────────────────────────────────────
const showItemModal = ref(false)
const editingItem   = ref(null)

const itemForm = useForm({
  name: '', generic_name: '', form: 'Tablet', category: '',
  classification: 'general', lot_number: '', expiry_date: '',
  supplier: '', stock_quantity: 0, reorder_level: 50,
  unit_cost: 0, selling_price: 0, unit: 'tablet', notes: '', status: 'active',
})

function openCreate() {
  editingItem.value = null
  itemForm.reset()
  itemForm.form           = 'Tablet'
  itemForm.classification = 'general'
  itemForm.reorder_level  = 50
  itemForm.unit           = 'tablet'
  itemForm.status         = 'active'
  itemForm.clearErrors()
  showItemModal.value = true
}

function openEdit(item) {
  editingItem.value       = item
  itemForm.name           = item.name
  itemForm.generic_name   = item.generic_name ?? ''
  itemForm.form           = item.form
  itemForm.category       = item.category ?? ''
  itemForm.classification = item.classification
  itemForm.lot_number     = item.lot_number ?? ''
  itemForm.expiry_date    = item.expiry_raw ?? ''
  itemForm.supplier       = item.supplier ?? ''
  itemForm.stock_quantity = item.stock_quantity
  itemForm.reorder_level  = item.reorder_level
  itemForm.unit_cost      = item.unit_cost
  itemForm.selling_price  = item.selling_price
  itemForm.unit           = item.unit
  itemForm.notes          = item.notes ?? ''
  itemForm.status         = item.status
  itemForm.clearErrors()
  showItemModal.value = true
}

function closeItemModal() { showItemModal.value = false }

function submitItem() {
  if (editingItem.value) {
    itemForm.put(route('inventory.update', editingItem.value.id), { onSuccess: closeItemModal })
  } else {
    itemForm.post(route('inventory.store'), { onSuccess: closeItemModal })
  }
}

// ─── Stock Adjustment Modal ────────────────────────────────────────────────
const showStockModal  = ref(false)
const adjustingItem   = ref(null)

const stockForm = useForm({
  type: 'in', quantity: 1, reference: '', notes: '',
})

const STOCK_TYPE_LABEL = computed(() => ({
  in: t('inv_stock_in'), out: t('inv_stock_out'), adjustment: t('inv_stock_adjustment'), disposal: t('inv_stock_disposal'),
}))

function openAdjust(item) {
  adjustingItem.value = item
  stockForm.reset()
  stockForm.type     = 'in'
  stockForm.quantity = 1
  stockForm.clearErrors()
  showStockModal.value = true
}

function closeStockModal() { showStockModal.value = false }

function submitStock() {
  stockForm.patch(route('inventory.stock', adjustingItem.value.id), {
    onSuccess: closeStockModal,
  })
}

const previewStock = computed(() => {
  if (!adjustingItem.value) return null
  const qty    = parseInt(stockForm.quantity) || 0
  const cur    = adjustingItem.value.stock_quantity
  if (stockForm.type === 'in')         return cur + qty
  if (stockForm.type === 'adjustment') return qty
  return Math.max(0, cur - qty)
})

// ─── View Drawer ───────────────────────────────────────────────────────────
const viewItem = ref(null)

function openView(item) {
  // fetch transactions lazily via same page reload with item id in query
  viewItem.value = item
}

// ─── Discontinue (soft delete) ─────────────────────────────────────────────
const discTarget = ref(null)
function doDiscontinue() {
  router.delete(route('inventory.destroy', discTarget.value.id), {
    onSuccess: () => { discTarget.value = null },
  })
}
</script>

<template>
  <div class="screen">
    <!-- Flash -->
    <div v-if="flash" class="flash-ok">{{ flash }}</div>

    <!-- KPI cards -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="kpi__label">{{ t('inv_kpi_sku') }}</div>
        <div class="kpi__value">{{ kpis.total_skus }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('inv_kpi_low') }}</div>
        <div class="kpi__value" style="color:var(--brand-orange)">{{ kpis.low_stock }}</div>
        <div class="kpi__sub">{{ t('inv_kpi_low_sub') }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('inv_kpi_expiry') }}</div>
        <div class="kpi__value" style="color:var(--brand-red)">{{ kpis.expiring_90d }}</div>
        <div class="kpi__sub">{{ t('inv_kpi_expiry_sub') }}</div>
      </div>
      <div class="kpi">
        <div class="kpi__label">{{ t('inv_kpi_value') }}</div>
        <div class="kpi__value" style="font-size:20px">{{ kpis.total_value }}</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="row">
      <!-- Search -->
      <div style="position:relative;flex:1;max-width:340px">
        <input v-model="search" class="input" :placeholder="t('inv_search')" style="padding-left:36px" />
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--fg3)">
          <Icon name="search" :size="15" />
        </span>
      </div>
      <!-- Filter chips -->
      <div class="filter-chips">
        <button v-for="f in [{k:'all',l:t('btn_all_filter')},{k:'low',l:t('inv_kpi_low')},{k:'expiring',l:t('inv_filter_expiring')},{k:'poison',l:t('inv_filter_poison')}]"
          :key="f.k"
          :class="['chip', activeFilter===f.k ? 'chip--active':'']"
          @click="setFilter(f.k)"
        >{{ f.l }}</button>
      </div>
      <div class="spacer"></div>
      <span style="font:500 12px var(--font-sans);color:var(--fg3)">{{ items.total }} item</span>
      <Btn variant="primary" @click="openCreate"><Icon name="plus" :size="14" /> {{ t('inv_new') }}</Btn>
    </div>

    <!-- Table -->
    <div class="card" style="overflow:hidden">
      <div class="table__head" style="grid-template-columns:2.2fr 1fr 80px 80px 100px 120px 90px 90px 1fr 110px">
        <div>{{ t('inv_col_drug') }}</div><div>{{ t('inv_col_form') }}</div><div>{{ t('inv_col_stock') }}</div><div>{{ t('inv_col_reorder') }}</div><div>{{ t('inv_col_expiry') }}</div><div>{{ t('inv_col_lot') }}</div><div>{{ t('inv_col_cost') }}</div><div>{{ t('inv_col_selling') }}</div><div>{{ t('inv_col_flags') }}</div><div></div>
      </div>

      <div
        v-for="item in items.data" :key="item.id"
        class="table__row"
        style="grid-template-columns:2.2fr 1fr 80px 80px 100px 120px 90px 90px 1fr 110px"
        :class="{ 'row--dim': item.status === 'discontinued' }"
      >
        <div>
          <div style="font:600 13px var(--font-sans);color:var(--fg1)">{{ item.name }}</div>
          <div v-if="item.generic_name" style="font:400 11px var(--font-sans);color:var(--fg3)">{{ item.generic_name }}</div>
        </div>
        <div style="font:500 12.5px var(--font-sans);color:var(--fg2)">{{ item.form }}</div>
        <div>
          <span
            class="mono"
            :style="{ font:'700 13px var(--font-mono)', color: item.flags.includes('low') ? 'var(--brand-orange)' : 'var(--fg1)' }"
          >{{ item.stock_quantity }}</span>
          <span style="font:400 10.5px var(--font-sans);color:var(--fg3)"> {{ item.unit }}</span>
        </div>
        <div class="mono" style="font:500 12px var(--font-mono);color:var(--fg3)">{{ item.reorder_level }}</div>
        <div
          class="mono"
          :style="{ font:'500 12px var(--font-mono)', color: item.flags.includes('expired') ? 'var(--brand-red)' : item.flags.includes('expiring') ? 'var(--brand-orange)' : 'var(--fg2)' }"
        >{{ item.expiry_date ?? '—' }}</div>
        <div class="mono" style="font:500 11.5px var(--font-mono);color:var(--fg3)">{{ item.lot_number ?? '—' }}</div>
        <div class="mono" style="font:600 12.5px var(--font-mono);color:var(--fg2)">RM {{ item.unit_cost.toFixed(2) }}</div>
        <div class="mono" style="font:700 12.5px var(--font-mono);color:var(--brand-green-dark)">
          {{ item.selling_price > 0 ? 'RM ' + item.selling_price.toFixed(2) : '—' }}
        </div>
        <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
          <Badge v-for="f in item.flags" :key="f" :tone="FLAG_CONFIG[f]?.tone">{{ FLAG_CONFIG[f]?.label }}</Badge>
          <span v-if="!item.flags.length" style="font:400 11.5px var(--font-sans);color:var(--fg3)">—</span>
        </div>
        <div class="row" style="gap:3px;justify-content:flex-end">
          <Btn variant="ghost" size="sm" @click="openView(item)">{{ t('inv_view') }}</Btn>
          <Btn variant="ghost" size="sm" @click="openAdjust(item)" :title="t('inv_adjust')">±</Btn>
          <Btn variant="ghost" size="sm" @click="openEdit(item)">{{ t('btn_edit') }}</Btn>
          <Btn variant="ghost" size="sm" style="color:var(--brand-red)" @click="discTarget=item">⊗</Btn>
        </div>
      </div>

      <div v-if="!items.data?.length" style="padding:32px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
        {{ t('inv_no_items') }}
      </div>

      <!-- Pagination -->
      <div v-if="items.last_page > 1" class="pagination">
        <button
          v-for="link in items.links" :key="link.label"
          :disabled="!link.url"
          :class="['page-btn', link.active ? 'active':'']"
          @click="link.url && router.get(link.url, {search:search||undefined,filter:activeFilter!=='all'?activeFilter:undefined},{preserveState:true})"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>

  <!-- ── View Drawer ──────────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="viewItem" class="drawer-backdrop" @click.self="viewItem=null">
      <div class="drawer">
        <div class="drawer__header">
          <div style="flex:1;min-width:0">
            <h2 class="drawer__name">{{ viewItem.name }}</h2>
            <div style="font:400 11.5px var(--font-sans);color:var(--fg3)">{{ viewItem.generic_name }}</div>
          </div>
          <div class="row" style="gap:6px;align-items:flex-start;flex-wrap:wrap">
            <Badge v-for="f in viewItem.flags" :key="f" :tone="FLAG_CONFIG[f]?.tone">{{ FLAG_CONFIG[f]?.label }}</Badge>
          </div>
          <button class="modal__close" @click="viewItem=null">✕</button>
        </div>

        <div class="drawer__body">
          <!-- Stock status bar -->
          <div class="stock-bar-wrap">
            <div class="row" style="margin-bottom:6px">
              <span style="font:700 22px var(--font-mono);color:var(--fg1)">{{ viewItem.stock_quantity }}</span>
              <span style="font:500 12px var(--font-sans);color:var(--fg3);margin-left:4px;align-self:flex-end;padding-bottom:2px">{{ viewItem.unit }}</span>
              <div class="spacer"></div>
              <span style="font:500 11.5px var(--font-sans);color:var(--fg3)">Reorder: {{ viewItem.reorder_level }}</span>
            </div>
            <div class="stock-track">
              <div class="stock-fill"
                :style="{
                  width: Math.min(100, Math.round(viewItem.stock_quantity / Math.max(viewItem.reorder_level * 2, 1) * 100)) + '%',
                  background: viewItem.flags.includes('low') ? 'var(--brand-orange)' : 'var(--brand-green)'
                }"
              ></div>
            </div>
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <!-- Details grid -->
          <div class="drow-section-title">{{ t('inv_sec_info') }}</div>
          <div class="info-grid">
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_form') }}</span><span class="info-val">{{ viewItem.form }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_category') }}</span><span class="info-val">{{ viewItem.category ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_classify') }}</span><span class="info-val">{{ CLASSIFICATION_LABEL[viewItem.classification] }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_lot') }}</span><span class="info-val mono">{{ viewItem.lot_number ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_expiry') }}</span>
              <span class="info-val mono" :style="{color: viewItem.flags.includes('expired')||viewItem.flags.includes('expiring') ? 'var(--brand-red)' : 'inherit'}">
                {{ viewItem.expiry_date ?? '—' }}
              </span>
            </div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_supplier') }}</span><span class="info-val">{{ viewItem.supplier ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_cost') }}</span><span class="info-val mono">RM {{ viewItem.unit_cost.toFixed(2) }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_selling_price') }}</span><span class="info-val mono" style="color:var(--brand-green-dark)">RM {{ viewItem.selling_price.toFixed(2) }}</span></div>
            <div class="info-row"><span class="info-label">{{ t('inv_lbl_value') }}</span><span class="info-val mono">RM {{ viewItem.stock_value?.toFixed(2) }}</span></div>
          </div>

          <div v-if="viewItem.notes" style="margin-top:12px;padding:10px 12px;background:var(--bg-muted);border-radius:8px;font:400 12.5px var(--font-sans);color:var(--fg2)">
            {{ viewItem.notes }}
          </div>

          <div class="hr" style="margin:14px 0"></div>

          <div class="row" style="gap:8px">
            <Btn variant="secondary" style="flex:1" @click="openAdjust(viewItem);viewItem=null">{{ t('inv_adjust') }}</Btn>
            <Btn variant="ghost"   size="sm" @click="openEdit(viewItem);viewItem=null">{{ t('btn_edit') }}</Btn>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── Add / Edit Item Modal ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showItemModal" class="modal-backdrop" @click.self="closeItemModal">
      <div class="modal modal--lg">
        <div class="modal__header">
          <h3 class="modal__title">{{ editingItem ? t('inv_modal_edit') + ' · ' + editingItem.name : t('inv_modal_create') }}</h3>
          <button class="modal__close" @click="closeItemModal">✕</button>
        </div>
        <form @submit.prevent="submitItem" class="modal__body">

          <div class="modal-section-title">{{ t('inv_sec_drug') }}</div>
          <div class="form-grid-3" style="margin-bottom:14px">
            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('inv_lbl_name') }} <span class="req">*</span></label>
              <input v-model="itemForm.name" class="input" placeholder="Amoxicillin 500mg" />
              <span v-if="itemForm.errors.name" class="field__error">{{ itemForm.errors.name }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_generic') }}</label>
              <input v-model="itemForm.generic_name" class="input" placeholder="Amoxicillin" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_form') }} <span class="req">*</span></label>
              <select v-model="itemForm.form" class="select">
                <option v-for="f in DRUG_FORMS" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_unit') }}</label>
              <input v-model="itemForm.unit" class="input" :placeholder="t('inv_ph_unit')" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_category') }}</label>
              <select v-model="itemForm.category" class="select">
                <option value="">— {{ t('inv_select_category') }} —</option>
                <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_classify') }} <span class="req">*</span></label>
              <select v-model="itemForm.classification" class="select">
                <option v-for="c in CLASSIFICATIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <div class="modal-section-title">{{ t('inv_sec_stock') }}</div>
          <div class="form-grid-4" style="margin-bottom:14px">
            <div class="field">
              <label class="field__label">{{ editingItem ? t('inv_stock_current') : t('inv_stock_initial') }} <span class="req">*</span></label>
              <input v-model.number="itemForm.stock_quantity" type="number" min="0" class="input" :disabled="!!editingItem" />
              <span v-if="itemForm.errors.stock_quantity" class="field__error">{{ itemForm.errors.stock_quantity }}</span>
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_reorder') }} <span class="req">*</span></label>
              <input v-model.number="itemForm.reorder_level" type="number" min="0" class="input" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_unit_cost') }} <span class="req">*</span></label>
              <input v-model.number="itemForm.unit_cost" type="number" min="0" step="0.01" class="input" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_selling_price') }} <span class="req">*</span></label>
              <input v-model.number="itemForm.selling_price" type="number" min="0" step="0.01" class="input" />
              <span v-if="itemForm.errors.selling_price" class="field__error">{{ itemForm.errors.selling_price }}</span>
            </div>
          </div>

          <div class="modal-section-title">{{ t('inv_sec_lot') }}</div>
          <div class="form-grid-3" style="margin-bottom:14px">
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_lot') }}</label>
              <input v-model="itemForm.lot_number" class="input" placeholder="AMX-2403" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_expiry') }}</label>
              <input v-model="itemForm.expiry_date" type="date" class="input" />
            </div>
            <div class="field">
              <label class="field__label">{{ t('inv_lbl_supplier') }}</label>
              <input v-model="itemForm.supplier" class="input" placeholder="Pharmaniaga" />
            </div>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label class="field__label">{{ t('inv_lbl_notes') }}</label>
            <textarea v-model="itemForm.notes" class="input" rows="2" :placeholder="t('inv_ph_notes')" style="resize:vertical"></textarea>
          </div>

          <div v-if="editingItem" class="field" style="max-width:220px;margin-bottom:14px">
            <label class="field__label">{{ t('inv_lbl_status') }}</label>
            <select v-model="itemForm.status" class="select">
              <option value="active">{{ t('status_active') }}</option>
              <option value="discontinued">{{ t('status_discontinued') }}</option>
            </select>
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeItemModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="itemForm.processing">
              {{ editingItem ? t('btn_update') : t('inv_add_item_btn') }}
            </Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Stock Adjustment Modal ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showStockModal" class="modal-backdrop" @click.self="closeStockModal">
      <div class="modal modal--sm">
        <div class="modal__header">
          <div style="flex:1;min-width:0">
            <h3 class="modal__title">{{ t('inv_stock_modal') }}</h3>
            <div style="font:500 11.5px var(--font-sans);color:var(--fg3)">{{ adjustingItem?.name }}</div>
          </div>
          <button class="modal__close" @click="closeStockModal">✕</button>
        </div>
        <form @submit.prevent="submitStock" class="modal__body">

          <!-- Current stock display -->
          <div class="stock-current-box">
            <div>
              <div style="font:600 10.5px var(--font-sans);letter-spacing:.06em;text-transform:uppercase;color:var(--fg3)">{{ t('inv_stock_current') }}</div>
              <div style="font:800 28px var(--font-mono);color:var(--fg1)">{{ adjustingItem?.stock_quantity }}</div>
              <div style="font:400 11.5px var(--font-sans);color:var(--fg3)">{{ adjustingItem?.unit }}</div>
            </div>
            <div class="stock-arrow">→</div>
            <div :style="{ color: previewStock <= adjustingItem?.reorder_level ? 'var(--brand-orange)' : 'var(--brand-green-dark)' }">
              <div style="font:600 10.5px var(--font-sans);letter-spacing:.06em;text-transform:uppercase;color:var(--fg3)">{{ t('inv_stock_after') }}</div>
              <div style="font:800 28px var(--font-mono)">{{ previewStock ?? '—' }}</div>
              <div style="font:400 11.5px var(--font-sans);color:var(--fg3)">{{ adjustingItem?.unit }}</div>
            </div>
          </div>

          <!-- Type selector -->
          <div class="stock-type-grid">
            <button
              v-for="stype in [{v:'in',l:t('inv_stock_in'),i:'↑'},{v:'out',l:t('inv_stock_out'),i:'↓'},{v:'adjustment',l:t('inv_stock_adjustment_short'),i:'⇄'},{v:'disposal',l:t('inv_stock_disposal'),i:'×'}]"
              :key="stype.v"
              type="button"
              :class="['stock-type-btn', stockForm.type===stype.v ? 'active':'']"
              @click="stockForm.type=stype.v"
            >
              <span class="stock-type-icon">{{ stype.i }}</span>
              <span>{{ stype.l }}</span>
            </button>
          </div>

          <div class="field" style="margin-bottom:12px">
            <label class="field__label">
              {{ stockForm.type === 'adjustment' ? t('inv_qty_actual') : t('inv_qty_label') }}
              <span class="req">*</span>
            </label>
            <input v-model.number="stockForm.quantity" type="number" min="1" class="input" style="font:700 16px var(--font-mono)" />
            <span v-if="stockForm.errors.quantity" class="field__error">{{ stockForm.errors.quantity }}</span>
            <span v-if="stockForm.type==='adjustment'" class="field__hint" style="font:400 11px var(--font-sans);color:var(--fg3)">
              {{ t('inv_qty_adjustment_hint') }}
            </span>
          </div>

          <div class="field" style="margin-bottom:12px">
            <label class="field__label">{{ t('inv_lbl_ref') }}</label>
            <input v-model="stockForm.reference" class="input" :placeholder="t('inv_ph_ref')" />
          </div>

          <div class="field">
            <label class="field__label">{{ t('inv_lbl_stock_notes') }}</label>
            <input v-model="stockForm.notes" class="input" :placeholder="t('inv_ph_stock_notes')" />
          </div>

          <div class="modal__footer">
            <Btn type="button" variant="secondary" @click="closeStockModal">{{ t('btn_cancel') }}</Btn>
            <Btn type="submit" variant="primary" :disabled="stockForm.processing">{{ t('btn_save') }}</Btn>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Discontinue Confirmation ───────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="discTarget" class="modal-backdrop" @click.self="discTarget=null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h3 class="modal__title" style="color:var(--brand-red)">{{ t('inv_disc_title') }}</h3>
          <button class="modal__close" @click="discTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <p style="font:400 13.5px var(--font-sans);color:var(--fg2);line-height:1.6;margin:0 0 16px">
            {{ t('inv_disc_body', { name: discTarget.name }) }}
          </p>
          <div class="modal__footer">
            <Btn variant="secondary" @click="discTarget=null">{{ t('btn_cancel') }}</Btn>
            <Btn variant="primary" style="background:var(--brand-red)" @click="doDiscontinue">{{ t('inv_disc_confirm') }}</Btn>
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

/* Filter chips */
.filter-chips { display: flex; gap: 6px; }
.chip {
  padding: 6px 14px; border: 1px solid var(--border); border-radius: 999px;
  background: #fff; color: var(--fg2); font: 500 12.5px var(--font-sans); cursor: pointer;
}
.chip:hover { border-color: var(--brand-green); color: var(--brand-green-dark); }
.chip--active { background: var(--brand-green); border-color: var(--brand-green); color: #fff; font-weight: 700; }

.row--dim { opacity: .55; }

/* ── Modals ── */
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
.modal--lg { width: 700px; }
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title  { flex:1; font:700 15px var(--font-sans); color:var(--fg1); margin:0; }
.modal__close  { width:28px; height:28px; border:0; background:var(--bg-muted); border-radius:6px; cursor:pointer; font-size:12px; color:var(--fg2); display:grid; place-items:center; flex-shrink:0; }
.modal__body   { padding: 20px; }
.modal__footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
.modal-section-title { font:700 11px var(--font-sans); letter-spacing:.06em; text-transform:uppercase; color:var(--fg3); margin-bottom:10px; }
.req { color: var(--brand-red); }

.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; }
.field { display:flex; flex-direction:column; gap:5px; }
.field__label { font:600 11px var(--font-sans); color:var(--fg2); }
.field__error { font:500 11px var(--font-sans); color:var(--brand-red); }

/* Stock adjustment */
.stock-current-box {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-soft); border-radius: 10px;
  padding: 14px 18px; margin-bottom: 18px;
}
.stock-arrow { font: 700 20px var(--font-mono); color: var(--fg3); }
.stock-type-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;
}
.stock-type-btn {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 10px 12px; border: 2px solid var(--border); border-radius: 10px;
  background: #fff; cursor: pointer; font: 500 12px var(--font-sans); color: var(--fg2);
}
.stock-type-btn:hover { border-color: var(--brand-green); }
.stock-type-btn.active { border-color: var(--brand-green); background: var(--brand-green-light); color: var(--brand-green-dark); font-weight: 700; }
.stock-type-icon { font-size: 18px; font-weight: 700; }

/* ── Drawer ── */
.drawer-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.35); display:flex; justify-content:flex-end; z-index:9999; }
.drawer { width:420px; max-width:100%; background:#fff; height:100%; overflow-y:auto; box-shadow:-8px 0 32px rgba(15,23,42,.12); display:flex; flex-direction:column; }
.drawer__header { display:flex; align-items:flex-start; gap:12px; padding:20px; border-bottom:1px solid var(--border); position:sticky; top:0; background:#fff; z-index:1; }
.drawer__name { font:700 16px var(--font-sans); color:var(--fg1); margin:0 0 4px; }
.drawer__body { padding:18px 20px; flex:1; }
.drow-section-title { font:700 10.5px var(--font-sans); letter-spacing:.06em; text-transform:uppercase; color:var(--fg3); margin-bottom:10px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.info-row { display:flex; flex-direction:column; gap:2px; }
.info-label { font:600 10.5px var(--font-sans); letter-spacing:.04em; text-transform:uppercase; color:var(--fg3); }
.info-val { font:500 13px var(--font-sans); color:var(--fg1); }
.info-val.mono { font-family:var(--font-mono); font-size:12px; }

/* Stock bar */
.stock-bar-wrap { margin-bottom: 4px; }
.stock-track { height: 8px; background: var(--bg-muted); border-radius: 4px; overflow: hidden; }
.stock-fill  { height: 100%; border-radius: 4px; transition: width .3s; }

/* Pagination */
.pagination { display:flex; gap:4px; padding:12px 16px; border-top:1px solid var(--border); justify-content:center; }
.page-btn { min-width:32px; height:32px; border:1px solid var(--border); border-radius:6px; background:#fff; color:var(--fg2); font:500 12px var(--font-sans); cursor:pointer; padding:0 8px; }
.page-btn.active { background:var(--brand-green); border-color:var(--brand-green); color:#fff; font-weight:700; }
.page-btn:disabled { opacity:.4; cursor:default; }
</style>
