<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  method: {
    type: String,
    default: "get",
  },
  as: {
    type: String,
    default: "a",
  },
  label: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
  },
});

const classes = computed(() =>
  props.active
    ? "flex gap-3 p-2.5 items-center cursor-pointer capitalize bg-green-600 rounded-xl font-bold text-white shadow-lg shadow-green-500/30 w-full transition-all duration-300 border border-green-600"
    : "flex gap-3 p-2.5 items-center cursor-pointer capitalize text-gray-600 hover:bg-green-50 rounded-xl w-full transition-all duration-200 border-none text-left"
);
</script>

<template>
  <div class="px-4 group/item">
    <Link :href="href" :method="method" :as="as" :class="classes">
      <slot />

      <div
        class="flex-1 flex justify-between items-center text-sm font-bold whitespace-nowrap group-[.sidebar-collapsed]/container:hidden group"
        :class="active ? 'text-white' : 'text-gray-600'"
      >
        <p>{{ label }}</p>
      </div>
    </Link>
  </div>
</template>
