<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="radio"
        :value="value"
        v-model="proxyChecked"
        class="w-5 h-5 border-gray-700 text-gray-700 shadow-sm focus:ring-gray-500"
    />
</template>
