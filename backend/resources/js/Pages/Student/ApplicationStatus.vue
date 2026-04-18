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

const collapse = ref([true, false, false, false]);
const showRevokeModal = ref(false);

function toggleCollapse(idx) {
    if (isStepReached(idx)) {
        collapse.value[idx] = !collapse.value[idx];
    }
}

function isStepReached(idx) {
    if (idx === 0) return true; // Step 1: Always reached
    if (idx === 1) return true; // Step 2: Assessment (Follows app submission)
    if (idx === 2) return props.user_assessment?.completed; // Step 3: Identity Verification
    if (idx === 3) return verificationCompleted.value; // Step 4: Course Selection (includes booking)
    return false;
}
function closeRevokeModal() {
    showRevokeModal.value = false;
}

const courseSelectionCompleted = computed(
    () => !!props.user_admission?.confirmed || !!props.user?.registered_course,
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

/** Step 4 milestone: with a separate confirmation step, "course selection" ends at course/admission choice. */
const courseSelectionMilestoneMet = computed(() => {
    if (!showConfirmationInFlow.value) {
        return courseSelectionCompleted.value;
    }
    return courseChosen.value;
});

const admissionConfirmationComplete = computed(
    () => !!props.user_admission?.confirmed,
);

function stepVisualStatus(stepKey) {
    if (stepKey === "submitted") {
        /* Signed-in students have already registered; aligns with timeline step 1. */
        return "complete";
    }
    if (stepKey === "assessment") {
        if (assessmentCompleted.value) return "complete";
        return "current";
    }
    if (stepKey === "verification") {
        if (verificationBlocked.value) return "blocked";
        if (verificationCompleted.value) return "complete";
        if (assessmentCompleted.value) return "current";
        return "upcoming";
    }
    if (stepKey === "course") {
        if (courseSelectionMilestoneMet.value) return "complete";
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

function stepCardClass(stepKey) {
    const s = stepVisualStatus(stepKey);
    if (s === "complete") {
        return "border-green-200 bg-green-50/90 ring-1 ring-green-100";
    }
    if (s === "current") {
        return "border-amber-300 bg-amber-50/90 ring-1 ring-amber-100";
    }
    if (s === "blocked") {
        return "border-red-200 bg-red-50/90 ring-1 ring-red-100";
    }
    return "border-gray-200 bg-gray-50/80";
}

function stepIconClass(stepKey) {
    const s = stepVisualStatus(stepKey);
    if (s === "complete") return "bg-green-500 text-white";
    if (s === "current") return "bg-amber-500 text-white";
    if (s === "blocked") return "bg-red-500 text-white";
    return "bg-gray-200 text-gray-500";
}

const illustratedFlowSteps = computed(() => {
    const base = [
        {
            key: "submitted",
            n: 1,
            label: "Application Submitted",
            hint: "Registration — your student account is active.",
            icon: "mark_email_read",
        },
        {
            key: "assessment",
            n: 2,
            label: "Assessment",
            hint: "Level determination test.",
            icon: "psychology",
        },
        {
            key: "verification",
            n: 3,
            label: "Identity Verification",
            hint: "Ghana Card check.",
            icon: "verified_user",
        },
        {
            key: "course",
            n: 4,
            label: "Course Selection",
            hint: "Choose course & session.",
            icon: "school",
        },
    ];
    if (showConfirmationInFlow.value) {
        base.push({
            key: "confirm",
            n: 5,
            label: "Application Confirmation",
            hint: "Finalize admission after booking.",
            icon: "task_alt",
        });
    }
    return base;
});
</script>

<template>
    <Head title="Application Status" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Application Status
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div
                    v-if="
                        props.user_admission && props.user_admission.confirmed
                    "
                    class="p-6 bg-white shadow sm:rounded-lg"
                >
                    <div
                        class="inline-flex space-x-2 items-center text-green-600 font-semibold text-lg"
                    >
                        <span class="material-symbols-outlined">
                            check_circle
                        </span>
                        <span class="font-semibold"
                            >Congratulations, {{ user.name }}! You have been
                            admitted.</span
                        >
                    </div>
                </div>

                <div class="p-6 bg-white sm:rounded-lg shadow">
                    <p class="font-medium text-lg text-gray-900 mb-1">
                        Expected Flow
                    </p>
                    <p class="text-sm text-gray-500 mb-5">
                        Same order and numbering as the Application Status steps below (step 1 is registration; you would not have this portal account without signing up).
                        {{
                            showConfirmationInFlow
                                ? " This path includes a fifth step once an admission record exists: confirmation after booking."
                                : " A fifth confirmation step appears after your admission record is created, if applicable."
                        }}
                    </p>

                    <div
                        class="grid gap-3 mb-8"
                        :class="
                            showConfirmationInFlow
                                ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-5'
                                : 'grid-cols-2 md:grid-cols-4'
                        "
                    >
                        <div
                            v-for="step in illustratedFlowSteps"
                            :key="step.key"
                            class="rounded-xl border p-4 flex flex-col items-center text-center transition-shadow shadow-sm hover:shadow-md min-h-[148px]"
                            :class="stepCardClass(step.key)"
                        >
                            <span
                                :class="stepIconClass(step.key)"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-full mb-3"
                            >
                                <span
                                    class="material-symbols-outlined text-[22px] leading-none"
                                    >{{ step.icon }}</span
                                >
                            </span>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5"
                            >
                                Step {{ step.n }}
                            </p>
                            <p
                                class="font-semibold text-sm text-gray-900 leading-snug"
                            >
                                {{ step.label }}
                            </p>
                            <p
                                class="text-xs text-gray-600 mt-1.5 leading-snug"
                            >
                                {{ step.hint }}
                            </p>
                        </div>
                    </div>

                    <p class="font-medium text-lg text-gray-900">
                        Application Status
                    </p>

                    <div class="mt-5 px-5 max-w-2xl">
                        <ol class="relative border-l border-green-500/30">
                            <!-- Step 1: Application Submitted -->
                            <li class="mb-10 ml-6">
                                <span
                                    class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white font-bold"
                                    >1</span
                                >
                                <div
                                    class="flex items-center cursor-pointer"
                                    @click="toggleCollapse(0)"
                                >
                                    <h3 class="font-bold text-lg text-gray-800">
                                        Application Submitted
                                    </h3>
                                    <svg
                                        :class="[
                                            'ml-2 w-4 h-4 text-gray-800 transition-transform',
                                            collapse[0] ? 'rotate-90' : '',
                                        ]"
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
                                <div
                                    v-if="collapse[0]"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    We've received your application! Our team is
                                    now ready to guide you through the following
                                    steps of your journey.
                                </div>
                            </li>

                            <!-- Step 2: Assessment -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                                        props.user_assessment?.completed
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-200 text-gray-400',
                                    ]"
                                    >2</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(1)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(1)"
                                >
                                    <h3
                                        :class="[
                                            'font-bold text-lg',
                                            props.user_assessment?.completed
                                                ? 'text-gray-800'
                                                : isStepReached(1)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Assessment
                                    </h3>
                                    <svg
                                        v-if="isStepReached(1)"
                                        :class="[
                                            'ml-2 w-4 h-4 text-gray-800 transition-transform',
                                            collapse[1] ? 'rotate-90' : '',
                                        ]"
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
                                <div
                                    v-if="collapse[1] && isStepReached(1)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template
                                        v-if="props.user_assessment?.completed"
                                    >
                                        Great job! Your assessment is complete
                                        and we've determined your starting
                                        level.
                                        <span
                                            v-if="
                                                usePage().props.config
                                                    ?.SHOW_STUDENT_LEVEL
                                            "
                                        >
                                            Your current level is:
                                            <span
                                                class="font-bold uppercase text-indigo-600"
                                                >{{
                                                    props.user.student_level
                                                }}</span
                                            ></span
                                        >
                                    </template>
                                    <template v-else>
                                        This assessment helps us understand your
                                        current skills so we can place you in
                                        the right course level. Please complete
                                        it to move forward.

                                        <div class="mt-5">
                                            <LinkButton
                                                :href="
                                                    route(
                                                        'student.level-assessment',
                                                    )
                                                "
                                            >
                                                Take assessment now
                                            </LinkButton>
                                        </div>
                                    </template>
                                </div>
                            </li>

                            <!-- Step 3: Identity Verification -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                                        verificationCompleted
                                            ? 'bg-green-500 text-white'
                                            : verificationBlocked
                                              ? 'bg-red-500 text-white'
                                            : 'bg-gray-200 text-gray-400',
                                    ]"
                                    >3</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(2)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(2)"
                                >
                                    <h3
                                        :class="[
                                            'font-bold text-lg',
                                            verificationCompleted
                                                ? 'text-gray-800'
                                                : isStepReached(2)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Identity Verification
                                    </h3>
                                    <svg
                                        v-if="isStepReached(2)"
                                        :class="[
                                            'ml-2 w-4 h-4 text-gray-800 transition-transform',
                                            collapse[2] ? 'rotate-90' : '',
                                        ]"
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
                                <div
                                    v-if="collapse[2] && isStepReached(2)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template v-if="verificationCompleted">
                                        Your Ghana Card verification is complete.
                                        You can proceed to course selection.
                                    </template>
                                    <template v-else-if="verificationBlocked">
                                        Verification attempts are blocked. Please
                                        contact support for assistance.
                                    </template>
                                    <template v-else>
                                        Complete your Ghana Card verification
                                        before selecting a course.

                                        <div class="mt-5">
                                            <LinkButton :href="route('student.verification.index')">
                                                Verify now
                                            </LinkButton>
                                        </div>
                                    </template>
                                </div>
                            </li>

                            <!-- Step 4: Course Selection (includes booking) -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                                        courseSelectionCompleted
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-200 text-gray-400',
                                    ]"
                                    >4</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(3)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(3)"
                                >
                                    <h3
                                        :class="[
                                            'font-bold text-lg',
                                            courseSelectionCompleted
                                                ? 'text-gray-800'
                                                : isStepReached(3)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Course Selection
                                    </h3>
                                    <svg
                                        v-if="isStepReached(3)"
                                        :class="[
                                            'ml-2 w-4 h-4 text-gray-800 transition-transform',
                                            collapse[3] ? 'rotate-90' : '',
                                        ]"
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
                                <div
                                    v-if="collapse[3] && isStepReached(3)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template v-if="props.user_admission?.confirmed">
                                        You have successfully selected your course and completed booking.
                                        <div class="mt-5">
                                            <RevokeOrDeclineAdmissionModal
                                                v-if="
                                                    props.user &&
                                                    props.user_admission
                                                "
                                                :user="props.user"
                                                :session="props.user_admission"
                                            />
                                        </div>
                                    </template>
                                    <template v-else-if="props.user?.registered_course">
                                        You selected a course. Complete session selection to finish your booking.
                                        <div class="mt-5">
                                            <LinkButton :href="route('student.session.index')">
                                                Choose a session
                                            </LinkButton>
                                        </div>
                                    </template>
                                    <template v-else>
                                        Now that your identity verification is complete,
                                        please select the course that best
                                        aligns with your interests and career goals. Session booking is part of this step.
                                        <div class="mt-5">
                                            <LinkButton :href="route('student.change-course')">
                                                Choose a course
                                            </LinkButton>
                                        </div>
                                    </template>
                                </div>
                            </li>

                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
