<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <span v-if="data.frequency">{{ data.frequency }}</span>
</template>
