<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'amber',
  }
});
</script>
<template>
  <Link
    :href="href"
    class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-xl font-bold text-xs uppercase tracking-widest transition duration-150 shadow-md"
    :class="variant === 'green' ? 'bg-green-600 text-white hover:bg-green-700 shadow-green-500/20' : 'bg-[#f9a825] text-gray-900 hover:bg-[#e09621] shadow-yellow-500/10'"
  >
    <slot />
  </Link>
</template>
