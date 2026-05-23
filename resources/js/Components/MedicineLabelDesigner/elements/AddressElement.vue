<script setup lang="ts">
import type { LabelData, ElementConfig } from '../types'

const props = defineProps<{
  data: LabelData
  config: ElementConfig
}>()

const hasAddress = (d: LabelData) =>
  d.address_line1 || d.address_line2 || d.postcode || d.city || d.state
</script>

<template>
  <div v-if="hasAddress(data)" class="leading-snug">
    <div v-if="data.address_line1">{{ data.address_line1 }}</div>
    <div v-if="data.address_line2">{{ data.address_line2 }}</div>
    <div v-if="data.postcode || data.city || data.state">
      <template v-if="data.postcode">{{ data.postcode }} </template>
      <template v-if="data.city">{{ data.city }}</template>
      <template v-if="data.state">, {{ data.state }}</template>
    </div>
  </div>
</template>
