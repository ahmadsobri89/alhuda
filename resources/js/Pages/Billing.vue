<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  invoices: Object,
  selected: Object,
  patients: Array,
  stats:    Object,
  filters:  Object,
  today:    String,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

/* ── filters ── */
const search     = ref(props.filters?.search ?? '')
const statusFilt = ref(props.filters?.status ?? '')

function applyFilter () {
  router.get('/billing', {
    search:  search.value || undefined,
    status:  statusFilt.value || undefined,
    invoice: props.selected?.id,
  }, { preserveState: true, replace: true })
}

let searchTimer
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilter, 350) })

function setStatus (s) { statusFilt.value = s; applyFilter() }

function selectInvoice (id) {
  router.get('/billing', {
    search:  search.value || undefined,
    status:  statusFilt.value || undefined,
    invoice: id,
  }, { preserveState: true, replace: true })
}

/* ── labels ── */
const statusTone  = { draft:'neutral', unpaid:'yellow', paid:'green', cancelled:'red' }
const statusLabel = { draft:'Draf', unpaid:'Belum Bayar', paid:'Bayar', cancelled:'Batal' }
const methodLabel = { cash:'Tunai', card:'Kad', duitnow:'DuitNow', panel:'Panel', insurance:'Insurans' }
function typeTone (t) {
  return { consultation:'blue', procedure:'purple', drug:'green', lab:'yellow', other:'neutral' }[t] ?? 'neutral'
}
const typeLabel = { consultation:'Konsultasi', procedure:'Prosedur', drug:'Ubat', lab:'Makmal', other:'Lain-lain' }

/* ── quick service presets ── */
const quickServices = [
  { code:'CONS-001', description:'Konsultasi GP',           type:'consultation', unit_price:35 },
  { code:'CONS-002', description:'Konsultasi Pakar',        type:'consultation', unit_price:80 },
  { code:'PROC-001', description:'Cucian Luka',             type:'procedure',    unit_price:15 },
  { code:'PROC-002', description:'Jahitan (per jahit)',     type:'procedure',    unit_price:20 },
  { code:'PROC-003', description:'Swab Tekak',              type:'procedure',    unit_price:25 },
  { code:'PROC-004', description:'Suntikan IM',             type:'procedure',    unit_price:10 },
  { code:'LAB-001',  description:'Darah Lengkap (FBC)',     type:'lab',          unit_price:30 },
  { code:'LAB-002',  description:'Gula Darah (HbA1c)',      type:'lab',          unit_price:45 },
  { code:'LAB-003',  description:'Ujian Air Kencing',       type:'lab',          unit_price:15 },
  { code:'DRUG-PCM', description:'Paracetamol 1g',          type:'drug',         unit_price:0.20 },
  { code:'DRUG-AMX', description:'Amoxicillin 500mg',       type:'drug',         unit_price:0.40 },
  { code:'DRUG-IBU', description:'Ibuprofen 400mg',         type:'drug',         unit_price:0.30 },
]

/* ── add-item drawer ── */
const showAddDrawer = ref(false)
const itemForm = useForm({ type:'consultation', code:'', description:'', quantity:1, unit_price:0 })
const itemLiveTotal = computed(() =>
  (parseFloat(itemForm.quantity) || 0) * (parseFloat(itemForm.unit_price) || 0)
)
function openAddDrawer () { itemForm.reset(); showAddDrawer.value = true }
function fillQuick (q) {
  itemForm.type        = q.type
  itemForm.code        = q.code
  itemForm.description = q.description
  itemForm.unit_price  = q.unit_price
  itemForm.quantity    = 1
}
function addItem () {
  itemForm.post(`/billing/${props.selected.id}/items`, {
    onSuccess: () => { showAddDrawer.value = false; itemForm.reset() },
  })
}
function removeItem (itemId) {
  router.delete(`/billing/${props.selected.id}/items/${itemId}`, { preserveScroll: true })
}

/* ── discount ── */
const discountEdit = ref(false)
const discountForm = useForm({ discount_amount: 0 })
function openDiscount () { discountForm.discount_amount = props.selected?.discount_amount ?? 0; discountEdit.value = true }
function saveDiscount () {
  discountForm.patch(`/billing/${props.selected.id}/discount`, { onSuccess: () => { discountEdit.value = false } })
}

/* ── finalize ── */
function finalize () { router.patch(`/billing/${props.selected.id}/finalize`, {}, { preserveScroll: true }) }

/* ── payment modal ── */
const showPayModal = ref(false)
const payMethod    = ref('cash')
const payMethods   = [
  { value:'cash',      label:'Tunai' },
  { value:'card',      label:'Kad' },
  { value:'duitnow',   label:'DuitNow' },
  { value:'panel',     label:'Panel / GL' },
  { value:'insurance', label:'Insurans' },
]
function submitPay () {
  router.patch(`/billing/${props.selected.id}/pay`,
    { payment_method: payMethod.value },
    { onSuccess: () => { showPayModal.value = false } }
  )
}

/* ── cancel / delete ── */
const showCancelModal = ref(false)
const showDeleteModal = ref(false)
function confirmCancel () { router.patch(`/billing/${props.selected.id}/cancel`, {}, { onSuccess: () => { showCancelModal.value = false } }) }
function confirmDelete () { router.delete(`/billing/${props.selected.id}`, { onSuccess: () => { showDeleteModal.value = false } }) }

/* ── new invoice modal ── */
const showNewModal  = ref(false)
const patSearch     = ref('')
const patDrop       = ref(false)
const chosenPat     = ref(null)
const filteredPats  = computed(() => {
  const q = patSearch.value.toLowerCase()
  return props.patients.filter(p =>
    p.name.toLowerCase().includes(q) || p.ic_number.includes(q) || p.patient_id.toLowerCase().includes(q)
  ).slice(0, 8)
})
const newForm = useForm({ patient_id: null, invoice_date: props.today, notes: '' })
function choosePat (p) { chosenPat.value = p; newForm.patient_id = p.id; patSearch.value = p.name; patDrop.value = false }
function submitNew () {
  newForm.post('/billing', {
    onSuccess: () => { showNewModal.value = false; newForm.reset(); patSearch.value = ''; chosenPat.value = null },
  })
}
</script>

<template>
  <div class="billing-root">

    <!-- ══ LEFT: senarai invois ══ -->
    <aside class="inv-list">
      <div class="inv-list__hd">
        <span class="inv-list__title">Bil &amp; Invois</span>
        <Btn variant="primary" size="sm" @click="showNewModal=true">+ Invois</Btn>
      </div>

      <div style="padding:10px 12px 0">
        <input v-model="search" class="input" style="width:100%;font-size:12px" placeholder="Cari invois / pesakit…" />
      </div>

      <div class="sf-chips">
        <button v-for="(lbl,val) in { '':'Semua', draft:'Draf', unpaid:'Belum Bayar', paid:'Bayar', cancelled:'Batal' }"
          :key="val" class="sf-chip" :class="{ active: statusFilt===val }" @click="setStatus(val)">{{ lbl }}</button>
      </div>

      <div class="inv-items">
        <div v-for="inv in invoices.data" :key="inv.id"
          class="inv-item" :class="{ active: selected?.id===inv.id }"
          @click="selectInvoice(inv.id)">
          <div class="inv-item__row1">
            <span class="inv-item__num">{{ inv.invoice_number }}</span>
            <Badge :tone="statusTone[inv.status]" size="xs">{{ statusLabel[inv.status] }}</Badge>
          </div>
          <div class="inv-item__name">{{ inv.patient_name }}</div>
          <div class="inv-item__meta">
            <span>{{ inv.invoice_date }}</span>
            <span class="inv-item__amt">RM {{ Number(inv.total_amount).toFixed(2) }}</span>
          </div>
        </div>
        <p v-if="!invoices.data.length" class="inv-empty">Tiada invois ditemui</p>
      </div>

      <div v-if="invoices.last_page>1" class="pg-bar">
        <button v-for="link in invoices.links" :key="link.label"
          v-html="link.label" class="pg-btn"
          :class="{ active:link.active, disabled:!link.url }" :disabled="!link.url"
          @click="link.url && router.get(link.url,{},{preserveState:true})" />
      </div>
    </aside>

    <!-- ══ RIGHT: detail ══ -->
    <main class="inv-detail">

      <!-- stats bar -->
      <div class="stats-bar">
        <div class="stat-box">
          <div class="stat-lbl">Pendapatan Hari Ini</div>
          <div class="stat-val">RM {{ Number(stats.today_revenue).toFixed(2) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Kutipan Bulan Ini</div>
          <div class="stat-val">RM {{ Number(stats.month_collected).toFixed(2) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Invois Tertunggak</div>
          <div class="stat-val">{{ stats.outstanding_count }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">Jumlah Tertunggak</div>
          <div class="stat-val">RM {{ Number(stats.outstanding_amount).toFixed(2) }}</div>
        </div>
      </div>

      <!-- flash -->
      <div v-if="flash?.success" class="flash-ok">{{ flash.success }}</div>

      <!-- empty -->
      <div v-if="!selected" class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--fg3)" stroke-width="1.5">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="1"/>
          <path d="M9 12h6M9 16h4"/>
        </svg>
        <p>Pilih invois dari senarai atau cipta invois baharu.</p>
      </div>

      <!-- invoice detail -->
      <div v-else class="inv-scroll">

        <!-- header -->
        <div class="inv-hd-card">
          <div class="inv-hd-left">
            <div class="inv-hd-top">
              <span class="inv-num">{{ selected.invoice_number }}</span>
              <Badge :tone="statusTone[selected.status]">{{ statusLabel[selected.status] }}</Badge>
              <Badge v-if="selected.payment_method" tone="blue">{{ methodLabel[selected.payment_method] }}</Badge>
            </div>
            <div class="inv-hd-sub">{{ selected.patient_name }} · {{ selected.patient_ic }} · {{ selected.invoice_date }}</div>
          </div>
          <div class="inv-hd-actions">
            <Btn v-if="selected.status!=='paid'&&selected.status!=='cancelled'"
              variant="ghost" size="sm" style="color:#DC2626" @click="showCancelModal=true">Batalkan</Btn>
            <Btn v-if="selected.status!=='paid'" variant="ghost" size="sm" @click="showDeleteModal=true">Padam</Btn>
          </div>
        </div>

        <!-- 2-col body -->
        <div class="inv-body">

          <!-- items column -->
          <div class="card" style="flex:1;min-width:0">
            <div class="card__header">
              <h3 class="card__title">Item Invois</h3>
              <Btn v-if="['draft','unpaid'].includes(selected.status)"
                variant="primary" size="sm" @click="openAddDrawer">+ Tambah Item</Btn>
            </div>

            <!-- table -->
            <div class="table__head" style="grid-template-columns:90px 1fr 60px 90px 90px 32px">
              <div>Jenis</div><div>Penerangan</div><div>Qty</div><div>Harga</div><div>Jumlah</div><div></div>
            </div>
            <div v-for="item in selected.items" :key="item.id"
              class="table__row" style="grid-template-columns:90px 1fr 60px 90px 90px 32px">
              <div><Badge :tone="typeTone(item.type)" size="xs">{{ typeLabel[item.type] }}</Badge></div>
              <div>
                <div style="font:500 12.5px var(--font-sans);color:var(--fg1)">{{ item.description }}</div>
                <div v-if="item.code" style="font:400 11px var(--font-mono);color:var(--fg3)">{{ item.code }}</div>
              </div>
              <div style="font:500 12px var(--font-mono);color:var(--fg2)">{{ item.quantity }}</div>
              <div style="font:500 12px var(--font-mono);color:var(--fg2)">{{ Number(item.unit_price).toFixed(2) }}</div>
              <div style="font:700 13px var(--font-mono);color:var(--fg1)">{{ Number(item.total_price).toFixed(2) }}</div>
              <div>
                <button v-if="['draft','unpaid'].includes(selected.status)"
                  class="rm-btn" @click="removeItem(item.id)" title="Padam item">✕</button>
              </div>
            </div>

            <p v-if="!selected.items.length"
              style="padding:28px 16px;text-align:center;color:var(--fg3);font:400 12.5px var(--font-sans)">
              Belum ada item — klik <strong>+ Tambah Item</strong> untuk mula.
            </p>
          </div>

          <!-- right column -->
          <div class="right-col">

            <!-- summary -->
            <div class="card">
              <div class="card__header"><h3 class="card__title">Ringkasan</h3></div>
              <div class="card__body" style="display:flex;flex-direction:column;gap:10px">
                <div class="sum-row">
                  <span class="sum-lbl">Subtotal</span>
                  <span class="sum-val">RM {{ Number(selected.subtotal).toFixed(2) }}</span>
                </div>
                <div class="sum-row">
                  <span class="sum-lbl">Diskaun</span>
                  <span v-if="!discountEdit" class="sum-val">
                    RM {{ Number(selected.discount_amount).toFixed(2) }}
                    <button v-if="['draft','unpaid'].includes(selected.status)"
                      @click="openDiscount" class="edit-link">Edit</button>
                  </span>
                  <span v-else style="display:flex;gap:4px;align-items:center">
                    <input v-model.number="discountForm.discount_amount" type="number" min="0" step="0.50"
                      class="input" style="width:72px;font-size:12px;padding:3px 6px" />
                    <Btn variant="primary" size="sm" @click="saveDiscount">OK</Btn>
                  </span>
                </div>
                <div class="hr" />
                <div class="sum-row">
                  <span style="font:700 13px var(--font-sans);color:var(--fg1)">JUMLAH</span>
                  <span class="total-val">RM {{ Number(selected.total_amount).toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <!-- actions -->
            <div class="card">
              <div class="card__header"><h3 class="card__title">Tindakan</h3></div>
              <div class="card__body" style="display:flex;flex-direction:column;gap:8px">

                <template v-if="selected.status==='draft'">
                  <Btn variant="secondary" @click="finalize" style="width:100%;justify-content:center">Muktamadkan Invois</Btn>
                  <Btn variant="primary" @click="showPayModal=true" style="width:100%;justify-content:center">Kutip Pembayaran</Btn>
                </template>

                <template v-else-if="selected.status==='unpaid'">
                  <Btn variant="primary" @click="showPayModal=true" style="width:100%;justify-content:center">Kutip Pembayaran</Btn>
                </template>

                <template v-else-if="selected.status==='paid'">
                  <div class="paid-box">
                    <div class="paid-box__title">✓ Diterima</div>
                    <div class="paid-box__meta">{{ methodLabel[selected.payment_method] }} · {{ selected.paid_at }}</div>
                    <div class="paid-box__meta">oleh {{ selected.paid_by }}</div>
                  </div>
                </template>

                <template v-else-if="selected.status==='cancelled'">
                  <div class="cancelled-box">✕ Dibatalkan</div>
                </template>

              </div>
            </div>

            <div v-if="selected.notes" class="card">
              <div class="card__header"><h3 class="card__title">Nota</h3></div>
              <div class="card__body">
                <p style="font:400 12px var(--font-sans);color:var(--fg2);white-space:pre-wrap">{{ selected.notes }}</p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- ══ DRAWER: tambah item ══ -->
  <div v-if="showAddDrawer" class="drawer-backdrop" @click.self="showAddDrawer=false">
      <div class="add-drawer">
        <div class="add-drawer__hd">
          <span style="font:700 14px var(--font-sans);color:var(--fg1)">Tambah Item</span>
          <button class="modal__close" @click="showAddDrawer=false">✕</button>
        </div>

        <div class="add-drawer__body">

          <!-- quick chips -->
          <div class="qsec">
            <div class="qsec__label">Perkhidmatan Biasa</div>
            <div class="qsec__grid">
              <button v-for="q in quickServices" :key="q.code"
                class="qchip"
                :class="{ active: itemForm.code===q.code }"
                @click="fillQuick(q)">
                <span class="qchip__name">{{ q.description }}</span>
                <span class="qchip__price">RM {{ q.unit_price.toFixed(2) }}</span>
              </button>
            </div>
          </div>

          <div class="hr" style="margin:4px 0" />

          <!-- form fields -->
          <div class="field">
            <label class="field__label">Jenis *</label>
            <select v-model="itemForm.type" class="select">
              <option value="consultation">Konsultasi</option>
              <option value="procedure">Prosedur</option>
              <option value="drug">Ubat</option>
              <option value="lab">Makmal</option>
              <option value="other">Lain-lain</option>
            </select>
          </div>

          <div class="field">
            <label class="field__label">Penerangan *</label>
            <input v-model="itemForm.description" class="input" placeholder="Nama item / ubat…" />
            <p v-if="itemForm.errors.description" class="field__err">{{ itemForm.errors.description }}</p>
          </div>

          <div class="field">
            <label class="field__label">Kod (pilihan)</label>
            <input v-model="itemForm.code" class="input" placeholder="cth. CONS-001" />
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field">
              <label class="field__label">Kuantiti *</label>
              <input v-model.number="itemForm.quantity" type="number" min="0.01" step="1" class="input" />
            </div>
            <div class="field">
              <label class="field__label">Harga Unit (RM) *</label>
              <input v-model.number="itemForm.unit_price" type="number" min="0" step="0.01" class="input" />
            </div>
          </div>

          <!-- live total -->
          <div class="live-total">
            <span style="font:500 12px var(--font-sans);color:var(--fg3)">Jumlah baris</span>
            <span style="font:800 20px var(--font-mono);color:var(--brand-green)">
              RM {{ itemLiveTotal.toFixed(2) }}
            </span>
          </div>

        </div>

        <div class="add-drawer__ft">
          <Btn variant="secondary" @click="showAddDrawer=false">Batal</Btn>
          <Btn variant="primary" :loading="itemForm.processing" @click="addItem">Tambah ke Invois</Btn>
        </div>
      </div>
  </div>

  <!-- ══ PAYMENT MODAL ══ -->
  <div v-if="showPayModal" class="modal-backdrop" @click.self="showPayModal=false">
    <div class="modal" style="width:380px">
      <div class="modal__header">
        <h3 class="modal__title">Kutip Pembayaran</h3>
        <button class="modal__close" @click="showPayModal=false">✕</button>
      </div>
      <div class="modal__body" style="display:flex;flex-direction:column;gap:16px">
        <div style="text-align:center;padding:16px 0">
          <div style="font:500 12px var(--font-sans);color:var(--fg3)">Jumlah Perlu Dibayar</div>
          <div style="font:800 32px var(--font-mono);color:var(--brand-green);margin-top:4px">
            RM {{ selected ? Number(selected.total_amount).toFixed(2) : '0.00' }}
          </div>
        </div>
        <div class="field">
          <label class="field__label">Kaedah Pembayaran</label>
          <div class="pay-grid">
            <button v-for="m in payMethods" :key="m.value"
              class="pay-btn" :class="{ active: payMethod===m.value }"
              @click="payMethod=m.value">{{ m.label }}</button>
          </div>
        </div>
      </div>
      <div class="modal__footer">
        <Btn variant="secondary" @click="showPayModal=false">Batal</Btn>
        <Btn variant="primary" @click="submitPay">Sahkan Pembayaran</Btn>
      </div>
    </div>
  </div>

  <!-- ══ CANCEL CONFIRM ══ -->
  <div v-if="showCancelModal" class="modal-backdrop" @click.self="showCancelModal=false">
    <div class="modal" style="width:360px">
      <div class="modal__header">
        <h3 class="modal__title">Batalkan Invois?</h3>
        <button class="modal__close" @click="showCancelModal=false">✕</button>
      </div>
      <div class="modal__body">
        <p style="font:400 13px var(--font-sans);color:var(--fg2)">
          Invois <strong>{{ selected?.invoice_number }}</strong> akan dibatalkan. Tindakan ini tidak boleh dibuat asal.
        </p>
      </div>
      <div class="modal__footer">
        <Btn variant="secondary" @click="showCancelModal=false">Tidak</Btn>
        <Btn variant="primary" style="background:#DC2626" @click="confirmCancel">Ya, Batalkan</Btn>
      </div>
    </div>
  </div>

  <!-- ══ DELETE CONFIRM ══ -->
  <div v-if="showDeleteModal" class="modal-backdrop" @click.self="showDeleteModal=false">
    <div class="modal" style="width:360px">
      <div class="modal__header">
        <h3 class="modal__title">Padam Invois?</h3>
        <button class="modal__close" @click="showDeleteModal=false">✕</button>
      </div>
      <div class="modal__body">
        <p style="font:400 13px var(--font-sans);color:var(--fg2)">
          Invois <strong>{{ selected?.invoice_number }}</strong> dan semua itemnya akan dipadam kekal.
        </p>
      </div>
      <div class="modal__footer">
        <Btn variant="secondary" @click="showDeleteModal=false">Tidak</Btn>
        <Btn variant="primary" style="background:#DC2626" @click="confirmDelete">Ya, Padam</Btn>
      </div>
    </div>
  </div>

  <!-- ══ NEW INVOICE MODAL ══ -->
  <div v-if="showNewModal" class="modal-backdrop" @click.self="showNewModal=false">
    <div class="modal" style="width:480px">
      <div class="modal__header">
        <h3 class="modal__title">Invois Baharu</h3>
        <button class="modal__close" @click="showNewModal=false">✕</button>
      </div>
      <div class="modal__body" style="display:flex;flex-direction:column;gap:14px">

        <div class="field" style="position:relative">
          <label class="field__label">Pesakit *</label>
          <input v-model="patSearch" class="input"
            placeholder="Cari nama / IC / ID pesakit…"
            @focus="patDrop=true" @blur="setTimeout(()=>patDrop=false,180)" autocomplete="off" />
          <div v-if="patDrop && filteredPats.length" class="pdrop">
            <button v-for="p in filteredPats" :key="p.id"
              class="pdrop__item" @mousedown.prevent="choosePat(p)">
              <span style="font:600 12.5px var(--font-sans)">{{ p.name }}</span>
              <span style="font:400 11px var(--font-sans);color:var(--fg3)">{{ p.ic_number }} · {{ p.patient_id }}</span>
            </button>
          </div>
          <p v-if="newForm.errors.patient_id" class="field__err">{{ newForm.errors.patient_id }}</p>
        </div>

        <div class="field">
          <label class="field__label">Tarikh Invois *</label>
          <input v-model="newForm.invoice_date" type="date" class="input" />
        </div>

        <div class="field">
          <label class="field__label">Nota (pilihan)</label>
          <textarea v-model="newForm.notes" class="input" rows="2" placeholder="Nota tambahan…" />
        </div>
      </div>
      <div class="modal__footer">
        <Btn variant="secondary" @click="showNewModal=false">Batal</Btn>
        <Btn variant="primary" :loading="newForm.processing" @click="submitNew">Cipta Invois</Btn>
      </div>
    </div>
  </div>

</template>

<style scoped>
/* ── root layout ── */
.billing-root {
  display: flex;
  height: calc(100vh - 56px);
  overflow: hidden;
}

/* ── left: invoice list ── */
.inv-list {
  width: 280px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
  border-right: 1px solid var(--border);
  overflow: hidden;
}
.inv-list__hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 12px 10px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.inv-list__title { font: 700 14px var(--font-sans); color: var(--fg1); }

.sf-chips { display: flex; gap: 4px; flex-wrap: wrap; padding: 8px 12px 6px; flex-shrink: 0; }
.sf-chip {
  font: 500 11px var(--font-sans); padding: 3px 10px; border-radius: 999px;
  border: 1px solid var(--border); background: #fff; color: var(--fg3); cursor: pointer; transition: all .15s;
}
.sf-chip.active, .sf-chip:hover { background: var(--brand-green-light); border-color: var(--brand-green); color: var(--brand-green); }

.inv-items { flex: 1; overflow-y: auto; }
.inv-item {
  padding: 10px 12px; border-bottom: 1px solid var(--border); cursor: pointer; transition: background .12s;
}
.inv-item:hover { background: var(--bg-soft); }
.inv-item.active { background: var(--brand-green-light); border-left: 3px solid var(--brand-green); }
.inv-item__row1 { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.inv-item__num  { font: 700 11.5px var(--font-mono); color: var(--fg1); }
.inv-item__name { font: 500 12.5px var(--font-sans); color: var(--fg2); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.inv-item__meta { display: flex; justify-content: space-between; margin-top: 4px; font: 400 11px var(--font-sans); color: var(--fg3); }
.inv-item__amt  { font: 700 12px var(--font-mono); color: var(--fg1); }
.inv-empty { padding: 32px 12px; text-align: center; color: var(--fg3); font: 400 12px var(--font-sans); }

.pg-bar { display: flex; gap: 4px; padding: 8px 12px; border-top: 1px solid var(--border); flex-wrap: wrap; flex-shrink: 0; }
.pg-btn { font: 500 11px var(--font-sans); padding: 3px 8px; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; }
.pg-btn.active { background: var(--brand-green); color: #fff; border-color: var(--brand-green); }
.pg-btn.disabled { opacity: .4; cursor: default; }

/* ── right: detail ── */
.inv-detail {
  flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--bg-soft);
}
.stats-bar { display: flex; gap: 1px; background: var(--border); border-bottom: 1px solid var(--border); flex-shrink: 0; }
.stat-box  { flex: 1; padding: 10px 16px; background: #fff; }
.stat-lbl  { font: 500 11px var(--font-sans); color: var(--fg3); }
.stat-val  { font: 700 15px var(--font-mono); color: var(--fg1); margin-top: 2px; }

.flash-ok { background: #F0FDF4; border-bottom: 1px solid #BBF7D0; color: #16A34A; font: 500 12.5px var(--font-sans); padding: 10px 20px; flex-shrink: 0; }

.empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: var(--fg3); font: 400 13px var(--font-sans); }

.inv-scroll { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 14px; }

/* header card */
.inv-hd-card {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
  background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px;
}
.inv-hd-left { display: flex; flex-direction: column; gap: 4px; }
.inv-hd-top  { display: flex; align-items: center; gap: 8px; }
.inv-num     { font: 700 16px var(--font-mono); color: var(--fg1); }
.inv-hd-sub  { font: 400 12px var(--font-sans); color: var(--fg3); }
.inv-hd-actions { display: flex; gap: 6px; flex-shrink: 0; }

/* 2-col body */
.inv-body { display: flex; gap: 14px; align-items: flex-start; }
.right-col { width: 240px; flex-shrink: 0; display: flex; flex-direction: column; gap: 14px; }

/* summary */
.sum-row  { display: flex; align-items: center; justify-content: space-between; }
.sum-lbl  { font: 500 12px var(--font-sans); color: var(--fg3); }
.sum-val  { font: 600 13px var(--font-mono); }
.total-val { font: 800 22px var(--font-mono); color: var(--brand-green); }
.edit-link { margin-left: 6px; font: 500 11px var(--font-sans); color: var(--brand-green); text-decoration: underline; background: none; border: none; cursor: pointer; }

.paid-box { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px; text-align: center; }
.paid-box__title { font: 700 12px var(--font-sans); color: #16A34A; }
.paid-box__meta  { font: 500 11px var(--font-sans); color: var(--fg3); margin-top: 3px; }
.cancelled-box { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px; text-align: center; font: 700 12px var(--font-sans); color: #DC2626; }

/* remove btn */
.rm-btn {
  width: 24px; height: 24px; border-radius: 6px; border: 1px solid var(--border); background: #fff;
  color: var(--fg3); font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .12s;
}
.rm-btn:hover { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }

/* ── add-item drawer ── */
.add-drawer {
  position: fixed; top: 0; right: 0; bottom: 0; width: 420px;
  background: #fff; border-left: 1px solid var(--border);
  display: flex; flex-direction: column;
  box-shadow: -8px 0 32px rgba(0,0,0,.12);
  z-index: 200;
}
.add-drawer__hd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.add-drawer__body { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.add-drawer__ft { display: flex; gap: 8px; justify-content: flex-end; padding: 14px 20px; border-top: 1px solid var(--border); flex-shrink: 0; }

/* quick chips in drawer */
.qsec { display: flex; flex-direction: column; gap: 8px; }
.qsec__label { font: 600 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .05em; }
.qsec__grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.qchip {
  display: flex; flex-direction: column; align-items: flex-start; gap: 2px;
  padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border);
  background: #fff; cursor: pointer; text-align: left; transition: all .12s;
}
.qchip:hover, .qchip.active { background: var(--brand-green-light); border-color: var(--brand-green); }
.qchip__name  { font: 500 11.5px var(--font-sans); color: var(--fg1); }
.qchip__price { font: 600 11px var(--font-mono); color: var(--fg3); }
.qchip.active .qchip__price { color: var(--brand-green); }

/* live total in drawer */
.live-total {
  display: flex; align-items: center; justify-content: space-between;
  background: var(--brand-green-light); border: 1px solid #A7F3D0;
  border-radius: 10px; padding: 12px 16px;
}

/* payment modal */
.pay-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
.pay-btn {
  font: 600 12px var(--font-sans); padding: 10px; border-radius: 8px;
  border: 2px solid var(--border); background: #fff; color: var(--fg2); cursor: pointer; transition: all .12s; text-align: center;
}
.pay-btn.active, .pay-btn:hover { border-color: var(--brand-green); background: var(--brand-green-light); color: var(--brand-green); }

/* ── modal backdrop & modal ── */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 300; padding: 16px;
}
.modal {
  background: #fff; border-radius: 14px;
  max-width: 100%; max-height: 90vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px 12px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal__title { font: 700 15px var(--font-sans); color: var(--fg1); flex: 1; }
.modal__close {
  width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border);
  background: #fff; color: var(--fg3); font-size: 12px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal__close:hover { background: var(--bg-soft); }
.modal__body   { padding: 16px 20px; }
.modal__footer { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 20px; border-top: 1px solid var(--border); }

/* ── drawer backdrop ── */
.drawer-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.3);
  z-index: 200;
}

/* patient dropdown */
.pdrop {
  position: absolute; top: 100%; left: 0; right: 0; background: #fff;
  border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.10);
  z-index: 50; overflow: hidden; margin-top: 2px;
}
.pdrop__item {
  display: flex; flex-direction: column; padding: 8px 12px; background: none; border: none;
  width: 100%; text-align: left; cursor: pointer; border-bottom: 1px solid var(--border); gap: 2px;
}
.pdrop__item:last-child { border-bottom: none; }
.pdrop__item:hover { background: var(--brand-green-light); }
.field__err { font: 400 11px var(--font-sans); color: #DC2626; margin-top: 2px; }
</style>
