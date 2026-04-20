<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    branch: Object, // { id, title }
    centres: {
        type: Array,
        default: () => [],
    },
});

const selectedCenter = ref(null);

const selectCenter = (center) => {
    selectedCenter.value = center.id;
    // Navigate to confirm details step
    router.get(
        route("student.course.select-course", {
            branch_id: props.branch.id,
            centre_id: center.id,
        }),
    );
};

const goBack = () => {
    router.get(route("student.course.index"));
};

const steps = [
    { number: 1, label: "Select Region" },
    { number: 2, label: "Select Center" },
    { number: 3, label: "Confirm Details" },
];
</script>

<template>
    <Head title="Select Training Center - Change Course" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Change Course
                </h2>
            </div>
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
                                    step.number === 2
                                        ? { backgroundColor: '#facc15' }
                                        : step.number < 2
                                          ? { backgroundColor: '#16a34a' }
                                          : { backgroundColor: '#d1d5db' }
                                "
                            >
                                {{ step.number === 1 ? "✓" : step.number }}
                            </div>

                            <!-- Connector Line (not after last step) -->
                            <div
                                v-if="index < steps.length - 1"
                                class="w-12"
                                :style="{
                                    height: '4px',
                                    backgroundColor:
                                        step.number < 2 ? '#16a34a' : '#d1d5db',
                                }"
                            ></div>
                        </template>
                    </div>

                    <!-- Step Label -->
                    <div class="text-center mt-4 text-gray-600 text-sm">
                        Select Training Center
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
                                home
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Select Training Center
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Choose the training center in
                                <strong>{{ props.branch?.title }}</strong> where
                                you'll attend
                            </p>
                        </div>
                    </div>

                    <!-- Centers Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <button
                            v-for="centre in props.centres"
                            :key="centre.id"
                            @click="selectCenter(centre)"
                            class="text-left p-6 border border-gray-200 rounded-lg hover:border-yellow-400 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2"
                            :class="{
                                'border-yellow-400 bg-yellow-50':
                                    selectedCenter === centre.id,
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">
                                        {{ centre.title }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mt-1">
                                        {{
                                            centre.gps_address ||
                                            "Location available"
                                        }}
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

                    <!-- Back Button -->
                    <div class="mt-8">
                        <button
                            @click="goBack"
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all"
                        >
                            Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
