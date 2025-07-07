<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div class="mt-1" v-show="message">
        <p class="text-sm text-red-600">
            {{ message }}
        </p>
    </div>
</template>
