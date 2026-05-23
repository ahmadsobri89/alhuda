<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()
</script>

<template>
  <p v-if="data.notes" class="whitespace-pre-wrap">{{ data.notes }}</p>
</template>
