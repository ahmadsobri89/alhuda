<script setup>
defineProps({ tone: { default: 'neutral' } })
</script>

<template>
  <span :class="['badge', 'badge--' + tone]"><slot /></span>
</template>
