<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const flash = computed(() => page.props.flash ?? {});

const stepMeta = {
    application_review: {
        title: "Review your application journey",
        hint: "Open the guide before your assessment.",
        routeName: "student.application-review.index",
        cta: "Open review",
        icon: "menu_book",
    },
    assessment: {
        title: "Complete your level assessment",
        hint: "Places you at the right starting level.",
        routeName: "student.level-assessment",
        cta: "Assessment",
        icon: "psychology",
    },
    identity_verification: {
        title: "Verify your identity",
        hint: "Ghana Card unlocks course selection.",
        routeName: "student.verification.index",
        cta: "Verify",
        icon: "verified_user",
    },
    course_selection: {
        title: "Choose your course",
        hint: "Programme, centre, session, or waitlist.",
        routeName: "student.change-course",
        cta: "Choose course",
        icon: "swap_horiz",
    },
};

const current = computed(() => {
    const key = user.value?.current_onboarding_step;
    if (!key || !stepMeta[key]) return null;
    const meta = { key, ...stepMeta[key] };
    if (key === "course_selection") {
        meta.cta = user.value?.registered_course ? "Review" : "Choose course";
    }
    return meta;
});

const visible = computed(() => !!current.value);
const showFlash = computed(() => !!flash.value?.message);
</script>

<template>
    <div
        v-if="visible"
        class="border-b border-amber-100/90 bg-amber-50/90 backdrop-blur-sm"
        role="region"
        aria-label="Next enrollment step"
    >
        <div
            v-if="showFlash && flash.message"
            class="px-3 py-1.5 sm:px-4 text-center text-[11px] sm:text-xs font-medium text-amber-950 bg-amber-100/70 border-b border-amber-200/50 leading-snug"
        >
            {{ flash.message }}
        </div>

        <div
            class="max-w-7xl mx-auto px-3 py-1.5 sm:px-4 sm:py-2 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-3"
        >
            <div class="flex min-w-0 flex-1 items-start gap-2 sm:gap-2.5">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#f9a825]/20 text-[#a05f00] sm:h-10 sm:w-10"
                    aria-hidden="true"
                >
                    <span class="material-symbols-outlined text-[20px] sm:text-[22px]">{{
                        current.icon
                    }}</span>
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p
                        class="text-[10px] font-bold uppercase tracking-wide text-amber-900/70 sm:text-[11px]"
                    >
                        Next step
                    </p>
                    <p
                        class="text-sm font-semibold text-gray-900 leading-tight sm:text-[15px]"
                    >
                        {{ current.title }}
                    </p>
                    <p
                        class="text-[11px] text-gray-600 leading-snug mt-0.5 sm:text-xs line-clamp-2 sm:line-clamp-none"
                    >
                        {{ current.hint }}
                    </p>
                </div>
            </div>

            <div
                class="flex shrink-0 flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-end sm:gap-2"
            >
                <Link
                    :href="route(current.routeName)"
                    class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-[#f9a825] px-3 py-2 text-center text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-amber-600/10 transition hover:bg-amber-500 sm:w-auto sm:px-4 sm:py-2 sm:text-sm min-h-[44px] sm:min-h-0"
                >
                    {{ current.cta }}
                    <span class="material-symbols-outlined text-lg sm:text-xl" aria-hidden="true"
                        >arrow_forward</span
                    >
                </Link>
                <Link
                    :href="route('student.application-status')"
                    class="text-center text-[11px] font-medium text-gray-600 underline-offset-2 hover:text-gray-900 hover:underline sm:text-left sm:text-xs py-1 sm:py-0"
                >
                    Application status
                </Link>
            </div>
        </div>
    </div>
</template>
