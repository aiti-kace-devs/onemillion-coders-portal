<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
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
    ? "flex gap-x-2 p-1.5 items-center cursor-pointer bg-gray-700 text-white text-sm rounded-sm peer capitalize"
    : "flex gap-x-2 p-1.5 items-center cursor-pointer peer capitalize text-gray-500 hover:text-gray-700"
);
</script>

<template>
  <div class="px-4 group/item">
    <Link :href="href" :class="classes">
      <slot />

      <div
        class="flex-1 flex justify-between items-center hidden group-hover/sidebar-container:block group"
      >
        <p>{{ label }}</p>
      </div>
    </Link>
  </div>
</template>
