<script setup lang="ts">
import type { LabelConfig } from './types'
import ElementList from './ElementList.vue'
import GlobalSettings from './GlobalSettings.vue'

const props = defineProps<{
  config: LabelConfig
  selectedId: string | null
}>()

const emit = defineEmits<{
  (e: 'update', config: LabelConfig): void
  (e: 'save'): void
  (e: 'select', id: string | null): void
  (e: 'add-free-text'): void
  (e: 'delete-free-text', id: string): void
  (e: 'add-shape', shape: 'circle' | 'rect'): void
  (e: 'delete-shape', id: string): void
}>()

const BG_SWATCHES = ['transparent', '#000000', '#ffffff', '#1a2e1a', '#0d1b2a', '#2d2d2d', '#f0ede8']

function set<K extends keyof LabelConfig>(key: K, val: LabelConfig[K]) {
  emit('update', { ...props.config, [key]: val })
}
</script>

<template>
  <div class="w-80 h-full flex flex-col bg-white border-r border-gray-200 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
      <h2 class="text-sm font-semibold text-gray-800">Tetapan Label</h2>
      <button
        @click="emit('save')"
        class="px-3 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-md hover:bg-gray-700 transition-colors"
      >Simpan</button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-5">

      <!-- Section 1: Background & Saiz -->
      <section>
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Latar & Saiz</h3>
        <div class="space-y-3">
          <!-- Background color swatches -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">Warna Latar</label>
            <div class="flex gap-1.5 flex-wrap items-center">
              <template v-for="col in BG_SWATCHES" :key="col">
                <!-- Transparent swatch -->
                <button
                  v-if="col === 'transparent'"
                  @click="set('backgroundColor', 'transparent')"
                  :class="config.backgroundColor === 'transparent' ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
                  class="w-7 h-7 rounded border border-gray-300 relative overflow-hidden transition-all"
                  title="Telus"
                >
                  <span class="absolute inset-0" style="background: repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 0 0 / 10px 10px;" />
                  <span v-if="config.backgroundColor === 'transparent'" class="absolute inset-0 flex items-center justify-center text-gray-600 text-xs font-bold">✓</span>
                </button>
                <!-- Solid colour swatch -->
                <button
                  v-else
                  @click="set('backgroundColor', col)"
                  :style="{ backgroundColor: col }"
                  :class="config.backgroundColor === col ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
                  class="w-7 h-7 rounded border border-gray-300 transition-all"
                />
              </template>
              <input
                type="color"
                :value="config.backgroundColor === 'transparent' ? '#000000' : config.backgroundColor"
                @input="set('backgroundColor', ($event.target as HTMLInputElement).value)"
                class="w-7 h-7 rounded border border-gray-300 cursor-pointer p-0"
                title="Pilih warna lain"
              />
            </div>
          </div>

          <!-- Width & Height -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Lebar (mm)</label>
              <input
                type="number"
                :value="config.labelWidth"
                @input="set('labelWidth', Number(($event.target as HTMLInputElement).value))"
                class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400"
                min="40"
                max="300"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Tinggi (mm)</label>
              <input
                type="number"
                :value="config.labelHeight"
                @input="set('labelHeight', Number(($event.target as HTMLInputElement).value))"
                class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400"
                min="20"
                max="300"
              />
            </div>
          </div>

          <!-- Font size -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">Saiz Fon</label>
            <div class="flex gap-1">
              <button
                v-for="[val, label] in [['text-xs','XS'], ['text-sm','SM'], ['text-base','Base']]"
                :key="val"
                @click="set('globalFontSize', val as LabelConfig['globalFontSize'])"
                :class="config.globalFontSize === val
                  ? 'bg-gray-800 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="flex-1 py-1.5 rounded text-xs font-medium transition-colors"
              >{{ label }}</button>
            </div>
          </div>
        </div>
      </section>

      <hr class="border-gray-100" />

      <!-- Section 2: Susunan & Keterlihatan -->
      <section>
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Susunan & Keterlihatan</h3>
          <div class="flex gap-1">
            <button
              @click="emit('add-free-text')"
              class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
              title="Tambah blok teks bebas"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Teks
            </button>
            <button
              @click="emit('add-shape', 'rect')"
              class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
              title="Tambah segiempat"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="1"/></svg>
              □
            </button>
            <button
              @click="emit('add-shape', 'circle')"
              class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
              title="Tambah bulatan"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/></svg>
              ○
            </button>
          </div>
        </div>
        <ElementList
          :config="config"
          :selected-id="selectedId"
          @update="emit('update', $event)"
          @select="emit('select', $event)"
          @delete-free-text="emit('delete-free-text', $event)"
          @delete-shape="emit('delete-shape', $event)"
        />
      </section>

      <hr class="border-gray-100" />

      <!-- Section 3: Global Settings -->
      <section>
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tetapan Global</h3>
        <GlobalSettings
          :config="config"
          @update="emit('update', $event)"
        />
      </section>

    </div>
  </div>
</template>
