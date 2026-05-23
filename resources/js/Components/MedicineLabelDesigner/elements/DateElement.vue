<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'
import { formatDateMalay } from '../utils/labelUtils'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <span v-if="data.dispense_date">{{ formatDateMalay(data.dispense_date) }}</span>
</template>
