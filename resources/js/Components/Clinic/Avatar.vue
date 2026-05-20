<script setup>
import { computed } from 'vue'
const props = defineProps({ name: String, size: String })
const initials = computed(() =>
  (props.name || '').split(' ').filter(Boolean).slice(0, 2).map(s => s[0]).join('').toUpperCase()
)
const cls = computed(() => ['avatar', props.size === 'lg' ? 'lg' : props.size === 'sm' ? 'sm' : ''].join(' '))
</script>

<template>
  <div :class="cls">{{ initials }}</div>
</template>
