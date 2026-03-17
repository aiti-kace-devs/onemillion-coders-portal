<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
});
</script>
<template>
  <Link
    :href="href"
    class="inline-flex items-center px-6 py-2.5 bg-[#f9a825] border border-transparent rounded-xl font-bold text-xs text-gray-900 uppercase tracking-widest hover:bg-[#e09621] transition duration-150 shadow-md shadow-yellow-500/10"
  >
    <slot />
  </Link>
</template>
