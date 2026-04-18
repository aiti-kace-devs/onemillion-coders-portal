<script setup>
import { computed } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import ExamCard from "../../Components/ExamCard.vue";

const props = defineProps({
    exams: Object,
    questionnaires: Object,
    registeredCourse: Object,
    cohort: Object,
    centre: Object,
    waitlistPosition: Number,
});

const { config } = usePage().props;
const user = computed(() => usePage().props.auth?.user || {});
const isOnWaitlist = computed(() => !!user.value?.on_waitlist);

const hasRegisteredCourse = computed(() => !!props.registeredCourse);

const cohortLabel = computed(() => {
    if (!props.cohort) return "";
    if (props.cohort.title) return props.cohort.title;
    if (props.cohort.batch_number)
        return `Cohort ${props.cohort.batch_number}${props.cohort.year ? " - " + props.cohort.year : ""}`;
    return "Cohort";
});

const cohortDetailRow = computed(() => {
    if (!props.cohort) return [];
    const items = [];
    if (props.cohort.batch_number && props.cohort.title) {
        items.push(`Cohort ${props.cohort.batch_number}`);
    }
    if (props.cohort.year) items.push(String(props.cohort.year));
    if (props.cohort.start_date || props.cohort.end_date) {
        const start = formatDate(props.cohort.start_date);
        const end = formatDate(props.cohort.end_date);
        if (start && end) items.push(`${start} — ${end}`);
        else if (start) items.push(start);
        else if (end) items.push(end);
    }
    return items;
});

const centreLocation = computed(() => {
    if (!props.centre) return "";
    const district = props.centre.gps_location?.[0]?.District;
    const region = props.centre.region;
    return [district, region].filter(Boolean).join(", ");
});

const centreDetailRow = computed(() => {
    if (!props.centre) return [];
    const items = [];
    if (centreLocation.value) items.push(centreLocation.value);
    if (props.centre.gps_address) items.push(props.centre.gps_address);
    return items;
});

const directionsUrl = computed(() => {
    if (!props.centre) return null;
    const gps = props.centre.gps_location?.[0];
    if (gps?.Latitude && gps?.Longitude) {
        return `https://www.google.com/maps/search/?api=1&query=${gps.Latitude},${gps.Longitude}`;
    }
    const district = gps?.District ?? "";
    const region = props.centre.region ?? "";
    const query = [props.centre.title, district, region, "Ghana"]
        .filter(Boolean)
        .join(", ");
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
});

const formatDate = (date) => {
    if (!date) return "";
    try {
        return new Date(date).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    } catch {
        return date;
    }
};

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
                        Hi, {{ user.name }} !
                    </h2>
                    <p class="text-gray-500 mt-1 font-medium text-lg">It's great to see you again. Here's what's happening today.</p>
                </div>

                <!-- Waitlist Notice -->
                <div
                    v-if="isOnWaitlist"
                    class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-6"
                >
                    <div class="flex items-start gap-4">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-600 shrink-0">
                            <span class="material-symbols-outlined">hourglass_top</span>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-amber-800">You're on the Waitlist</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                You are currently on the waitlist for your chosen course.
                                You will be notified when a space becomes available.
                            </p>
                            <p v-if="waitlistPosition" class="text-sm font-semibold text-amber-800 mt-2">
                                Your position: <span class="text-lg">#{{ waitlistPosition }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Summary Section: Course + Centre on same row -->
                <div
                    v-if="hasRegisteredCourse"
                    class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6"
                >
                    <!-- Course Details card -->
                    <div
                        class="relative bg-white rounded-2xl shadow-sm p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
                        :class="{ 'md:col-span-2': !centre }"
                    >
                        <div class="absolute top-0 left-0 h-full w-1 bg-[#f9a825]"></div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825]">
                                <span class="material-symbols-outlined">school</span>
                            </span>
                            <div class="flex-1 text-left">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">
                                    {{ isOnWaitlist ? 'Chosen Course' : 'Registered Course' }}
                                </p>
                                <h3 class="text-lg font-bold text-gray-800">
                                    {{ registeredCourse.course_name }}
                                </h3>
                            </div>
                        </div>
                        <div v-if="cohort" class="mt-2 text-sm text-gray-600 flex items-center gap-2 flex-wrap text-left">
                            <span class="inline-flex items-center gap-1 text-[#f9a825]">
                                <span class="material-symbols-outlined text-base">groups</span>
                            </span>
                            <span class="font-medium">{{ cohortLabel }}</span>
                            <template v-for="(item, idx) in cohortDetailRow" :key="idx">
                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span>{{ item }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Centre Details card (with directions) -->
                    <div
                        v-if="centre"
                        class="relative bg-white rounded-2xl shadow-sm p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
                    >
                        <div class="absolute top-0 left-0 h-full w-1 bg-[#f9a825]"></div>
                        <span
                            v-if="centre.is_pwd_friendly"
                            class="absolute top-4 right-4 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-700"
                        >PWD Friendly</span>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825]">
                                <span class="material-symbols-outlined">location_on</span>
                            </span>
                            <div class="flex-1 text-left">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Centre Details</p>
                                <h3 class="text-lg font-bold text-gray-800">{{ centre.title }}</h3>
                            </div>
                        </div>
                        <div
                            v-if="centreDetailRow.length"
                            class="mt-2 text-sm text-gray-600 flex items-center gap-2 flex-wrap text-left"
                        >
                            <template v-for="(item, idx) in centreDetailRow" :key="idx">
                                <span v-if="idx > 0" class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span>{{ item }}</span>
                            </template>
                        </div>
                        <!-- Directions link inside centre card -->
                        <a
                            v-if="directionsUrl"
                            :href="directionsUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-[#f9a825] hover:text-amber-700 transition-colors"
                        >
                            <span class="material-symbols-outlined text-base">near_me</span>
                            Get Directions
                        </a>
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
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6"
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
                                :href="route('student.verification.index')"
                                class="block h-full"
                            >
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
                                >
                                    <div class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500"></div>
                                    <span
                                        class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="
                                            user.verification_completed
                                                ? 'bg-green-100 text-green-700'
                                                : user.verification_blocked
                                                  ? 'bg-red-100 text-red-700'
                                                  : 'bg-yellow-100 text-yellow-700'
                                        "
                                    >
                                        {{
                                            user.verification_completed
                                                ? "Verified"
                                                : user.verification_blocked
                                                  ? "Blocked"
                                                  : "Pending"
                                        }}
                                    </span>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900"
                                        >
                                            <span class="material-symbols-outlined">verified_user</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Identity Verification
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            Complete Ghana Card identity verification to continue course selection.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                v-if="user.isAdmitted && config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS"
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
                                v-if="user.isAdmitted && config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS"
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
                                v-if="user.isAdmitted && !user.isOnlineCourse"
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

                    <div v-if="user.isAdmitted && config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS">
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
