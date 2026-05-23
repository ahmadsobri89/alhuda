<script setup lang="ts">
import { computed, ref } from 'vue'
import type { LabelConfig, LabelData, ElementConfig } from './types'
import { mmToPx } from './utils/labelUtils'

import LogoElement from './elements/LogoElement.vue'
import MedicineElement from './elements/MedicineElement.vue'
import DosageElement from './elements/DosageElement.vue'
import FrequencyElement from './elements/FrequencyElement.vue'
import FoodTimingElement from './elements/FoodTimingElement.vue'
import PatientElement from './elements/PatientElement.vue'
import DateElement from './elements/DateElement.vue'
import NotesElement from './elements/NotesElement.vue'
import AddressElement from './elements/AddressElement.vue'
import PhoneElement from './elements/PhoneElement.vue'
import EmailElement from './elements/EmailElement.vue'
import FreeTextElement from './elements/FreeTextElement.vue'
import ShapeElement from './elements/ShapeElement.vue'

const props = defineProps<{
  config: LabelConfig
  data: LabelData
  selectedId: string | null
}>()

const emit = defineEmits<{
  (e: 'update:config', config: LabelConfig): void
  (e: 'update:freeText', id: string, val: string): void
  (e: 'select', id: string | null): void
}>()

const elementComponents: Record<string, any> = {
  logo: LogoElement,
  medicine: MedicineElement,
  dosage: DosageElement,
  frequency: FrequencyElement,
  food_timing: FoodTimingElement,
  patient: PatientElement,
  date: DateElement,
  notes: NotesElement,
  address: AddressElement,
  phone: PhoneElement,
  email: EmailElement,
  free_text: FreeTextElement,
}

const containerRef = ref<HTMLElement | null>(null)
const labelRef     = ref<HTMLElement | null>(null)

const labelPxW = computed(() => mmToPx(props.config.labelWidth))
const labelPxH = computed(() => mmToPx(props.config.labelHeight))

const scale = computed(() => {
  if (!containerRef.value) return 1
  const cw = containerRef.value.clientWidth  - 48
  const ch = containerRef.value.clientHeight - 48
  return Math.min(cw / labelPxW.value, ch / labelPxH.value)
})

const CHECKER = 'repeating-conic-gradient(#d1d5db 0% 25%, #f9fafb 0% 50%) 0 0 / 16px 16px'

const labelStyle = computed(() => {
  const bg = props.config.backgroundColor
  return {
    width:           labelPxW.value + 'px',
    height:          labelPxH.value + 'px',
    backgroundColor: bg === 'transparent' ? undefined : bg,
    backgroundImage: bg === 'transparent' ? CHECKER : undefined,
    transform:       `scale(${scale.value})`,
    transformOrigin: 'center',
    position:        'relative' as const,
    flexShrink:      '0',
  }
})

// ── Drag / resize state ──────────────────────────────────────────────────────
type DragMode = 'move' | 'resize-e' | 'resize-s' | 'resize-se'

interface DragState {
  id: string
  mode: DragMode
  startClientX: number
  startClientY: number
  origX: number
  origY: number
  origW: number
  origH: number
}

let drag: DragState | null = null

function getScaledDelta(e: PointerEvent): { dx: number; dy: number } {
  if (!drag) return { dx: 0, dy: 0 }
  const rawDx = e.clientX - drag.startClientX
  const rawDy = e.clientY - drag.startClientY
  return {
    dx: (rawDx / scale.value / labelPxW.value) * 100,
    dy: (rawDy / scale.value / labelPxH.value) * 100,
  }
}

function onElementPointerDown(e: PointerEvent, id: string, mode: DragMode) {
  e.preventDefault()
  e.stopPropagation()
  emit('select', id)
  const el = props.config.elements[id]
  drag = {
    id,
    mode,
    startClientX: e.clientX,
    startClientY: e.clientY,
    origX: el.x,
    origY: el.y,
    origW: el.w,
    origH: el.h ?? 0,
  }
  ;(e.currentTarget as Element).setPointerCapture(e.pointerId)
}

function onLabelPointerMove(e: PointerEvent) {
  if (!drag) return
  const { dx, dy } = getScaledDelta(e)
  const { id, mode, origX, origY, origW } = drag
  const el = props.config.elements[id]

  let updated: ElementConfig

  if (mode === 'move') {
    updated = { ...el, x: Math.max(0, Math.min(95, origX + dx)), y: Math.max(0, Math.min(95, origY + dy)) }
  } else if (mode === 'resize-e') {
    updated = { ...el, w: Math.max(8, Math.min(100 - origX, origW + dx)) }
  } else if (mode === 'resize-s') {
    updated = { ...el, h: Math.max(5, Math.min(100 - origY, drag.origH + dy)) }
  } else if (mode === 'resize-se') {
    updated = { ...el, w: Math.max(8, Math.min(100 - origX, origW + dx)), h: Math.max(5, Math.min(100 - origY, drag.origH + dy)) }
  } else {
    updated = el
  }

  emit('update:config', {
    ...props.config,
    elements: { ...props.config.elements, [id]: updated },
  })
}

function onLabelPointerUp() {
  drag = null
}

function onLabelClick(e: MouseEvent) {
  if (e.target === labelRef.value) emit('select', null)
}

// ── Per-element inline styles ────────────────────────────────────────────────
function isShape(id: string): boolean {
  return id.startsWith('shape_')
}

function elementWrapStyle(id: string): Record<string, string> {
  const el = props.config.elements[id]
  const s: Record<string, string> = {
    position:    'absolute',
    left:        el.x + '%',
    top:         el.y + '%',
    width:       el.w + '%',
    color:       el.textColor ?? '#000000',
    fontSize:    (el.fontSize ?? 12) + 'px',
    boxSizing:   'border-box',
    touchAction: 'none',
  }
  if (isShape(id) && el.h) {
    s.height = el.h + '%'
  }
  if (!isShape(id)) {
    s.backgroundColor = el.backgroundColor ?? 'transparent'
    if (el.border) {
      s.borderWidth = el.borderWidth + 'px'
      s.borderStyle = el.borderStyle
      s.borderColor = el.borderColor
    }
  }
  return s
}
</script>

<template>
  <div
    ref="containerRef"
    class="flex-1 flex items-center justify-center bg-gray-100 overflow-hidden p-6 select-none"
  >
    <!-- Scaled label canvas -->
    <div
      ref="labelRef"
      :style="labelStyle"
      class="shadow-2xl overflow-visible"
      @click="onLabelClick"
      @pointermove="onLabelPointerMove"
      @pointerup="onLabelPointerUp"
      @pointercancel="onLabelPointerUp"
    >
      <template v-for="id in config.elementOrder" :key="id">
        <div
          v-if="config.elements[id]?.visible"
          :style="elementWrapStyle(id)"
          :class="[
            config.elements[id].padding,
            config.elements[id].cornerRadius,
            config.elements[id].fontWeight,
            'group',
            selectedId === id
              ? 'outline outline-2 outline-blue-400 outline-offset-0 z-10'
              : 'hover:outline hover:outline-1 hover:outline-white hover:outline-offset-0 hover:outline-dashed',
          ]"
          style="cursor: move;"
          @pointerdown="onElementPointerDown($event, id, 'move')"
        >
          <!-- Element content -->
          <ShapeElement
            v-if="id.startsWith('shape_')"
            :config="config.elements[id]"
          />
          <FreeTextElement
            v-else-if="id === 'free_text' || id.startsWith('text_')"
            :data="data"
            :config="config.elements[id]"
            :model-value="config.freeTexts?.[id] ?? ''"
            @update:model-value="emit('update:freeText', id, $event)"
          />
          <component
            v-else
            :is="elementComponents[id]"
            :data="data"
            :config="config.elements[id]"
          />

          <!-- Resize handle — East (right edge) -->
          <div
            v-if="selectedId === id"
            class="absolute top-0 right-0 w-2 h-full flex items-center justify-center cursor-ew-resize"
            style="touch-action: none;"
            @pointerdown.stop="onElementPointerDown($event, id, 'resize-e')"
          >
            <div class="w-1.5 h-6 bg-blue-400 rounded-full opacity-80" />
          </div>

          <!-- Resize handles — South + SE (shapes only) -->
          <template v-if="selectedId === id && id.startsWith('shape_')">
            <div
              class="absolute bottom-0 left-0 w-full h-2 flex items-center justify-center cursor-ns-resize"
              style="touch-action: none;"
              @pointerdown.stop="onElementPointerDown($event, id, 'resize-s')"
            >
              <div class="w-6 h-1.5 bg-blue-400 rounded-full opacity-80" />
            </div>
            <div
              class="absolute bottom-0 right-0 w-3 h-3 cursor-nwse-resize"
              style="touch-action: none;"
              @pointerdown.stop="onElementPointerDown($event, id, 'resize-se')"
            >
              <div class="w-2 h-2 bg-blue-400 rounded-sm opacity-80" />
            </div>
          </template>

          <!-- Selection corner indicators -->
          <template v-if="selectedId === id">
            <div class="absolute -top-1 -left-1 w-2 h-2 bg-blue-400 rounded-full" />
            <div class="absolute -top-1 -right-1 w-2 h-2 bg-blue-400 rounded-full" />
            <div class="absolute -bottom-1 -left-1 w-2 h-2 bg-blue-400 rounded-full" />
            <div class="absolute -bottom-1 -right-1 w-2 h-2 bg-blue-400 rounded-full" />
          </template>
        </div>
      </template>

      <!-- Deselect overlay (click empty space) -->
      <div
        v-if="selectedId"
        class="absolute inset-0 z-0"
        style="pointer-events: none;"
      />
    </div>

    <!-- Scale indicator -->
    <div class="absolute bottom-3 right-4 text-xs text-gray-400 font-mono">
      {{ config.labelWidth }}×{{ config.labelHeight }}mm
      <span v-if="scale < 1"> · {{ Math.round(scale * 100) }}%</span>
    </div>
  </div>
</template>
