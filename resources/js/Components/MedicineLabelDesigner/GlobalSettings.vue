<script setup lang="ts">
import type { LabelConfig } from './types'

const props = defineProps<{
  config: LabelConfig
}>()

const emit = defineEmits<{
  (e: 'update', config: LabelConfig): void
}>()

function set<K extends keyof LabelConfig>(key: K, val: LabelConfig[K]) {
  emit('update', { ...props.config, [key]: val })
}
</script>

<template>
  <div class="space-y-3">
    <!-- Global corners -->
    <div>
      <label class="block text-xs text-gray-500 mb-1">Sudut Label</label>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['rounded-none','□'], ['rounded-md','▢'], ['rounded-xl','◻']]"
          :key="val"
          @click="set('globalCorners', val as LabelConfig['globalCorners'])"
          :class="config.globalCorners === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="flex-1 py-1.5 rounded text-sm font-medium transition-colors"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Column gap -->
    <div>
      <label class="block text-xs text-gray-500 mb-1">Jarak Lajur</label>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['gap-1','S'], ['gap-2','M'], ['gap-3','L']]"
          :key="val"
          @click="set('columnGap', val as LabelConfig['columnGap'])"
          :class="config.columnGap === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="flex-1 py-1.5 rounded text-sm font-medium transition-colors"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Label padding -->
    <div>
      <label class="block text-xs text-gray-500 mb-1">Padding Label</label>
      <div class="flex gap-1">
        <button
          v-for="[val, label] in [['p-2','S'], ['p-3','M'], ['p-4','L']]"
          :key="val"
          @click="set('padding', val as LabelConfig['padding'])"
          :class="config.padding === val
            ? 'bg-gray-800 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          class="flex-1 py-1.5 rounded text-sm font-medium transition-colors"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Show divider -->
    <div class="flex items-center justify-between">
      <span class="text-xs text-gray-500">Tunjuk Pemisah</span>
      <button
        @click="set('showDivider', !config.showDivider)"
        :class="config.showDivider
          ? 'bg-gray-800'
          : 'bg-gray-300'"
        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
      >
        <span
          :class="config.showDivider ? 'translate-x-5' : 'translate-x-1'"
          class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
        />
      </button>
    </div>
  </div>
</template>
