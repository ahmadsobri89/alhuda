<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import type { LabelConfig, LabelData, ElementConfig } from './types'
import { defaultConfig } from './defaultConfig'
import SettingsPanel from './SettingsPanel.vue'
import LabelPreview from './LabelPreview.vue'

const props = defineProps<{
  data: LabelData
  defaultConfig?: LabelConfig
  clinicId?: number | string
}>()

const emit = defineEmits<{
  (e: 'config-change', config: LabelConfig): void
  (e: 'save', config: LabelConfig): void
}>()

function deepClone<T>(obj: T): T {
  return JSON.parse(JSON.stringify(obj))
}

const config     = ref<LabelConfig>(deepClone(props.defaultConfig ?? defaultConfig))
const selectedId = ref<string | null>(null)

watch(config, (val) => emit('config-change', val), { deep: true })

function onUpdate(updated: LabelConfig)       { config.value = updated }
function onConfigUpdate(updated: LabelConfig) { config.value = updated }

function onFreeTextUpdate(id: string, val: string) {
  config.value = {
    ...config.value,
    freeTexts: { ...config.value.freeTexts, [id]: val },
  }
}

function addFreeText() {
  const id = `text_${Date.now()}`
  const newEl: ElementConfig = {
    visible: true, x: 5, y: 5, w: 30, h: 0,
    columnSpan: 1, fontSize: 12, fontWeight: 'font-normal',
    textColor: '#000000', backgroundColor: 'transparent',
    border: false, borderWidth: '1', borderStyle: 'solid', borderColor: '#000000',
    cornerRadius: 'rounded-none', padding: 'p-1',
  }
  config.value = {
    ...config.value,
    elementOrder: [...config.value.elementOrder, id],
    elements:     { ...config.value.elements, [id]: newEl },
    freeTexts:    { ...config.value.freeTexts, [id]: 'Teks baru' },
  }
  selectedId.value = id
}

function addShape(shape: 'circle' | 'rect') {
  const id = `shape_${Date.now()}`
  const newEl: ElementConfig = {
    visible: true, x: 5, y: 5, w: 20, h: 20, shape,
    columnSpan: 1, fontSize: 12, fontWeight: 'font-normal',
    textColor: '#000000', backgroundColor: '#cccccc',
    border: false, borderWidth: '1', borderStyle: 'solid', borderColor: '#000000',
    cornerRadius: shape === 'circle' ? 'rounded-full' : 'rounded-none', padding: 'p-1',
  }
  config.value = {
    ...config.value,
    elementOrder: [...config.value.elementOrder, id],
    elements:     { ...config.value.elements, [id]: newEl },
  }
  selectedId.value = id
}

function deleteShape(id: string) {
  const elements = { ...config.value.elements }
  delete elements[id]
  config.value = {
    ...config.value,
    elementOrder: config.value.elementOrder.filter(i => i !== id),
    elements,
  }
  if (selectedId.value === id) selectedId.value = null
}

function deleteFreeText(id: string) {
  const elements = { ...config.value.elements }
  const freeTexts = { ...config.value.freeTexts }
  delete elements[id]
  delete freeTexts[id]
  config.value = {
    ...config.value,
    elementOrder: config.value.elementOrder.filter(i => i !== id),
    elements,
    freeTexts,
  }
  if (selectedId.value === id) selectedId.value = null
}

async function loadTemplate() {
  if (!props.clinicId) return
  try {
    const res = await fetch(`/api/label-templates/${props.clinicId}`)
    if (res.ok) config.value = await res.json()
  } catch { /* silently ignore */ }
}

async function saveTemplate() {
  try {
    await fetch('/api/label-templates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ clinic_id: props.clinicId, config: config.value }),
    })
    emit('save', config.value)
  } catch { /* silently ignore */ }
}

onMounted(loadTemplate)
</script>

<template>
  <div class="flex h-full overflow-hidden">
    <SettingsPanel
      :config="config"
      :selected-id="selectedId"
      @update="onUpdate"
      @save="saveTemplate"
      @select="selectedId = $event"
      @add-free-text="addFreeText"
      @delete-free-text="deleteFreeText"
      @add-shape="addShape"
      @delete-shape="deleteShape"
    />
    <LabelPreview
      :config="config"
      :data="data"
      :selected-id="selectedId"
      @update:config="onConfigUpdate"
      @update:free-text="onFreeTextUpdate"
      @select="selectedId = $event"
    />
  </div>
</template>
