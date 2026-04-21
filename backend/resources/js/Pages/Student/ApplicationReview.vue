<script setup>
import { computed, ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    application_review_embed_url: {
        type: String,
        default: null,
    },
    application_review_embed_available: {
        type: Boolean,
        default: false,
    },
    application_review_completed: {
        type: Boolean,
        default: false,
    },
    application_review_completed_at: {
        type: String,
        default: null,
    },
});

/** True when the server intends to embed an external page (flag on + non-empty src). */
function propsEmbedConfigured(p) {
    const url = p.application_review_embed_url;
    const hasUrl = typeof url === "string" && url.trim().length > 0;
    const flagged = Boolean(p.application_review_embed_available);
    return flagged && hasUrl;
}

/** True while the iframe is broken or blocked; set false again when embed config becomes valid. */
const iframeUnavailable = ref(!propsEmbedConfigured(props));

watch(
    () => [props.application_review_embed_available, props.application_review_embed_url],
    () => {
        if (propsEmbedConfigured(props)) {
            iframeUnavailable.value = false;
        }
    },
);

/** External review iframe is active: hide all generic in-portal copy (intro, amber steps, four-card grid). */
const showEmbeddedReview = computed(() => propsEmbedConfigured(props) && !iframeUnavailable.value);

/** In-portal fallback only when no working embedded review (missing URL, error, or blocked load). */
const showGenericFallbackChrome = computed(() => !showEmbeddedReview.value);

const showConfirmFooter = computed(() => !props.application_review_completed);

const completedAtLabel = computed(() => {
    if (!props.application_review_completed_at) {
        return "";
    }
    const d = new Date(props.application_review_completed_at);
    if (Number.isNaN(d.getTime())) {
        return "";
    }
    return ` on ${d.toLocaleString()}`;
});

const form = useForm({});

function handleIframeError() {
    iframeUnavailable.value = true;
}

function submitContinue() {
    form.post(route("student.application-review.complete"));
}
</script>

<template>
    <Head title="Application review" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{
                    application_review_completed
                        ? "Application review"
                        : "Review your application journey"
                }}
            </h2>
        </template>

        <div class="py-10">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div
                    v-if="application_review_completed"
                    class="rounded-lg border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900"
                    role="status"
                >
                    <span class="font-semibold">Step completed.</span>
                    You can return here for reference.
                    <span v-if="completedAtLabel" class="block mt-1 text-emerald-800/90"
                        >Recorded{{ completedAtLabel }}.</span
                    >
                </div>

                <p
                    v-if="showGenericFallbackChrome && !application_review_completed"
                    class="text-gray-600 text-sm sm:text-base leading-relaxed"
                >
                    Take a few minutes to understand how the student portal is organised—from this review
                    through assessment, identity checks, and course selection. When you are ready, confirm below
                    to move on; a compact next-step reminder under the header will point to your current task
                    until you are admitted or have finished selecting a course.
                </p>

                <div
                    v-if="showEmbeddedReview"
                    class="rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm"
                >
                    <iframe
                        :src="application_review_embed_url"
                        class="w-full border-0 bg-white"
                        style="min-height: 520px; height: 70vh"
                        referrerpolicy="strict-origin-when-cross-origin"
                        title="Application review"
                        @error="handleIframeError"
                    />
                </div>

                <div
                    v-else-if="!application_review_completed"
                    class="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50/80 to-white p-6 sm:p-8 shadow-sm motion-safe:animate-[fadeSlide_0.55s_ease-out]"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
                        <div
                            class="h-14 w-14 rounded-2xl bg-amber-400/30 flex items-center justify-center motion-safe:animate-[pulseSoft_2.5s_ease-in-out_infinite]"
                        >
                            <span class="material-symbols-outlined text-amber-800 text-3xl">menu_book</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Your enrollment path</h3>
                            <p class="text-sm text-gray-600 mt-1">Here is a quick overview of each step ahead.</p>
                        </div>
                    </div>

                    <ol class="space-y-4 text-sm text-gray-700">
                        <li class="flex gap-3 motion-safe:animate-[fadeSlide_0.55s_ease-out]">
                            <span
                                class="flex-none h-8 w-8 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-sm"
                                >1</span
                            >
                            <div>
                                <p class="font-semibold text-gray-900">Application submitted</p>
                                <p class="text-gray-600 mt-0.5">
                                    Your student account is active and your basic application details are on file.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3 motion-safe:animate-[fadeSlide_0.55s_ease-out]">
                            <span
                                class="flex-none h-8 w-8 rounded-full bg-amber-600 text-white font-bold flex items-center justify-center text-sm ring-2 ring-amber-200 ring-offset-2"
                                >2</span
                            >
                            <div>
                                <p class="font-semibold text-gray-900">Application review (this step)</p>
                                <p class="text-gray-600 mt-0.5">
                                    You read how the portal flows so you know what to expect before assessments and
                                    course selection.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3 motion-safe:animate-[fadeSlide_0.55s_ease-out]">
                            <span
                                class="flex-none h-8 w-8 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-sm"
                                >3</span
                            >
                            <div>
                                <p class="font-semibold text-gray-900">Level assessment</p>
                                <p class="text-gray-600 mt-0.5">
                                    A short, proctored-style check helps us place you at the right starting level for
                                    instruction.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3 motion-safe:animate-[fadeSlide_0.55s_ease-out]">
                            <span
                                class="flex-none h-8 w-8 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-sm"
                                >4</span
                            >
                            <div>
                                <p class="font-semibold text-gray-900">Identity verification</p>
                                <p class="text-gray-600 mt-0.5">
                                    Ghana Card verification confirms your identity and unlocks booking tools in the
                                    portal.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3 motion-safe:animate-[fadeSlide_0.55s_ease-out]">
                            <span
                                class="flex-none h-8 w-8 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-sm"
                                >5</span
                            >
                            <div>
                                <p class="font-semibold text-gray-900">Course selection</p>
                                <p class="text-gray-600 mt-0.5">
                                    You choose a programme path, centre, and session (or join a waitlist) using the
                                    guided course picker—see the section below for how each path works.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>

                <section
                    v-if="showGenericFallbackChrome"
                    class="rounded-xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm space-y-5"
                    :class="
                        application_review_completed
                            ? 'opacity-95 pointer-events-none select-none'
                            : ''
                    "
                    aria-labelledby="enrolment-paths-heading"
                >
                    <div
                        v-if="application_review_completed"
                        class="text-xs font-semibold uppercase tracking-wide text-gray-500 -mt-1 mb-2"
                    >
                        Reference — read only
                    </div>
                    <div>
                        <h3 id="enrolment-paths-heading" class="text-lg font-semibold text-gray-900">
                            Course selection: how each enrolment path works
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            The course picker may offer different outcomes depending on programme design and seat
                            availability. Here is what each path usually involves in this portal.
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-lg border border-amber-100 bg-amber-50/50 p-4 motion-safe:animate-[fadeSlide_0.55s_ease-out]"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-amber-800">groups</span>
                                <h4 class="font-semibold text-gray-900 text-sm">In-person cohort enrolment</h4>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                You browse available programmes, pick a training centre, then choose a cohort or session
                                with open seats. When you complete booking, your place is tied to that centre and
                                intake; session details and next actions show up on your dashboard and application
                                status.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-sky-100 bg-sky-50/50 p-4 motion-safe:animate-[fadeSlide_0.55s_ease-out]"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-sky-800">wifi_tethering</span>
                                <h4 class="font-semibold text-gray-900 text-sm">Online or blended (when offered)</h4>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Some programmes may be delivered fully online or as a blend of online work and centre
                                days. The picker will show what applies to each programme. Identity verification is
                                still required before you can confirm a seat, so your record matches your Ghana Card.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-amber-100 bg-amber-50/50 p-4 motion-safe:animate-[fadeSlide_0.55s_ease-out]"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-amber-800">hourglass_top</span>
                                <h4 class="font-semibold text-gray-900 text-sm">Waitlist</h4>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                If your preferred cohort is full, you can often join a waitlist instead of abandoning
                                your choice. You keep a fair position in line; when a seat opens, the team or system will
                                guide you to complete enrolment. Your dashboard will show waitlist status when this
                                applies.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-emerald-100 bg-emerald-50/50 p-4 motion-safe:animate-[fadeSlide_0.55s_ease-out]"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-emerald-800">verified</span>
                                <h4 class="font-semibold text-gray-900 text-sm">Shortlist &amp; locked selection</h4>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                After you choose a course, you may be shortlisted while admissions are finalised. In that
                                state your selection can be read-only in the portal to avoid conflicting bookings. If
                                you need a change, use official support channels listed on the main site.
                            </p>
                        </div>
                    </div>
                </section>

                <div
                    v-if="showConfirmFooter"
                    class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between"
                >
                    <p class="text-xs text-gray-500">
                        By continuing, you confirm you have reviewed this information.
                    </p>
                    <button
                        type="button"
                        class="inline-flex justify-center items-center px-5 py-2.5 rounded-lg font-semibold text-gray-900 bg-[#f9a825] hover:bg-[#e09621] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f9a825] disabled:opacity-50"
                        :disabled="form.processing"
                        @click="submitContinue"
                    >
                        I've reviewed — continue
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeSlide {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes pulseSoft {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.04);
    }
}
@media (prefers-reduced-motion: reduce) {
    .motion-safe\:animate-\[fadeSlide_0\.55s_ease-out\] {
        animation: none;
    }
    .motion-safe\:animate-\[pulseSoft_2\.5s_ease-in-out_infinite\] {
        animation: none;
    }
}
</style>
