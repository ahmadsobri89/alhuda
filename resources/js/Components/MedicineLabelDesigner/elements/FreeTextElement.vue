<script setup lang="ts">
import { ref, nextTick } from 'vue'
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void
}>()

const editing  = ref(false)
const inputRef = ref<HTMLTextAreaElement | null>(null)

async function startEdit(e: MouseEvent) {
  e.stopPropagation()
  editing.value = true
  await nextTick()
  inputRef.value?.focus()
  inputRef.value?.select()
}

function stopEdit() {
  editing.value = false
}
</script>

<template>
  <div class="relative w-full" @dblclick="startEdit">
    <textarea
      v-if="editing"
      ref="inputRef"
      :value="modelValue"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      @blur="stopEdit"
      @keydown.escape="stopEdit"
      @pointerdown.stop
      class="w-full bg-transparent resize-none outline-none ring-1 ring-blue-400 rounded min-h-[1.2em]"
      rows="2"
    />
    <span v-else class="whitespace-pre-wrap break-words block min-h-[1em]">{{ modelValue || '…' }}</span>
  </div>
</template>
