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
    <select
        class="border-gray-300 focus:border-gray-700 focus:ring-gray-700 rounded-sm shadow-sm text-sm disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-700"
        :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
        <slot />
    </select>
</template>
