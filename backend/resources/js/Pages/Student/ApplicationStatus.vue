<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, useForm, Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import RevokeOrDeclineAdmissionModal from "@/Components/RevokeOrDeclineAdmissionModal.vue";
import LinkButton from "@/Components/LinkButton.vue";

const props = defineProps({
    user: Object,
    user_exam: Object,
    user_admission: Object,
    user_assessment: Object,
    verification_status: Object,
});

const reviewCompleted = computed(
    () => !!props.user?.application_review_completed_at,
);
const assessmentCompleted = computed(() => !!props.user_assessment?.completed);
const verificationCompleted = computed(() => !!props.verification_status?.verified);
const verificationBlocked = computed(() => !!props.verification_status?.blocked);

/** Admission row exists — booking/confirmation is part of this student's journey (5-step flow). */
const showConfirmationInFlow = computed(() => !!props.user_admission);

const courseChosen = computed(
    () =>
        !!props.user?.registered_course || !!props.user_admission?.course_id,
);

const admissionConfirmationComplete = computed(
    () => !!props.user_admission?.confirmed,
);

function stepIcon(stepKey) {
    const icons = {
        submitted: "mark_email_read",
        review: "menu_book",
        assessment: "psychology",
        verification: "verified_user",
        course: "school",
        confirm: "task_alt",
    };
    return icons[stepKey] || "fiber_manual_record";
}

function stepVisualStatus(stepKey) {
    if (stepKey === "submitted") return "complete";
    if (stepKey === "review") return reviewCompleted.value ? "complete" : "current";
    if (stepKey === "assessment") {
        if (assessmentCompleted.value) return "complete";
        if (reviewCompleted.value) return "current";
        return "upcoming";
    }
    if (stepKey === "verification") {
        if (verificationBlocked.value) return "blocked";
        if (verificationCompleted.value) return "complete";
        if (assessmentCompleted.value) return "current";
        return "upcoming";
    }
    if (stepKey === "course") {
        if (courseChosen.value) return "complete";
        if (verificationCompleted.value) return "current";
        return "upcoming";
    }
    if (stepKey === "confirm") {
        if (admissionConfirmationComplete.value) return "complete";
        if (courseChosen.value) return "current";
        return "upcoming";
    }
    return "upcoming";
}

// Initialize collapse state based on the current active step
const initialCollapse = [true, false, false, false, false, false];
const steps = ['submitted', 'review', 'assessment', 'verification', 'course', 'confirm'];
let foundCurrent = false;

// Expand the first 'current' step, or the last 'complete' step if all are complete
for (let i = 0; i < steps.length; i++) {
    const status = stepVisualStatus(steps[i]);
    if (status === 'current') {
        initialCollapse[i] = true;
        foundCurrent = true;
        break;
    }
}
if (!foundCurrent) {
    // If none are current, maybe they are all complete? Expand the last one.
    initialCollapse[initialCollapse.length - 1] = true;
}

const collapse = ref(initialCollapse);

const showRevokeModal = ref(false);

function toggleCollapse(idx) {
    if (isStepReached(idx)) {
        collapse.value[idx] = !collapse.value[idx];
    }
}

function isStepReached(idx) {
    if (idx === 0) return true;
    if (idx === 1) return true;
    if (idx === 2) return reviewCompleted.value;
    if (idx === 3) return assessmentCompleted.value;
    if (idx === 4) return verificationCompleted.value;
    if (idx === 5) return courseChosen.value;
    return false;
}
</script>

<template>
    <Head title="Application Status" />

    <AuthenticatedLayout compact-content-top>
        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Application Status
                </h2>
            </div>
        </template>

        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Admission Header -->
                <div
                    v-if="props.user_admission && props.user_admission.confirmed"
                    class="relative overflow-hidden p-8 bg-white shadow-xl shadow-green-500/5 sm:rounded-3xl border border-green-100"
                >
                    <div class="absolute top-0 right-0 -tr-4 -mr-4 w-32 h-32 bg-green-50 rounded-full blur-3xl opacity-50"></div>
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start gap-5">
                            <div class="shrink-0 flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-2xl shadow-inner">
                                <span class="material-symbols-outlined text-4xl">celebration</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 leading-tight">Congratulations, {{ user.first_name || user.name }}!</h3>
                                <p class="text-green-700 font-medium text-lg mt-1">You have been successfully admitted.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 bg-green-50 rounded-xl border border-green-100 flex items-center gap-2 text-green-700 font-bold">
                                <span class="material-symbols-outlined text-[20px]">verified</span>
                                Admission Confirmed
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Journey Container -->
                <div class="bg-white sm:rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 md:p-8">
                        <div class="mb-8">
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">Your Enrollment Journey</h3>
                            <p class="text-gray-500 mt-1 text-sm">Follow these steps to finalize your place in the program.</p>
                        </div>

                        <div class="relative">
                            <!-- Dynamic Progress Line -->
                            <!-- We use bottom-12 to make the gray background line stop at the last step icon's center -->
                            <div class="absolute left-5 top-5 bottom-5 w-0.5 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    class="absolute top-0 left-0 w-full bg-green-500 transition-all duration-1000 ease-in-out"
                                    :style="{ height: (Math.max(0, steps.filter(s => stepVisualStatus(s) === 'complete').length - 1) / (steps.length - 1)) * 100 + '%' }"
                                ></div>
                            </div>

                            <div class="space-y-4 relative">
                                <div
                                    v-for="(stepKey, index) in steps"
                                    :key="stepKey"
                                    v-show="stepKey !== 'confirm' || showConfirmationInFlow"
                                    class="relative pl-14 group"
                                >
                                    <!-- Step Icon/Indicator -->
                                    <div
                                        class="absolute left-0 top-0 mt-0.5 flex items-center justify-center w-10 h-10 rounded-xl shadow-md transition-all duration-500 ring-4 ring-white z-10"
                                        :class="[
                                            stepVisualStatus(stepKey) === 'complete' ? 'bg-green-500 text-white shadow-green-200' :
                                            stepVisualStatus(stepKey) === 'current' ? 'bg-amber-500 text-white shadow-amber-200 scale-105 animate-pulse-subtle' :
                                            stepVisualStatus(stepKey) === 'blocked' ? 'bg-red-500 text-white shadow-red-200' :
                                            'bg-white text-gray-400 border border-gray-200 shadow-none'
                                        ]"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ stepVisualStatus(stepKey) === 'complete' ? 'check' : stepIcon(stepKey) }}
                                        </span>
                                    </div>

                                    <!-- Content Card -->
                                    <div
                                        class="p-4 transition-all duration-300 rounded-xl border"
                                        :class="[
                                            stepVisualStatus(stepKey) === 'current' ? 'bg-white border-amber-200 shadow-lg shadow-amber-500/5' :
                                            stepVisualStatus(stepKey) === 'complete' ? 'bg-gray-50/50 border-gray-100 opacity-90' :
                                            'bg-white border-gray-50 opacity-60 grayscale-[0.5]'
                                        ]"
                                    >
                                        <div
                                            class="flex items-center justify-between cursor-pointer"
                                            @click="toggleCollapse(index)"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-[10px] font-black uppercase tracking-widest mb-0.5"
                                                    :class="[
                                                        stepVisualStatus(stepKey) === 'complete' ? 'text-green-500' :
                                                        stepVisualStatus(stepKey) === 'current' ? 'text-amber-600' : 'text-gray-400'
                                                    ]"
                                                >
                                                    Step {{ index + 1 }} • {{ stepVisualStatus(stepKey).toUpperCase() }}
                                                </span>
                                                <h4
                                                    class="text-base font-bold transition-colors"
                                                    :class="stepVisualStatus(stepKey) === 'current' ? 'text-gray-900' : 'text-gray-700'"
                                                >
                                                    {{ stepKey === 'submitted' ? 'Application Submitted' :
                                                       stepKey === 'review' ? 'Application Review' :
                                                       stepKey === 'assessment' ? 'Level Determination Test' :
                                                       stepKey === 'verification' ? 'Identity Verification' :
                                                       stepKey === 'course' ? 'Course & Session Selection' : 'Admission Confirmation' }}
                                                </h4>
                                            </div>
                                            <div
                                                v-if="isStepReached(index)"
                                                class="flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-100 transition-colors"
                                            >
                                                <span class="material-symbols-outlined text-gray-400 text-sm transition-transform" :class="collapse[index] ? 'rotate-180' : ''">expand_more</span>
                                            </div>
                                        </div>

                                        <div
                                            v-if="collapse[index] && isStepReached(index)"
                                            class="mt-4 pt-4 border-t border-gray-100/50 text-sm text-gray-600 leading-relaxed"
                                        >
                                            <!-- Step-specific content -->
                                            <div v-if="stepKey === 'submitted'">
                                                Your application was successfully received. We're excited to have you join our program!
                                            </div>

                                            <div v-if="stepKey === 'review'">
                                                <template v-if="reviewCompleted">
                                                    <p>You have reviewed the enrollment overview. You can still return to the overview if needed.</p>
                                                    <div class="mt-3">
                                                        <Link :href="route('student.application-review.index')" class="inline-flex items-center gap-2 text-green-600 font-bold hover:text-green-700 transition-colors">
                                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                            View Enrollment Overview
                                                        </Link>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p>Before you begin, please read through our detailed enrollment guide and program requirements.</p>
                                                    <div class="mt-3">
                                                        <LinkButton :href="route('student.application-review.index')" class="shadow-lg shadow-amber-500/20 px-4 py-1.5 text-sm">
                                                            Start Review
                                                        </LinkButton>
                                                    </div>
                                                </template>
                                            </div>

                                            <div v-if="stepKey === 'assessment'">
                                                <template v-if="assessmentCompleted">
                                                    <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg border border-green-100/50">
                                                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-green-600 shadow-sm">
                                                            <span class="material-symbols-outlined text-xl">trending_up</span>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-green-800">Assessment Complete</p>
                                                            <p v-if="usePage().props.config?.SHOW_STUDENT_LEVEL" class="text-base font-black text-green-900 uppercase">
                                                                LEVEL: {{ props.user.student_level }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p>This test helps us understand your current skills to place you in the right curriculum level.</p>
                                                    <div class="mt-3">
                                                        <LinkButton :href="route('student.level-assessment')" class="shadow-lg shadow-amber-500/20 px-4 py-1.5 text-sm">
                                                            Take Assessment Now
                                                        </LinkButton>
                                                    </div>
                                                </template>
                                            </div>

                                            <div v-if="stepKey === 'verification'">
                                                <template v-if="verificationCompleted">
                                                    <div class="flex items-center gap-2 text-green-700 font-semibold">
                                                        <span class="material-symbols-outlined text-base">verified</span>
                                                        Identity verified successfully with Ghana Card.
                                                    </div>
                                                </template>
                                                <template v-else-if="verificationBlocked">
                                                    <div class="p-3 bg-red-50 rounded-lg border border-red-100 flex items-start gap-2">
                                                        <span class="material-symbols-outlined text-red-600 text-sm">error</span>
                                                        <div>
                                                            <p class="font-bold text-red-900 text-sm">Verification Blocked</p>
                                                            <p class="text-xs text-red-700 mt-0.5">Please contact support for assistance in manually verifying your identity.</p>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p>We need to verify your identity to ensure a secure program environment.</p>
                                                    <div class="mt-3">
                                                        <LinkButton :href="route('student.verification.index')" class="shadow-lg shadow-amber-500/20 px-4 py-1.5 text-sm">
                                                            Start Verification
                                                        </LinkButton>
                                                    </div>
                                                </template>
                                            </div>

                                            <div v-if="stepKey === 'course'">
                                                <template v-if="courseChosen">
                                                    <div class="p-3 bg-white border border-gray-100 rounded-lg flex items-center justify-between">
                                                        <p class="font-medium text-gray-900">You've selected your course preference.</p>
                                                        <Link :href="route('student.change-course')" class="inline-flex items-center gap-1.5 text-amber-600 font-bold hover:text-amber-700 transition-colors">
                                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                                            Change Selection
                                                        </Link>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p>Now choose from our available courses and training sessions that match your level.</p>
                                                    <div class="mt-3">
                                                        <LinkButton :href="route('student.change-course')" class="shadow-lg shadow-amber-500/20 px-4 py-1.5 text-sm">
                                                            Choose Course
                                                        </LinkButton>
                                                    </div>
                                                </template>
                                            </div>

                                            <div v-if="stepKey === 'confirm'">
                                                <template v-if="admissionConfirmationComplete">
                                                    <p class="font-medium">Your admission record is finalized. You're all set!</p>
                                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                                        <RevokeOrDeclineAdmissionModal
                                                            v-if="props.user && props.user_admission"
                                                            :user="props.user"
                                                            :session="props.user_admission"
                                                        />
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p>Finalize your admission by confirming your booking details.</p>
                                                    <div class="mt-3">
                                                        <LinkButton :href="route('student.session.index')" class="shadow-lg shadow-amber-500/20 px-4 py-1.5 text-sm">
                                                            Confirm Booking
                                                        </LinkButton>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; transform: scale(1.1); }
    50% { opacity: 0.9; transform: scale(1.05); }
}
.animate-pulse-subtle {
    animation: pulse-subtle 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
