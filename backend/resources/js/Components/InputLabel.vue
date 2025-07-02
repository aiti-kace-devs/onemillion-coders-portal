<script setup>
defineProps({
  value: {
    type: [String, Number, Boolean], // Specify the type(s) of the value prop
    required: true, // Mark it as required if necessary
  },
  required: {
    type: Boolean,
    default: false, // Provide a default value
  },
});
</script>

<template>
  <label class="block font-medium text-sm capitalize text-gray-700">
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
    <span class="text-red-600" v-if="required">*</span>
  </label>
</template>
