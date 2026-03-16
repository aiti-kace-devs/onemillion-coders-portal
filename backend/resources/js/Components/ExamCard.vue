<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const user = usePage().props.auth?.user || {};

const levelDeterminationTestUrl = computed(() => {
    const baseUrl = usePage().props.quiz_frontend_url;
    const token = usePage().props.quiz_jwt_token;
    const userId = user?.userId ?? "";
    if (!token) return `${baseUrl}/quiz`;
    return `${baseUrl}/quiz/${userId}?token=${token}`;
});
</script>

<template>
    <a
        :href="levelDeterminationTestUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="block h-full"
    >
        <div
            class="relative group bg-white rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-6 flex flex-col h-full border border-gray-100/50"
        >
            <!-- Icon and Title -->
            <div class="flex items-center gap-3 mb-2">
                <span
                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900"
                >
                    <span class="material-symbols-outlined">psychology</span>
                </span>
                <div class="flex-1 text-left">
                    <h3 class="text-lg font-bold text-gray-800">
                        Level Determination Assessment
                    </h3>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-2 space-y-1 text-left">
                <p class="text-sm">
                    Take the assessment to determine your level.
                </p>
            </div>
        </div>
    </a>
</template>
