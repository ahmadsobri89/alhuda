<script setup>
defineProps({ variant: { default: 'secondary' }, size: String })
</script>

<template>
  <button :class="['btn', 'btn--' + variant, size === 'sm' ? 'btn--sm' : '']">
    <slot />
  </button>
</template>
