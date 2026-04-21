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
        class="border-gray-200 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-sm text-base disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-700 h-11"
        :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
        <slot />
    </select>
</template>
