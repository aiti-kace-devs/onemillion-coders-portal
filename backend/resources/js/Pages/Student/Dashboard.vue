<script setup>
import { computed, ref } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import RevokeOrDeclineAdmissionModal from "@/Components/RevokeOrDeclineAdmissionModal.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    exams: Object,
    questionnaires: Object,
    registeredCourse: Object,
    cohort: Object,
    centre: Object,
    waitlistPosition: Number,
    userAdmission: Object,
    isInAdmissionCooldown: Boolean,
    admissionCooldownTimeRemaining: String,
    partnerAdmission: Object,
});

const { config } = usePage().props;
const user = computed(() => usePage().props.auth?.user || {});
const isOnWaitlist = computed(() => !!user.value?.on_waitlist);
const onboardingStep = computed(
    () => user.value?.current_onboarding_step ?? null,
);
const showOnboardingModal = ref(
    !!onboardingStep.value && !props.isInAdmissionCooldown,
);
const showCooldownModal = ref(props.isInAdmissionCooldown);

const onboardingModalConfig = computed(() => {
    const step = onboardingStep.value;
    const configs = {
        application_review: {
            icon: 'menu_book',
            title: 'Review Your Application',
            message: 'Before you begin, please take a few minutes to review the enrollment process and understand each step ahead.',
            buttonText: 'Start Review',
            route: 'student.application-review.index',
        },
        assessment: {
            icon: 'psychology',
            title: 'Complete Your Assessment',
            message: 'You need to complete the level determination assessment. This short test helps us place you at the right starting level.',
            buttonText: 'Take Assessment',
            route: 'student.level-assessment',
        },
        identity_verification: {
            icon: 'verified_user',
            title: 'Verify Your Identity',
            message: 'Please complete your Ghana Card identity verification to continue your enrollment.',
            buttonText: 'Start Verification',
            route: 'student.verification.index',
        },
        course_selection: {
            icon: 'school',
            title: 'Choose a Course',
            message: "You haven't selected a course yet. Please choose a course to continue your enrollment.",
            buttonText: 'Choose a Course',
            route: 'student.change-course',
        },
    };
    return configs[step] || null;
});

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

const hasPartnerAdmission = computed(() => {
    return !!(props.partnerAdmission?.status === 'admitted' && props.registeredCourse?.is_online);
});

/** When assessment is still pending, surface it before identity verification in Quick Access. */
const showAssessmentQuickAccess = computed(() => !tieredTestTaken.value);

const hasMoreShortcuts = computed(() => {
    const u = user.value;
    const showCourseAssessment = !!config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS;
    return (
        (u.isAdmitted && showCourseAssessment) ||
        u.isAdmitted ||
        (tieredTestTaken.value && !u.isAdmitted && !u.shortlist)
    );
});

function quickAccessShowNextRibbon(stepKey) {
    return onboardingStep.value === stepKey;
}
const firstName = computed(() => {
    const name = user.value?.name?.split(" ")[0] || "";
    return name.charAt(0).toUpperCase() + name.slice(1).toLowerCase();
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 17) return "Good afternoon";
    return "Good evening";
});
</script>

<template>

    <Head title="Dashboard">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
            rel="stylesheet" />
    </Head>
    <AuthenticatedLayout>
        <!-- Admission Cooldown Modal -->
        <Modal :show="showCooldownModal" max-width="md" @close="showCooldownModal = false">
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-100">
                    <span class="material-symbols-outlined text-3xl text-orange-700">schedule</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Course Selection Temporarily Unavailable
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    You recently revoked your admission. Please wait before
                    selecting a new course.
                </p>
                <div v-if="admissionCooldownTimeRemaining"
                    class="mt-4 inline-block px-4 py-3 rounded-lg bg-orange-50 border border-orange-200">
                    <p class="text-sm font-semibold text-orange-800">
                        Time remaining:
                        <span class="text-lg">{{
                            admissionCooldownTimeRemaining
                            }}</span>
                    </p>
                </div>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
                        @click="showCooldownModal = false">
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Onboarding Step Modal (dynamic for all steps) -->
        <Modal :show="showOnboardingModal && !!onboardingModalConfig" max-width="md"
            @close="showOnboardingModal = false">
            <div v-if="onboardingModalConfig" class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                    <span class="material-symbols-outlined text-3xl text-amber-700">{{ onboardingModalConfig.icon
                        }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ onboardingModalConfig.title }}
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    {{ onboardingModalConfig.message }}
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
                        @click="showOnboardingModal = false">
                        Close
                    </button>
                    <Link :href="route(onboardingModalConfig.route)"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-gray-900 bg-[#f9a825] hover:bg-[#e09621] transition-colors">
                        {{ onboardingModalConfig.buttonText }}
                    </Link>
                </div>
            </div>
        </Modal>

        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Dashboard
                </h2>
            </div>
        </template>

        <div class="pt-3">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-12 relative">
                    <div class="absolute -top-20 -left-20 w-64 h-64 bg-orange-100/30 rounded-full blur-[100px] -z-10">
                    </div>

                    <!-- Label -->
                    <!-- <div class="mb-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em]">OMCP Portal</span>
                    </div> -->

                    <!-- Main Heading -->
                    <div class="flex flex-col">
                        <h2 class="text-3xl md:text-4xl lg:text-4xl text-gray-900 tracking-tight leading-tight"
                            style="font-family: 'Playfair Display', serif;">
                            <span class="font-light">{{ greeting }},</span>
                            <span class="relative inline-block ml-3 font-medium">
                                {{ firstName }}
                                <!-- Decorative Underline (Brush/Stroke style) -->
                                <svg class="absolute -bottom-2 left-0 w-full h-3 text-amber-900/10 pointer-events-none"
                                    preserveAspectRatio="none" viewBox="0 0 300 20">
                                    <path d="M5 15 Q 150 5, 295 15" fill="none" stroke="currentColor" stroke-width="6"
                                        stroke-linecap="round" />
                                </svg>
                            </span>
                        </h2>
                    </div>

                    <!-- Subtext with accent line -->
                    <div class="flex items-center gap-4 mt-4">
                        <p class="text-gray-500 font-medium text-base italic opacity-80">
                            It's great to see you again. Here's what's happening
                            today.
                        </p>
                    </div>
                </div>

                <!-- Waitlist Notice -->
                <div v-if="isOnWaitlist" class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-6">
                    <div class="flex items-start gap-4">
                        <span
                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-600 shrink-0">
                            <span class="material-symbols-outlined">hourglass_top</span>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-amber-800">
                                You're on the Waitlist
                            </h3>
                            <p class="text-sm text-amber-700 mt-1">
                                You are currently on the waitlist for your
                                chosen course. You will be notified when a space
                                becomes available.
                            </p>
                            <p v-if="waitlistPosition" class="text-sm font-semibold text-amber-800 mt-2">
                                Your position:
                                <span class="text-lg">#{{ waitlistPosition }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Summary Section: Course + Centre on same row -->
                <div v-if="hasRegisteredCourse" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Course Details card -->
                    <div class="relative bg-white rounded-2xl shadow-sm p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden"
                        :class="{ 'md:col-span-2': !centre }">
                        <div class="absolute top-0 left-0 h-full w-1 bg-[#f9a825]"></div>
                        <div class="flex items-center gap-3 mb-2">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825]">
                                <span class="material-symbols-outlined">school</span>
                            </span>
                            <div class="flex-1 text-left">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">
                                    {{
                                        isOnWaitlist
                                            ? "Chosen Course"
                                            : "Registered Course"
                                    }}
                                </p>
                                <h3 class="text-lg font-bold text-gray-800">
                                    {{ registeredCourse.course_name }}
                                </h3>
                            </div>
                        </div>

                        <div v-if="cohort"
                            class="mt-2 text-sm text-gray-600 flex items-center gap-2 flex-wrap text-left">
                            <span class="inline-flex items-center gap-1 text-[#f9a825]">
                                <span class="material-symbols-outlined text-base">groups</span>
                            </span>
                            <span class="font-medium">{{ cohortLabel }}</span>
                            <template v-for="(item, idx) in cohortDetailRow" :key="idx">
                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span>{{ item }}</span>
                            </template>
                        </div>

                        <!-- Revoke button — moved to footer, redesigned as a quiet destructive action -->
                        <div v-if="userAdmission?.confirmed && user?.id"
                            class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-green-600 font-medium">Admission active</span>
                            <div class="flex items-center gap-3">
                                <!-- Partner Join Button -->
                                <a v-if="hasPartnerAdmission"
                                    :href="route('student.partner-login', { partner_slug: partnerAdmission.partner_slug })"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#f9a825] text-gray-900 rounded-xl text-sm font-bold shadow-sm hover:shadow-lg hover:shadow-[#f9a825]/20 transition-all duration-300">
                                    <span class="material-symbols-outlined text-sm">school</span>
                                    Go to Classroom
                                </a>

                                <RevokeOrDeclineAdmissionModal :user="user" :session="userAdmission"
                                    :show-intro-text="false" />
                            </div>
                        </div>
                    </div>

                    <!-- Centre Details card -->
                    <div v-if="centre"
                        class="relative bg-white rounded-2xl shadow-sm p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                        <div class="absolute top-0 left-0 h-full w-1 bg-[#f9a825]"></div>
                        <span v-if="centre.is_pwd_friendly"
                            class="absolute top-4 right-4 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-700">PWD
                            Friendly</span>
                        <div class="flex items-center gap-3 mb-2">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825]">
                                <span class="material-symbols-outlined">location_on</span>
                            </span>
                            <div class="flex-1 text-left">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">
                                    Centre Details
                                </p>
                                <h3 class="text-lg font-bold text-gray-800">
                                    {{ centre.title }}
                                </h3>
                            </div>
                        </div>
                        <div v-if="centreDetailRow.length"
                            class="mt-2 text-sm text-gray-600 flex items-center gap-2 flex-wrap text-left">
                            <template v-for="(item, idx) in centreDetailRow" :key="idx">
                                <span v-if="idx > 0" class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span>{{ item }}</span>
                            </template>
                        </div>
                        <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between">
                            <a v-if="directionsUrl" :href="directionsUrl" target="_blank" rel="noopener noreferrer"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-green-600 hover:text-green-700 transition-colors">
                                <span class="material-symbols-outlined text-base">near_me</span>
                                Click here to get direction to your center
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-10">
                    <div>
                        <p class="mb-1 text-sm font-bold text-gray-400 uppercase tracking-widest">
                            Quick access
                        </p>
                        <p class="mb-4 text-sm text-gray-500 max-w-2xl">
                            Application status is your hub for progress and the
                            expected flow; use the shortcuts below to complete
                            each step.
                        </p>

                        <Link :href="route('student.application-status')" class="block w-full max-w-3xl mb-6">
                            <div
                                class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-3 border border-gray-100/80 overflow-hidden">
                                <div
                                    class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                </div>
                                <span
                                    class="inline-flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 shrink-0 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                    <span class="material-symbols-outlined text-[22px] sm:text-[24px]">contract</span>
                                </span>
                                <div class="flex-1 text-left min-w-0">
                                    <h3 class="text-base sm:text-lg font-bold text-gray-800">
                                        Application status
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-0.5">
                                        View your timeline, expected flow, and what to do next.
                                    </p>
                                </div>
                                <span class="material-symbols-outlined text-gray-400 shrink-0 hidden sm:inline"
                                    aria-hidden="true">chevron_right</span>
                            </div>
                        </Link>

                        <!-- Before level assessment: review → assessment → verification. -->
                        <template v-if="showAssessmentQuickAccess">
                            <div class="flex flex-col gap-6 max-w-3xl mb-10">
                                <div v-if="!user.application_review_completed">
                                    <p class="mb-3 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        Application review
                                    </p>
                                    <Link :href="route(
                                        'student.application-review.index',
                                    )
                                        " class="block w-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <span v-if="
                                                quickAccessShowNextRibbon(
                                                    'application_review',
                                                )
                                            "
                                                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                Next step
                                            </span>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">menu_book</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
                                                        Application review
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="mt-2 space-y-1 text-left">
                                                <p class="text-sm">
                                                    Read how enrollment works
                                                    before you start the level
                                                    assessment.
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>

                                <div>
                                    <p class="mb-3 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        Assessment
                                    </p>
                                    <Link :href="route('student.level-assessment')
                                        " class="block w-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <span v-if="
                                                quickAccessShowNextRibbon(
                                                    'assessment',
                                                )
                                            "
                                                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                Next step
                                            </span>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">psychology</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
                                                        Level Determination
                                                        Assessment
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="mt-2 space-y-1 text-left">
                                                <p class="text-sm">
                                                    Complete this before
                                                    identity verification. It
                                                    places you at the right
                                                    starting level.
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>

                                <div>
                                    <p class="mb-3 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        Identity Verification
                                    </p>
                                    <Link :href="route('student.verification.index')
                                        " class="block w-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <span v-if="
                                                quickAccessShowNextRibbon(
                                                    'identity_verification',
                                                )
                                            "
                                                class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 z-[1]">
                                                Next step
                                            </span>
                                            <span
                                                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="{
                                                    'bg-green-100 text-green-700':
                                                        user.verification_status ===
                                                        'verified',
                                                    'bg-red-100 text-red-700':
                                                        user.verification_status ===
                                                        'blocked',
                                                    'bg-orange-100 text-orange-700':
                                                        user.verification_status ===
                                                        'processing',
                                                    'bg-red-100 text-red-700':
                                                        user.verification_status ===
                                                        'failed',
                                                    'bg-orange-100 text-orange-700':
                                                        user.verification_status ===
                                                        'pending',
                                                }">
                                                {{
                                                    {
                                                        verified: "Verified",
                                                        blocked: "Blocked",
                                                        processing:
                                                            "Processing",
                                                        failed: "Failed",
                                                        pending: "Pending",
                                                    }[
                                                    user.verification_status
                                                    ] || "Pending"
                                                }}
                                            </span>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
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
                                                    Complete Ghana Card identity
                                                    verification to continue
                                                    course selection.
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <template v-if="hasMoreShortcuts">
                                <p class="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    More shortcuts
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    <Link v-if="
                                        user.isAdmitted &&
                                        config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS
                                    " :href="route('student.results')" class="block h-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <!-- Hover Accent Line -->
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <!-- Icon and Title -->
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">task</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
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

                                    <Link :href="route('student.assessment.index')
                                        " v-if="
                                            user.isAdmitted &&
                                            config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS
                                        " class="block h-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <!-- Hover Accent Line -->
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <!-- Icon and Title -->
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">rate_review</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
                                                        Course Assessment
                                                    </h3>
                                                </div>
                                            </div>

                                            <!-- Exam Details -->
                                            <div class="mt-2 space-y-1 text-left">
                                                <p class="text-sm">
                                                    Provide feedback and rating
                                                    on course to improve course
                                                    delivery.
                                                </p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link v-if="
                                        user.isAdmitted &&
                                        !user.isOnlineCourse
                                    " :href="route('student.attendance.show')" class="block h-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <!-- Hover Accent Line -->
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <!-- Icon and Title -->
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">rule</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
                                                        Attendance
                                                    </h3>
                                                </div>
                                            </div>
                                            <!-- Exam Details -->
                                            <div class="mt-2 space-y-1 text-left">
                                                <p class="text-sm">
                                                    This module displays your
                                                    attendance record.
                                                </p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link v-if="
                                        tieredTestTaken &&
                                        !user.isAdmitted &&
                                        !user.shortlist &&
                                        !isInAdmissionCooldown
                                    " :href="route('student.change-course')" class="block h-full">
                                        <div
                                            class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                            <!-- Hover Accent Line -->
                                            <div
                                                class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                            </div>
                                            <span v-if="
                                                quickAccessShowNextRibbon(
                                                    'course_selection',
                                                )
                                            "
                                                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 z-[1]">
                                                Next step
                                            </span>
                                            <!-- Icon and Title -->
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                                    <span class="material-symbols-outlined">swap_horiz</span>
                                                </span>
                                                <div class="flex-1 text-left">
                                                    <h3 class="text-lg font-bold text-gray-800">
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
                            </template>
                        </template>

                        <!-- After level assessment: shortcut grid (application status is above for everyone). -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <Link :href="route('student.application-status')" class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">contract</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Application Status
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            View your timeline, expected flow,
                                            and what to do next.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link :href="route('student.verification.index')" class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <span v-if="
                                        quickAccessShowNextRibbon(
                                            'identity_verification',
                                        )
                                    "
                                        class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 z-[1]">
                                        Next step
                                    </span>
                                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-green-100 text-green-700':
                                                user.verification_status ===
                                                'verified',
                                            'bg-red-100 text-red-700':
                                                user.verification_status ===
                                                'blocked',
                                            'bg-orange-100 text-orange-700':
                                                user.verification_status ===
                                                'processing',
                                            'bg-red-100 text-red-700':
                                                user.verification_status ===
                                                'failed',
                                            'bg-orange-100 text-orange-700':
                                                user.verification_status ===
                                                'pending',
                                        }">
                                        {{
                                            {
                                                verified: "Verified",
                                                blocked: "Blocked",
                                                processing: "Processing",
                                                failed: "Failed",
                                                pending: "Pending",
                                            }[user.verification_status] ||
                                            "Pending"
                                        }}
                                    </span>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
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
                                            Complete Ghana Card identity
                                            verification to continue course
                                            selection.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link v-if="
                                user.isAdmitted &&
                                config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS
                            " :href="route('student.results')" class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">task</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Results
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            View your exam results and
                                            performance.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link :href="route('student.assessment.index')" v-if="
                                user.isAdmitted &&
                                config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS
                            " class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">rate_review</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Course Assessment
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            Provide feedback and rating on
                                            course to improve course delivery.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link v-if="user.isAdmitted && !user.isOnlineCourse"
                                :href="route('student.attendance.show')" class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">rule</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Attendance
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1 text-left">
                                        <p class="text-sm">
                                            This module displays your attendance
                                            record.
                                        </p>
                                    </div>
                                </div>
                            </Link>

                            <Link v-if="
                                tieredTestTaken &&
                                !user.isAdmitted &&
                                !user.shortlist &&
                                !isInAdmissionCooldown
                            " :href="route('student.change-course')" class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <span v-if="
                                        quickAccessShowNextRibbon(
                                            'course_selection',
                                        )
                                    "
                                        class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 z-[1]">
                                        Next step
                                    </span>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">swap_horiz</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                {{
                                                    user.registered_course
                                                        ? "Change Course"
                                                        : "Choose Course"
                                                }}
                                            </h3>
                                        </div>
                                    </div>
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
                    <div v-if="
                        user.isAdmitted &&
                        config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS
                    ">
                        <p class="mb-4 text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Course Assessment
                        </p>
                        <div v-if="questionnaires.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            <Link v-for="(questionnaire, key) in questionnaires" :key="questionnaire.id" :href="route(
                                'student.assessment.take-questionnaire',
                                questionnaire.code,
                            )
                                " class="block h-full">
                                <div
                                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col h-full border border-gray-100/80 overflow-hidden">
                                    <!-- Hover Accent Line -->
                                    <div
                                        class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500">
                                    </div>
                                    <!-- Status badge -->
                                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="questionnaire.is_submitted
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-yellow-100 text-yellow-700'
                                            ">
                                        {{
                                            questionnaire.is_submitted
                                                ? "Completed"
                                                : "Incomplete"
                                        }}
                                    </span>

                                    <!-- Icon and Title -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                                            <span class="material-symbols-outlined">rate_review</span>
                                        </span>
                                        <div class="flex-1 text-left">
                                            <h3 class="text-lg font-bold text-gray-800">
                                                {{ questionnaire.title }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div v-else
                            class="border border-gray-200 bg-white rounded-lg p-4 text-center h-64 flex justify-center items-center">
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
    0% {
        transform: rotate(0deg);
    }

    10% {
        transform: rotate(14deg);
    }

    20% {
        transform: rotate(-8deg);
    }

    30% {
        transform: rotate(14deg);
    }

    40% {
        transform: rotate(-4deg);
    }

    50% {
        transform: rotate(10deg);
    }

    60% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(0deg);
    }
}

.animate-wave {
    animation: wave 2.5s infinite;
}
</style>
