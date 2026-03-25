<script setup>
import { onMounted, ref } from 'vue';

defineProps(['modelValue']);

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="border-gray-200 focus:border-gray-900 focus:ring-gray-900 rounded-xl shadow-sm text-sm read-only:cursor-not-allowed read-only:bg-gray-200 read-only:text-gray-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-700 h-11"
        :value="modelValue" maxlength="255"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>
