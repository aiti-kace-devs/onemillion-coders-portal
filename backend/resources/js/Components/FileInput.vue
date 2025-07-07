<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
  modelValue: File, // Bound file
  maxSize: { type: Number, default: 2 * 1024 * 1024 }, // Default 2MB
  allowedTypes: {
    type: Array,
    default: () => ["image/jpeg", "image/png", "image/gif"],
  },
});

defineEmits(["update:modelValue"]);

const fileInput = ref(null);

onMounted(() => {
  if (fileInput.value.hasAttribute("autofocus")) {
    fileInput.value.focus();
  }
});

defineExpose({ focus: () => fileInput.value.focus() });
</script>

<template>
  <input
    type="file"
    class="text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100"
    ref="fileInput"
  />
</template>
