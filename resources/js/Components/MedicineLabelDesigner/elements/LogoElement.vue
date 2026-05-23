<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
  size?: 'sm' | 'md' | 'lg'
}>()

const sizeClass = {
  sm: 'h-8',
  md: 'h-12',
  lg: 'h-16',
}
</script>

<template>
  <img
    v-if="data.logo"
    :src="data.logo"
    :class="sizeClass[size ?? 'md']"
    class="object-contain"
    alt="Logo Klinik"
  />
</template>
