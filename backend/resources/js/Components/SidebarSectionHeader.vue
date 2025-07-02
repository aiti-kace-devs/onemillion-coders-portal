<template>
  <div class="lg:group-[.sidebar-collapsed]/container:hidden lg:block mb-2">
    <p class="text-gray-600 uppercase font-semibold text-xs tracking-wide">{{ title }}</p>
  </div>
</template>

<script>
export default {
  props: {
    title: String,
  },
};
</script>
