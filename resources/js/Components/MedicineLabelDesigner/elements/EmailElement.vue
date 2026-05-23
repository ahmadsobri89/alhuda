<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <div v-if="data.email" class="flex items-center gap-1">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
      <polyline points="22,6 12,13 2,6"/>
    </svg>
    <span>{{ data.email }}</span>
  </div>
</template>
