<script setup>
const props = defineProps({
  src: {
    type: String,
    default: "/assets/images/logo.png",
  },
});
</script>
<template>
  <img :src="src" alt="logo" />
</template>
