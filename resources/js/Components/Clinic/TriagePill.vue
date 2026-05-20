<script setup>
import { computed } from 'vue'
const props = defineProps({ level: Number })
const label = computed(() => ['', 'Emergency', 'Urgent', 'Semi', 'Standard', 'Non-urgent'][props.level])
</script>

<template>
  <span :class="['triage-pill', 'triage-' + level]">L{{ level }} · {{ label }}</span>
</template>
