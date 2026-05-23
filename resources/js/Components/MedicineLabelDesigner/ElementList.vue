<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Sortable } from '@shopify/draggable'
import type { LabelConfig, ElementConfig } from './types'
import ElementAccordion from './ElementAccordion.vue'

const ELEMENTS = [
  { id: 'logo',        label: 'Logo Klinik' },
  { id: 'medicine',    label: 'Nama Ubat / Kegunaan' },
  { id: 'dosage',      label: 'Dos' },
  { id: 'frequency',   label: 'Kekerapan Makan Ubat' },
  { id: 'food_timing', label: 'Masa Makan' },
  { id: 'patient',     label: 'Nama Pesakit' },
  { id: 'date',        label: 'Tarikh' },
  { id: 'notes',       label: 'Catatan' },
  { id: 'address',     label: 'Alamat' },
  { id: 'phone',       label: 'No Telefon' },
  { id: 'email',       label: 'Emel' },
  { id: 'free_text',   label: 'Teks Bebas' },
]

const props = defineProps<{
  config: LabelConfig
  selectedId: string | null
}>()

const emit = defineEmits<{
  (e: 'update', config: LabelConfig): void
  (e: 'select', id: string | null): void
  (e: 'delete-free-text', id: string): void
  (e: 'delete-shape', id: string): void
}>()

const listRef = ref<HTMLElement | null>(null)
const openAccordion = ref<string | null>(null)
const itemRefs = ref<Record<string, HTMLElement>>({})
let sortable: Sortable | null = null

// When preview selects an element, open its accordion + scroll into view
watch(() => props.selectedId, async (id) => {
  if (!id) return
  openAccordion.value = id
  await nextTick()
  itemRefs.value[id]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
})

function isFreeText(id: string): boolean {
  return id === 'free_text' || id.startsWith('text_')
}

function isShape(id: string): boolean {
  return id.startsWith('shape_')
}

function getElementLabel(id: string): string {
  const found = ELEMENTS.find(el => el.id === id)
  if (found) return found.label
  if (isFreeText(id)) {
    const preview = props.config.freeTexts?.[id]
    return preview ? `"${preview.slice(0, 20)}${preview.length > 20 ? '…' : ''}"` : 'Teks Bebas'
  }
  if (isShape(id)) {
    const shape = props.config.elements[id]?.shape
    return shape === 'circle' ? 'Bulatan' : 'Segiempat'
  }
  return id
}

function toggleVisible(id: string) {
  const elements = { ...props.config.elements, [id]: { ...props.config.elements[id], visible: !props.config.elements[id].visible } }
  emit('update', { ...props.config, elements })
}

function toggleAccordion(id: string) {
  const next = openAccordion.value === id ? null : id
  openAccordion.value = next
  emit('select', next)
}

function updateElement(id: string, elConfig: ElementConfig) {
  const elements = { ...props.config.elements, [id]: elConfig }
  emit('update', { ...props.config, elements })
}

onMounted(() => {
  if (!listRef.value) return
  sortable = new Sortable(listRef.value, {
    draggable: '.drag-item',
    handle: '.drag-handle',
  })
  sortable.on('sortable:stop', (evt: any) => {
    const { oldIndex, newIndex } = evt
    if (oldIndex === newIndex) return
    const order = [...props.config.elementOrder]
    const [moved] = order.splice(oldIndex, 1)
    order.splice(newIndex, 0, moved)
    emit('update', { ...props.config, elementOrder: order })
  })
})

onUnmounted(() => sortable?.destroy())
</script>

<template>
  <div ref="listRef" class="space-y-1">
    <div
      v-for="id in config.elementOrder"
      :key="id"
      :ref="el => { if (el) itemRefs[id] = el as HTMLElement }"
      class="drag-item"
    >
      <div
        class="flex items-center gap-2 p-2 rounded border transition-colors"
        :class="selectedId === id
          ? 'bg-blue-50 border-blue-300'
          : 'bg-gray-50 border-gray-200'"
      >
        <span class="drag-handle text-gray-400 cursor-grab select-none text-base leading-none">⠿</span>
        <span
          class="flex-1 text-sm"
          :class="selectedId === id ? 'text-blue-700 font-semibold' : 'text-gray-700'"
        >{{ getElementLabel(id) }}</span>
        <button
          @click="toggleVisible(id)"
          class="text-sm w-6 h-6 flex items-center justify-center rounded hover:bg-gray-200 transition-colors"
          :title="config.elements[id].visible ? 'Sembunyikan' : 'Tunjukkan'"
        >
          <span v-if="config.elements[id].visible">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
          <span v-else>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </span>
        </button>
        <!-- Delete button — free text or shape elements -->
        <button
          v-if="isFreeText(id)"
          @click="emit('delete-free-text', id)"
          class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
          title="Padam"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </button>
        <button
          v-if="isShape(id)"
          @click="emit('delete-shape', id)"
          class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
          title="Padam"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </button>

        <button
          @click="toggleAccordion(id)"
          :class="openAccordion === id ? 'bg-gray-200 text-gray-700' : 'text-gray-400 hover:bg-gray-200'"
          class="w-6 h-6 flex items-center justify-center rounded transition-colors"
          title="Tetapan"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
        </button>
      </div>

      <ElementAccordion
        v-if="openAccordion === id"
        :id="id"
        :config="config.elements[id]"
        @update="updateElement(id, $event)"
      />
    </div>
  </div>
</template>
