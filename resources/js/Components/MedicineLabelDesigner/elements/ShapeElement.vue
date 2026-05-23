<script setup lang="ts">
import type { ElementConfig } from '../types'

defineProps<{
  config: ElementConfig
}>()
</script>

<template>
  <div
    class="w-full h-full"
    :class="config.cornerRadius"
    :style="{
      backgroundColor: config.backgroundColor !== 'transparent' ? config.backgroundColor : 'transparent',
      border: config.border ? `${config.borderWidth}px ${config.borderStyle} ${config.borderColor}` : 'none',
    }"
  />
</template>
