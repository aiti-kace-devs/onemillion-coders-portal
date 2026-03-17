<script setup>
import { computed } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import ExamCard from "../../Components/ExamCard.vue";

const props = defineProps({
    exams: Object,
    questionnaires: Object,
    registeredCourse: Object,
});

const user = computed(() => usePage().props.auth?.user || {});

const hasRegisteredCourse = computed(() => !!props.registeredCourse);

const examList = computed(() => props.exams || []);
const totalExams = computed(() => examList.value.length);
const completedExams = computed(
    () => examList.value.filter((e) => e.submitted).length,
);
const pendingExams = computed(
    () =>
        examList.value.filter(
            (e) => !e.submitted && getExamStatus(e) === "pending",
        ).length,
);
const overdueExams = computed(
    () => examList.value.filter((e) => getExamStatus(e) === "overdue").length,
);
const overallProgress = computed(() =>
    totalExams.value === 0
        ? 0
        : Math.round((completedExams.value / totalExams.value) * 100),
);

const tieredTestTaken = computed(() => {
    return !!user.value?.assessment_completed;
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="pt-3">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h2 class="text-4xl font-black text-gray-900 tracking-tighter leading-tight">
                        Hi, {{ user.name }} <span class="inline-block origin-bottom animate-wave">👋</span>
                    </h2>
                    <p class="text-gray-500 mt-1 font-medium text-lg">It's great to see you again. Here's what's happening today.</p>
                </div>

                <!-- Chosen Course Section -->
                <div v-if="hasRegisteredCourse" class="mt-6">
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-orange-100/50 p-6 transition-all duration-300 relative overflow-hidden group shadow-orange-500/5"
                    >
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-[#f9a825]"></div>
                        <div class="flex items-center gap-4">
                            <div
                                class="p-3 bg-[#f9a825]/10 rounded-lg text-[#f9a825]"
                            >
                                <span class="material-symbols-outlined text-3xl"
                                    >school</span
                                >
                            </div>
                            <div>
                                <p
                                    class="text-gray-500 text-xs font-medium uppercase tracking-wider"
                                >
                                    Your Chosen Course
                                </p>
                                <h3 class="text-xl font-bold text-gray-800">
                                    {{ registeredCourse.course_name }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-10">
                    <div>
                        <p
                            class="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Quick Access
                        </p>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                        >
                            <Link
                                :href="route('student.application-status')"
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
                                            <span
                                                class="material-symbols-outlined"
                                                >contract</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                Application Status
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Exam Details -->
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            Discover your standing for the
                                            current application.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                v-if="user.isAdmitted"
                                :href="route('student.results')"
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
                                            <span
                                                class="material-symbols-outlined"
                                                >task</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                Results
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Exam Details -->
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            View your exam results and
                                            performance.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                :href="route('student.assessment.index')"
                                v-if="user.isAdmitted"
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
                                            <span
                                                class="material-symbols-outlined"
                                                >rate_review</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                Course Assessment
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Exam Details -->
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            Provide feedback and rating on
                                            course to improve course delivery.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                v-if="user.isAdmitted"
                                :href="route('student.attendance.show')"
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
                                            <span
                                                class="material-symbols-outlined"
                                                >rule</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                Attendance
                                            </h3>
                                        </div>
                                    </div>
                                    <!-- Exam Details -->
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            This module displays your attendance
                                            record.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                v-if="
                                    tieredTestTaken &&
                                    !user.isAdmitted &&
                                    !user.shortlist
                                "
                                :href="route('student.change-course')"
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
                                            <span
                                                class="material-symbols-outlined"
                                                >swap_horiz</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                {{
                                                    user.registered_course
                                                        ? "Change Course"
                                                        : "Choose Course"
                                                }}
                                            </h3>
                                        </div>
                                    </div>
                                    <!-- Exam Details -->
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            {{
                                                user.registered_course
                                                    ? "Change your course to a different one."
                                                    : "Select a course to get started."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div v-if="!tieredTestTaken">
                        <p
                            class="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Assessment
                        </p>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8"
                        >
                            <ExamCard />
                        </div>
                    </div>

                    <div v-if="user.isAdmitted">
                        <p
                            class="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Course Assessment
                        </p>
                        <div
                            v-if="questionnaires.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8"
                        >
                            <Link
                                v-for="(questionnaire, key) in questionnaires"
                                :key="questionnaire.id"
                                :href="
                                    route(
                                        'student.assessment.take-questionnaire',
                                        questionnaire.code,
                                    )
                                "
                                class="block h-full"
                            >
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
                                >
                                    <!-- Hover Accent Line -->
                                    <div class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500"></div>
                                    <!-- Status badge -->
                                    <span
                                        class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="
                                            questionnaire.is_submitted
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-yellow-100 text-yellow-700'
                                        "
                                    >
                                        {{
                                            questionnaire.is_submitted
                                                ? "Completed"
                                                : "Incomplete"
                                        }}
                                    </span>

                                    <!-- Icon and Title -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900"
                                        >
                                            <span
                                                class="material-symbols-outlined"
                                                >rate_review</span
                                            >
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3
                                                class="text-lg font-bold text-gray-800"
                                            >
                                                {{ questionnaire.title }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div
                            v-else
                            class="border border-gray-200 bg-white rounded-lg p-4 text-center h-64 flex justify-center items-center"
                        >
                            <p class="text-gray-500 text-sm">
                                No course assessment available.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes wave {
    0% { transform: rotate(0deg); }
    10% { transform: rotate(14deg); }
    20% { transform: rotate(-8deg); }
    30% { transform: rotate(14deg); }
    40% { transform: rotate(-4deg); }
    50% { transform: rotate(10deg); }
    60% { transform: rotate(0deg); }
    100% { transform: rotate(0deg); }
}

.animate-wave {
    animation: wave 2.5s infinite;
}
</style>
