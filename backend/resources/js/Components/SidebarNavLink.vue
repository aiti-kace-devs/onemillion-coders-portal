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
    ? "flex gap-2 p-1.5 items-center cursor-pointer capitalize bg-gray-700 rounded-sm text-white peer w-full"
    : "flex gap-2 p-1.5 items-center cursor-pointer capitalize text-gray-500 hover:text-gray-700 peer w-full bg-transparent border-none text-left"
);
</script>

<template>
  <div class="px-4 group/item">
    <Link :href="href" :method="method" :as="as" :class="classes">
      <slot />

      <div
        class="flex-1 flex justify-between items-center text-sm font-medium whitespace-nowrap group-[.sidebar-collapsed]/container:hidden group"
      >
        <p>{{ label }}</p>
      </div>
    </Link>
  </div>
</template>
