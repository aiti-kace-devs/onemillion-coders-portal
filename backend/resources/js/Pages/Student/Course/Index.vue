<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    branches: Array, // [{ id, name }]
});

const selectedBranch = ref(null);

const selectBranch = (branch) => {
    selectedBranch.value = branch.id;
    // Navigate to select training center
    router.get(route("student.course.select-center", { branch_id: branch.id }));
};

const steps = [
    { number: 1, label: "Select Region" },
    { number: 2, label: "Select Center" },
    { number: 3, label: "Confirm Details" },
];
</script>

<template>
    <Head title="Select Region - Change Course" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Change Course
                </h2>
            </div>
        </template>

        <div class="py-4 px-2 sm:px-6 lg:px-8">
            <!-- Step Indicator -->
            <div class="max-w-7xl mx-auto mb-6">
                <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-4 sm:p-8 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-center gap-1 sm:gap-4">
                        <template
                            v-for="(step, index) in steps"
                            :key="step.number"
                        >
                            <!-- Step Circle -->
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="flex items-center justify-center w-8 h-8 sm:w-12 sm:h-12 rounded-full font-bold text-xs sm:text-base text-white transition-all shadow-sm"
                                    :style="
                                        step.number === 1
                                            ? { backgroundColor: '#facc15' }
                                            : { backgroundColor: '#e5e7eb' }
                                    "
                                >
                                    {{ step.number }}
                                </div>
                                <span class="hidden sm:block text-[10px] uppercase tracking-wider font-bold text-gray-400">Step {{ step.number }}</span>
                            </div>

                            <!-- Connector Line (not after last step) -->
                            <div
                                v-if="index < steps.length - 1"
                                class="hidden sm:block flex-1 max-w-[40px] sm:max-w-[100px] h-1 rounded-full mx-1 sm:mx-2"
                                :style="{
                                    backgroundColor: '#e5e7eb',
                                    marginTop: '-16px'
                                }"
                            ></div>
                            <!-- Mobile Connector Line -->
                            <div
                                v-if="index < steps.length - 1"
                                class="w-4 h-[2px] bg-gray-200 sm:hidden"
                            ></div>
                        </template>
                    </div>

                    <!-- Step Label -->
                    <div class="text-center mt-3 text-gray-800 font-bold text-sm sm:text-base uppercase tracking-widest">
                        {{ steps[0].label }}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 sm:p-10">
                        <!-- Header with Icon -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left mb-8">
                            <div
                                class="flex-shrink-0 flex items-center justify-center rounded-2xl shadow-inner"
                                :style="{
                                    backgroundColor: '#facc15',
                                    width: '56px',
                                    height: '56px',
                                }"
                            >
                                <span
                                    class="material-symbols-outlined text-white"
                                    :style="{ fontSize: '32px' }"
                                >
                                    location_on
                                </span>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">
                                    Select Your Region
                                </h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base max-w-lg">
                                    Choose the region where you'd like to attend training. We have centers across the country.
                                </p>
                            </div>
                        </div>

                        <!-- Regions Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <button
                                v-for="branch in branches"
                                :key="branch.id"
                                @click="selectBranch(branch)"
                                class="group relative text-left p-5 sm:p-6 border-2 border-gray-50 rounded-2xl hover:border-amber-400 hover:bg-amber-50/30 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                :class="{
                                    'border-amber-400 bg-amber-50/50 shadow-md':
                                        selectedBranch === branch.id,
                                }"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 text-base sm:text-lg truncate group-hover:text-amber-700 transition-colors">
                                            {{ branch.title }}
                                        </h3>
                                        <div class="flex items-center gap-1.5 mt-1 text-gray-500">
                                            <span class="material-symbols-outlined text-sm">home_pin</span>
                                            <p class="text-xs sm:text-sm font-medium">
                                                Available Centers
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-amber-100 group-hover:text-amber-600 transition-colors">
                                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
