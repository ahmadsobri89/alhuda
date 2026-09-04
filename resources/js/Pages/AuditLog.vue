<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  logs:     { type: Object, default: () => ({ data: [], links: [] }) },
  logNames: { type: Array,  default: () => [] },
  filters:  { type: Object, default: () => ({}) },
})

const PER_PAGE_OPTIONS = [20, 50, 100]
const perPage = ref(props.filters?.per_page ?? 20)
const user    = ref(props.filters?.user ?? '')
const event   = ref(props.filters?.event ?? '')
const logName = ref(props.filters?.log_name ?? '')
const from    = ref(props.filters?.from ?? '')
const to      = ref(props.filters?.to ?? '')

const eventTone = { created: 'green', updated: 'yellow', deleted: 'red' }

function query(extra = {}) {
  return {
    per_page: perPage.value !== 20 ? perPage.value : undefined,
    user: user.value || undefined,
    event: event.value || undefined,
    log_name: logName.value || undefined,
    from: from.value || undefined,
    to: to.value || undefined,
    ...extra,
  }
}
function applyFilters() {
  router.get(route('audit-log'), query(), { preserveState: true, replace: true })
}
function resetFilters() {
  user.value = ''; event.value = ''; logName.value = ''; from.value = ''; to.value = ''
  router.get(route('audit-log'), {}, { preserveState: true, replace: true })
}
function goToPage(url) {
  if (url) router.get(url, query(), { preserveState: true, preserveScroll: true })
}

const expanded = ref(null)
function toggle(id) {
  expanded.value = expanded.value === id ? null : id
}
</script>

<template>
  <div class="audit-root">
    <div class="card table-card">
      <div class="card__header">
        <h3 class="card__title">Log Audit</h3>
        <p class="card__sub" style="margin-left:auto">Jejak semua perubahan (cipta/kemaskini/padam) dalam sistem</p>
      </div>

      <div class="filter-bar">
        <input v-model="user" class="input" placeholder="Cari pengguna..." @keyup.enter="applyFilters" />
        <select v-model="event" class="input">
          <option value="">Semua tindakan</option>
          <option value="created">Cipta</option>
          <option value="updated">Kemaskini</option>
          <option value="deleted">Padam</option>
        </select>
        <select v-model="logName" class="input">
          <option value="">Semua modul</option>
          <option v-for="n in logNames" :key="n" :value="n">{{ n }}</option>
        </select>
        <input v-model="from" type="date" class="input" />
        <input v-model="to" type="date" class="input" />
        <button class="btn-apply" @click="applyFilters">Tapis</button>
        <button class="btn-reset" @click="resetFilters">Reset</button>
      </div>

      <div class="table-scroll">
        <div class="table__head" style="grid-template-columns:150px 160px 100px 140px 90px 1fr">
          <div>Masa</div><div>Pengguna</div><div>Tindakan</div><div>Modul</div><div>ID</div><div>Butiran</div>
        </div>
        <template v-for="r in logs.data" :key="r.id">
          <div
            class="table__row"
            style="grid-template-columns:150px 160px 100px 140px 90px 1fr;cursor:pointer"
            @click="toggle(r.id)"
          >
            <div class="mono" style="font:500 11.5px var(--font-mono);color:var(--fg3)">{{ r.ts }}</div>
            <div style="font:600 12.5px var(--font-sans)">{{ r.user }}</div>
            <div><Badge :tone="eventTone[r.event] ?? 'neutral'">{{ r.event }}</Badge></div>
            <div style="font:500 12px var(--font-sans);color:var(--fg2)">{{ r.log_name }}</div>
            <div class="mono" style="font:500 11.5px var(--font-mono);color:var(--fg3)">#{{ r.subject_id }}</div>
            <div style="font:400 12px var(--font-sans);color:var(--fg2)">{{ r.description }}</div>
          </div>
          <div v-if="expanded === r.id" class="diff-panel">
            <div class="diff-col">
              <div class="diff-title">Sebelum</div>
              <pre class="diff-json">{{ JSON.stringify(r.old, null, 2) }}</pre>
            </div>
            <div class="diff-col">
              <div class="diff-title">Selepas</div>
              <pre class="diff-json">{{ JSON.stringify(r.attributes, null, 2) }}</pre>
            </div>
          </div>
        </template>
        <div v-if="!logs.data?.length" style="padding:24px;text-align:center;color:var(--fg3);font:500 13px var(--font-sans)">
          Tiada log dijumpai.
        </div>
      </div>
    </div>

    <div v-if="logs.data?.length" class="pagination">
      <div class="pagination__info">
        Papar
        <select v-model.number="perPage" @change="applyFilters" class="per-page-select">
          <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
        </select>
        / muka surat · {{ logs.from }}–{{ logs.to }} dari {{ logs.total }}
      </div>
      <div v-if="logs.last_page > 1" class="pagination__pages">
        <button
          v-for="link in logs.links" :key="link.label"
          :disabled="!link.url"
          :class="['page-btn', link.active ? 'active':'']"
          @click="goToPage(link.url)"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.audit-root {
  padding: 20px 24px;
  height: calc(100vh - 56px);
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: var(--bg-soft);
}
.filter-bar {
  display: flex;
  gap: 8px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  align-items: center;
}
.input {
  font: 500 12px var(--font-sans);
  padding: 6px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: #fff;
  color: var(--fg1);
}
.btn-apply, .btn-reset {
  font: 600 12px var(--font-sans);
  padding: 6px 14px;
  border-radius: 6px;
  border: 1px solid var(--border);
  cursor: pointer;
}
.btn-apply { background: var(--brand-green); color: #fff; border-color: var(--brand-green); }
.btn-reset { background: #fff; color: var(--fg2); }
.diff-panel {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 10px 16px 16px;
  background: var(--bg-soft);
  border-bottom: 1px solid var(--border);
}
.diff-title { font: 700 11px var(--font-sans); color: var(--fg3); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.diff-json {
  font: 500 11px var(--font-mono);
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 8px 10px;
  max-height: 200px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

@media (max-width: 640px) {
  .diff-panel { grid-template-columns: 1fr; }
}
</style>
