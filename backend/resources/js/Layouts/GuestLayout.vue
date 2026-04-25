<script setup>
import { ref, onMounted, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";

const backgroundImage = ref(null);

onMounted(async () => {
    try {
        const response = await fetch("/api/globals/student_login_image");
        if (!response.ok) throw new Error("Failed to load student login image");
        const json = await response.json();
        backgroundImage.value = json?.data?.image?.url ?? null;
    } catch (error) {
        console.error("GuestLayout background load error", error);
    }
});

const backgroundStyle = computed(() => ({
    backgroundImage: backgroundImage.value
        ? `url(${backgroundImage.value})`
        : undefined,
    backgroundSize: backgroundImage.value ? "cover" : undefined,
    backgroundPosition: backgroundImage.value ? "center center" : undefined,
    backgroundRepeat: backgroundImage.value ? "no-repeat" : undefined,
    backgroundColor: backgroundImage.value ? "rgba(31, 41, 55, 1)" : "#4b5563", // gray overlay effect
    backgroundBlendMode: backgroundImage.value ? "overlay" : undefined,
}));
</script>

<template>
    <div
        :style="backgroundStyle"
        class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-600"
    >
        <div class="relative z-10 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-8 mb-6 px-4 max-w-3xl w-full">
            <img
                src="/assets/home/images/c.png"
                alt="Ministry of Communication, Digital Technology and Innovations"
                class="h-16 sm:h-20 lg:h-24 w-auto object-contain"
            />
            <span class="h-px w-24 sm:h-12 sm:w-px lg:h-16 bg-white/30" aria-hidden="true"></span>
            <Link href="/" aria-label="One Million Coders home" class="shrink-0">
                <ApplicationLogo
                    src="/assets/images/logo-bt.png"
                    class="h-16 sm:h-20 lg:h-24 w-auto object-contain"
                />
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
