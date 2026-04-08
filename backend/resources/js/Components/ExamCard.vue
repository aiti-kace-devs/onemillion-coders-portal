<script setup>
import { Link } from "@inertiajs/vue3";

</script>

<template>
    <Link
        :href="route('student.level-assessment')"
        class="block h-full"
    >
        <div
            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
        >
            <!-- Hover Accent Line -->
            <div class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500"></div>
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
                    Take the assessment to determine your starting course level.
                </p>
            </div>
        </div>
    </Link>
</template>
