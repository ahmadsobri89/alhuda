<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <span v-if="data.medicine_name || data.medicine_usage" class="font-semibold">
    <template v-if="data.medicine_name">{{ data.medicine_name }}</template>
    <template v-if="data.medicine_name && data.medicine_usage"> ({{ data.medicine_usage }})</template>
    <template v-else-if="data.medicine_usage">{{ data.medicine_usage }}</template>
  </span>
</template>
