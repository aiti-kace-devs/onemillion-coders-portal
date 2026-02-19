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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Change Course
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Step Indicator -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg p-8">
                    <div class="flex items-center justify-center gap-2">
                        <template
                            v-for="(step, index) in steps"
                            :key="step.number"
                        >
                            <!-- Step Circle -->
                            <div
                                class="flex items-center justify-center w-12 h-12 rounded-full font-semibold text-white transition-all"
                                :style="
                                    step.number === 1
                                        ? { backgroundColor: '#facc15' }
                                        : { backgroundColor: '#d1d5db' }
                                "
                            >
                                {{ step.number }}
                            </div>

                            <!-- Connector Line (not after last step) -->
                            <div
                                v-if="index < steps.length - 1"
                                class="w-12"
                                :style="{
                                    height: '4px',
                                    backgroundColor: '#d1d5db',
                                }"
                            ></div>
                        </template>
                    </div>

                    <!-- Step Label -->
                    <div class="text-center mt-4 text-gray-600 text-sm">
                        Select Region
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white rounded-lg shadow-sm px-6 py-8">
                    <!-- Header with Icon -->
                    <div class="flex items-start gap-4">
                        <div
                            class="flex-shrink-0 flex items-center justify-center rounded-lg"
                            :style="{
                                backgroundColor: '#facc15',
                                width: '64px',
                                height: '64px',
                            }"
                        >
                            <span
                                class="material-symbols-outlined text-white"
                                :style="{ fontSize: '40px' }"
                            >
                                location_on
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Select Your Region
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Choose the region where you'd like to attend
                                training
                            </p>
                        </div>
                    </div>

                    <!-- Regions Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <button
                            v-for="branch in branches"
                            :key="branch.id"
                            @click="selectBranch(branch)"
                            class="text-left p-6 border border-gray-200 rounded-lg hover:border-yellow-400 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2"
                            :class="{
                                'border-yellow-400 bg-yellow-50':
                                    selectedBranch === branch.id,
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">
                                        {{ branch.title }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mt-1">
                                        Training centers available
                                    </p>
                                </div>
                                <svg
                                    class="w-6 h-6 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
