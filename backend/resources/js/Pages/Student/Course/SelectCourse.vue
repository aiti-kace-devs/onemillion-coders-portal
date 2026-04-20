<script setup>
import { ref, nextTick } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    user: Object,
    branch: Object, // { id, title }
    centre: Object, // { id, title, gps_address }
    courses: {
        type: Array,
        default: () => [],
    },
    flash: Object,
});

const selectedCourse = ref(null);
const isLoading = ref(false);
const showSuccessMessage = ref(false);

const selectCourse = (course) => {
    selectedCourse.value = course.id;
};

const confirmSelection = () => {
    if (!selectedCourse.value) {
        alert("Please select a course");
        return;
    }

    isLoading.value = true;
    // Submit the course selection
    router.post(
        route("student.update-course"),
        {
            course_id: selectedCourse.value,
            branch_id: props.branch.id,
            centre_id: props.centre.id,
        },
        {
            onSuccess: async () => {
                // Wait for props to be fully updated
                await nextTick();

                // Check if there's an error flash message from the backend
                // If yes, don't show success message
                if (props.flash?.key === "error") {
                    toastr.error(props.flash.message);
                    isLoading.value = false;
                    return;
                }

                showSuccessMessage.value = true;
                isLoading.value = false;
                // Redirect after 2 seconds to show success message
                setTimeout(() => {
                    router.visit(route("student.exam.index"));
                }, 2000);
            },
            onError: (errors) => {
                isLoading.value = false;
                console.log("Validation errors:", errors);
                toastr.error("Something went wrong. Please try again.");
            },
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

const goBack = () => {
    router.get(
        route("student.course.select-center", {
            branch_id: props.branch.id,
        }),
    );
};

const steps = [
    { number: 1, label: "Select Region" },
    { number: 2, label: "Select Center" },
    { number: 3, label: "Confirm Details" },
];
</script>

<template>
    <Head title="Select Course - Change Course" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Change Course
                </h2>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            <div
                v-if="props.flash?.flash || showSuccessMessage"
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 text-red-800':
                            props.flash?.key === 'error',
                        'bg-green-50 border-l-4 border-green-500 text-green-800':
                            props.flash?.key === 'success' ||
                            showSuccessMessage,
                    }"
                    class="rounded-lg p-4 flex items-start gap-3"
                >
                    <span
                        class="material-symbols-outlined flex-shrink-0"
                        :style="{
                            color:
                                props.flash?.key === 'error'
                                    ? '#dc2626'
                                    : '#16a34a',
                        }"
                    >
                        {{
                            props.flash?.key === "error"
                                ? "error"
                                : "check_circle"
                        }}
                    </span>
                    <p class="text-sm font-medium">
                        {{
                            showSuccessMessage
                                ? "Course changed successfully! Redirecting..."
                                : props.flash?.flash
                        }}
                    </p>
                </div>
            </div>

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
                                    step.number === 3
                                        ? { backgroundColor: '#facc15' }
                                        : step.number < 3
                                          ? { backgroundColor: '#16a34a' }
                                          : { backgroundColor: '#d1d5db' }
                                "
                            >
                                {{ step.number < 3 ? "✓" : step.number }}
                            </div>

                            <!-- Connector Line (not after last step) -->
                            <div
                                v-if="index < steps.length - 1"
                                class="w-12"
                                :style="{
                                    height: '4px',
                                    backgroundColor:
                                        step.number < 3 ? '#16a34a' : '#d1d5db',
                                }"
                            ></div>
                        </template>
                    </div>

                    <!-- Step Label -->
                    <div class="text-center mt-4 text-gray-600 text-sm">
                        Select Course
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white rounded-lg shadow-sm px-6 py-8">
                    <!-- Header with Icon -->
                    <div class="flex items-start gap-4 mb-8">
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
                                school
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Select Your Course
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Choose a course to pursue at
                                <strong>{{ props.centre?.title }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Location Info -->
                    <div
                        class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"
                    >
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 font-semibold">
                                    REGION
                                </p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ props.branch?.title }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-semibold">
                                    TRAINING CENTER
                                </p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ props.centre?.title }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Courses List -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Available Courses
                    </h3>

                    <div
                        v-if="props.courses.length === 0"
                        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6"
                    >
                        <p class="text-sm text-yellow-800">
                            No courses available at this training center.
                        </p>
                    </div>

                    <div v-else class="space-y-3 mb-8">
                        <button
                            v-for="course in props.courses"
                            :key="course.id"
                            @click="selectCourse(course)"
                            class="w-full text-left p-4 border-2 border-gray-200 rounded-lg hover:border-yellow-400 hover:bg-yellow-50 transition-all focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2"
                            :class="{
                                'border-yellow-400 bg-yellow-50':
                                    selectedCourse === course.id,
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">
                                        {{ course.course_name }}
                                    </h4>
                                    <p
                                        v-if="course.duration"
                                        class="text-sm text-gray-600 mt-1"
                                    >
                                        {{ course.duration }}
                                    </p>
                                </div>
                                <div
                                    class="ml-4 flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            selectedCourse === course.id
                                                ? '#facc15'
                                                : '#e5e7eb',
                                    }"
                                >
                                    <span
                                        class="material-symbols-outlined text-white"
                                        style="font-size: 16px"
                                    >
                                        {{
                                            selectedCourse === course.id
                                                ? "check"
                                                : ""
                                        }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button
                            @click="goBack"
                            :disabled="showSuccessMessage"
                            class="px-6 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Back
                        </button>
                        <PrimaryButton
                            @click="confirmSelection"
                            :disabled="
                                !selectedCourse ||
                                isLoading ||
                                showSuccessMessage
                            "
                            class="flex-1"
                        >
                            {{
                                showSuccessMessage
                                    ? "Course Selected!"
                                    : isLoading
                                      ? "Loading..."
                                      : "Confirm Course Selection"
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
