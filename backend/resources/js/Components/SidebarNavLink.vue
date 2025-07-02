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
  redirect: {
    type: Boolean,
    default: false 
  },
});

const linkComponent = props.redirect ? "a" : Link;

const classes = computed(() =>
  props.active
    ? "flex gap-x-2 px-4 py-3 justify-center items-center cursor-pointer rounded-sm font-bold leading-5 peer text-white bg-gray-800"
    : "flex gap-x-2 px-4 py-3 justify-center items-center cursor-pointer rounded-sm font-medium leading-5 text-gray-500 hover:text-gray-700 peer"
);
</script>

<template>
  <div class="group/item">
    <component
      :is="linkComponent"
       :href="href" 
       :class="classes">
      <slot />

      <div
        class="flex-1 flex justify-between items-center font-medium whitespace-nowrap group-[.sidebar-collapsed]/container:hidden group"
      >
        <p class="capitalize">{{ label }}</p>
      </div>
    </component>
  </div>
</template>
