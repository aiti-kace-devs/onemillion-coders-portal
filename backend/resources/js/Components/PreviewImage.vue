<script>
export default {
    props: {
        src: {
            type: String,
            default: null
        },
        alt: {
            type: String,
            default: null
        }
    },
}
</script>

<template>
    <img :src="src" :alt="alt" id="previewImage" class="h-28 w-28 md:w-56 md:h-36 object-cover rounded-md text-center">
</template>