<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <span v-if="data.patient_name">Pesakit: {{ data.patient_name }}</span>
</template>
