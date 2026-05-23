<script setup lang="ts">
import type { ElementConfig } from './types'

const props = defineProps<{
  id: string
  config: ElementConfig
}>()

const emit = defineEmits<{
  (e: 'update', config: ElementConfig): void
}>()

function set<K extends keyof ElementConfig>(key: K, val: ElementConfig[K]) {
  emit('update', { ...props.config, [key]: val })
}

const spanWidthMap: Record<1 | 2 | 3, number> = { 1: 30, 2: 63, 3: 96 }

function setSpan(span: 1 | 2 | 3) {
  emit('update', { ...props.config, columnSpan: span, w: spanWidthMap[span] })
}
</script>

<template>
  <div class="mt-1 ml-6 p-2 bg-white border border-gray-100 rounded-md space-y-2 text-xs">
    <!-- Column Span -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Lajur</span>
      <div class="flex gap-1">
        <button
          v-for="span in [1, 2, 3]"
          :key="span"
          @click="setSpan(span as 1 | 2 | 3)"
          :class="config.columnSpan === span
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="w-7 h-7 rounded-full text-xs font-medium transition-colors"
        >{{ span }}</button>
      </div>
    </div>

    <!-- Border toggle -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Sempadan</span>
      <button
        @click="set('border', !config.border)"
        :class="config.border ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600'"
        class="px-2 py-1 rounded text-xs transition-colors"
      >{{ config.border ? 'ON' : 'OFF' }}</button>
    </div>

    <!-- Border options (when ON) -->
    <template v-if="config.border">
      <div class="flex items-center gap-1">
        <span class="w-20 text-gray-500 shrink-0">Lebar</span>
        <div class="flex gap-1">
          <button
            v-for="w in ['1', '2', '4', '8']"
            :key="w"
            @click="set('borderWidth', w as '1' | '2' | '4' | '8')"
            :class="config.borderWidth === w
              ? 'bg-gray-800 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="w-7 h-7 rounded text-xs font-medium transition-colors"
          >{{ w }}</button>
        </div>
      </div>

      <div class="flex items-center gap-1">
        <span class="w-20 text-gray-500 shrink-0">Gaya</span>
        <div class="flex gap-1">
          <button
            @click="set('borderStyle', 'solid')"
            :class="config.borderStyle === 'solid' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-2 h-7 rounded text-xs transition-colors"
          >─</button>
          <button
            @click="set('borderStyle', 'dashed')"
            :class="config.borderStyle === 'dashed' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-2 h-7 rounded text-xs transition-colors"
          >- -</button>
          <button
            @click="set('borderStyle', 'dotted')"
            :class="config.borderStyle === 'dotted' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-2 h-7 rounded text-xs transition-colors"
          >···</button>
        </div>
      </div>

      <div class="flex items-center gap-1">
        <span class="w-20 text-gray-500 shrink-0">Warna</span>
        <div class="flex gap-1">
          <button
            v-for="col in ['#ffffff', '#000000', '#ef4444', '#3b82f6']"
            :key="col"
            @click="set('borderColor', col)"
            :style="{ backgroundColor: col }"
            :class="config.borderColor === col ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
            class="w-6 h-6 rounded border border-gray-300 transition-all"
          />
        </div>
      </div>
    </template>

    <!-- Font size -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Saiz (px)</span>
      <div class="flex items-center gap-1">
        <button
          @click="set('fontSize', Math.max(4, config.fontSize - 1))"
          class="w-7 h-7 rounded bg-gray-100 text-gray-600 hover:bg-gray-200 text-sm font-bold transition-colors"
        >−</button>
        <input
          type="number"
          :value="config.fontSize"
          @change="set('fontSize', Math.min(100, Math.max(4, Number(($event.target as HTMLInputElement).value))))"
          class="w-12 h-7 text-center text-xs border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-gray-400"
          min="4"
          max="100"
        />
        <button
          @click="set('fontSize', Math.min(100, config.fontSize + 1))"
          class="w-7 h-7 rounded bg-gray-100 text-gray-600 hover:bg-gray-200 text-sm font-bold transition-colors"
        >+</button>
      </div>
    </div>

    <!-- Font weight -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Tebal</span>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['font-normal','—'], ['font-medium','M'], ['font-semibold','S'], ['font-bold','B']]"
          :key="val"
          @click="set('fontWeight', val as ElementConfig['fontWeight'])"
          :class="config.fontWeight === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          :style="{ fontWeight: val.replace('font-', '') }"
          class="flex-1 h-7 rounded text-xs transition-colors"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Text color -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Warna Teks</span>
      <div class="flex gap-1 items-center flex-wrap">
        <button
          v-for="col in ['#000000', '#ffffff', '#374151', '#6b7280', '#ef4444', '#3b82f6', '#16a34a', '#f59e0b']"
          :key="col"
          @click="set('textColor', col)"
          :style="{ backgroundColor: col }"
          :class="config.textColor === col ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
          class="w-6 h-6 rounded border border-gray-300 transition-all"
        />
        <label class="w-6 h-6 rounded border border-gray-300 overflow-hidden cursor-pointer relative" title="Pilih warna">
          <input
            type="color"
            :value="config.textColor"
            @input="set('textColor', ($event.target as HTMLInputElement).value)"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
          />
          <span class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs">+</span>
        </label>
      </div>
    </div>

    <!-- Background color -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Latar</span>
      <div class="flex gap-1 items-center flex-wrap">
        <!-- Transparent swatch -->
        <button
          @click="set('backgroundColor', 'transparent')"
          :class="config.backgroundColor === 'transparent' ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
          class="w-6 h-6 rounded border border-gray-300 relative overflow-hidden transition-all"
          title="Telus"
        >
          <span class="absolute inset-0" style="background: repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 0 0 / 8px 8px;" />
          <span v-if="config.backgroundColor === 'transparent'" class="absolute inset-0 flex items-center justify-center text-gray-600 text-xs font-bold">✓</span>
        </button>
        <!-- Colour swatches -->
        <button
          v-for="col in ['#000000', '#ffffff', '#1a2e1a', '#0d1b2a', '#2d2d2d', '#f0ede8', '#ef4444', '#3b82f6', '#16a34a', '#f59e0b']"
          :key="col"
          @click="set('backgroundColor', col)"
          :style="{ backgroundColor: col }"
          :class="config.backgroundColor === col ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
          class="w-6 h-6 rounded border border-gray-300 transition-all"
        />
        <!-- Custom colour picker -->
        <label class="w-6 h-6 rounded border border-gray-300 overflow-hidden cursor-pointer relative transition-all" title="Pilih warna">
          <input
            type="color"
            :value="config.backgroundColor === 'transparent' ? '#ffffff' : config.backgroundColor"
            @input="set('backgroundColor', ($event.target as HTMLInputElement).value)"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
          />
          <span class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs">+</span>
        </label>
      </div>
    </div>

    <!-- Corner radius -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Sudut</span>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['rounded-none','□'], ['rounded-md','▢'], ['rounded-xl','◻'], ['rounded-full','◯']]"
          :key="val"
          @click="set('cornerRadius', val as ElementConfig['cornerRadius'])"
          :class="config.cornerRadius === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="w-7 h-7 rounded text-sm transition-colors"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Padding -->
    <div class="flex items-center gap-1">
      <span class="w-20 text-gray-500 shrink-0">Padding</span>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['p-0','0'], ['p-1','S'], ['p-2','M'], ['p-3','L']]"
          :key="val"
          @click="set('padding', val as ElementConfig['padding'])"
          :class="config.padding === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="w-7 h-7 rounded text-xs font-medium transition-colors"
        >{{ label }}</button>
      </div>
    </div>
  </div>
</template>
