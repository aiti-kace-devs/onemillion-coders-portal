<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage } from "@inertiajs/vue3";
import SidebarSectionHeader from "@/Components/SidebarSectionHeader.vue";
import OnboardingNextStrip from "@/Components/Student/OnboardingNextStrip.vue";
import SidebarNavLink from "@/Components/SidebarNavLink.vue";

const isSidebarOpen = ref(false); // Mobile sidebar state
const isSidebarCollapsed = ref(true); // Desktop sidebar state

const toggleMobileSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeMobileSidebar = () => {
    isSidebarOpen.value = false;
};

const page = usePage();

const props = defineProps({
    fullHeight: {
        type: Boolean,
        default: false,
    },
    hideGradient: {
        type: Boolean,
        default: false,
    },
    /** Tighter top padding under header/onboarding strip (e.g. Application Status page). */
    compactContentTop: {
        type: Boolean,
        default: false,
    },
});

const auth = computed(() => page.props.auth ?? {});
const config = computed(() => page.props.config ?? {});
const user = computed(() => auth.value.user ?? {});

// Handle window resize to reset states if needed
const handleResize = () => {
    if (window.innerWidth >= 1024) {
        isSidebarOpen.value = false;
    }
};

const collapseSidebarOnContentInteraction = () => {
    if (window.innerWidth < 1024) {
        isSidebarOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div
        class="group/container min-h-screen flex bg-[#f8f9fa] relative overflow-x-hidden"
        :class="[
            isSidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-not-collapsed',
            isSidebarOpen ? 'mobile-sidebar-open' : 'mobile-sidebar-closed'
        ]"
    >
        <!-- Mobile Backdrop -->
        <div
            v-if="isSidebarOpen"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[1001] lg:hidden transition-opacity duration-300"
            @click="closeMobileSidebar"
        ></div>

        <!-- Sidebar -->
        <aside
            id="mobile-sidebar"
            class="fixed left-0 top-0 z-[1002] h-full bg-white border-r border-gray-200/80 shadow-xl lg:shadow-none transition-transform duration-300 ease-in-out
                   w-[280px] lg:w-64"
            :class="[
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0',
                isSidebarCollapsed ? 'lg:w-[70px]' : 'lg:w-64'
            ]"
            @mouseenter="isSidebarCollapsed = false"
            @mouseleave="isSidebarCollapsed = true"
        >
            <div class="h-full flex flex-col overflow-hidden">
                <!-- Sidebar Header -->
                <div class="h-16 flex items-center justify-between px-4 lg:group-[.sidebar-collapsed]/container:px-0 lg:group-[.sidebar-collapsed]/container:justify-center border-b border-gray-50 transition-all duration-300">
                    <Link
                        :href="route('student.dashboard')"
                        class="flex items-center gap-2 transition-all duration-300"
                        aria-label="Dashboard Home"
                    >
                        <ApplicationLogo
                            :src="
                                (isSidebarCollapsed && !isSidebarOpen)
                                    ? '/assets/images/logo-short.png'
                                    : '/assets/images/logo-bt.png'
                            "
                            class="h-10 transition-all duration-300"
                            :class="(isSidebarCollapsed && !isSidebarOpen) ? 'w-8' : 'w-auto'"
                        />
                    </Link>

                    <!-- Close button for mobile -->
                    <button
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                        @click="closeMobileSidebar"
                        aria-label="Close Sidebar"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 overflow-y-auto py-4 space-y-1 custom-scrollbar">
                    <SidebarNavLink
                        :active="route().current('student.dashboard')"
                        :href="route('student.dashboard')"
                        :label="'Dashboard'"
                    >
                        <span class="material-symbols-outlined">dashboard</span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        v-if="
                            !user.isAdmitted &&
                            !user.application_review_completed
                        "
                        :href="route('student.application-review.index')"
                        :active="
                            route().current('student.application-review.*')
                        "
                        :label="'Application review'"
                    >
                        <span class="material-symbols-outlined">menu_book</span>
                    </SidebarNavLink>

                    <!--           <SidebarNavLink
            :active="route().current('student.exam.index')"
            :href="route('student.exam.index')"
            :label="'Exam'"
          >
            <span class="material-symbols-outlined">quiz</span>
          </SidebarNavLink> -->

          <SidebarNavLink
            v-if="user.isAdmitted && config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS"
            :active="route().current('student.results')"
            :href="route('student.results')"
            :label="'Results'"
          >
            <span class="material-symbols-outlined">task</span>
          </SidebarNavLink>

                    <SidebarNavLink
                        :active="route().current('student.profile.edit')"
                        :href="route('student.profile.edit')"
                        :label="'Profile'"
                    >
                        <span class="material-symbols-outlined">user_attributes</span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        v-if="!user.isAdmitted && !user.shortlist && user.assessment_completed"
                        :href="route('student.change-course')"
                        :active="route().current('student.change-course')"
                        :label="user.registered_course ? 'Change Course' : 'Choose Course'"
                    >
                        <span class="material-symbols-outlined">swap_horiz</span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.application-status')"
                        :active="route().current('student.application-status')"
                        :label="'Application status'"
                    >
                        <span class="material-symbols-outlined">contract</span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.verification.index')"
                        :active="route().current('student.verification.*')"
                        :label="'Verification'"
                    >
                        <span class="material-symbols-outlined">verified_user</span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.course-history')"
                        :active="route().current('student.course-history')"
                        :label="'Course History'"
                    >
                        <span class="material-symbols-outlined">history</span>
                    </SidebarNavLink>

                    <template v-if="user.isAdmitted">
                        <SidebarNavLink
                            v-if="!user.isOnlineCourse"
                            :active="route().current('student.attendance.show')"
                            :href="route('student.attendance.show')"
                            :label="'Attendance'"
                        >
                            <span class="material-symbols-outlined">rule</span>
                        </SidebarNavLink>

                        <SidebarNavLink
                            v-if="config.SHOW_COURSE_ASSESSMENT_TO_STUDENTS"
                            :active="route().current('student.assessment.*')"
                            :href="route('student.assessment.index')"
                            :label="'Course Assessment'"
                        >
                            <span class="material-symbols-outlined">rate_review</span>
                        </SidebarNavLink>
                    </template>

                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <SidebarNavLink
                            :href="route('auth.logout')"
                            :label="'Log Out'"
                            :method="'post'"
                            :as="'button'"
                        >
                            <span class="material-symbols-outlined text-red-500">logout</span>
                        </SidebarNavLink>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div
            class="flex-1 flex flex-col min-h-0 md:ml-[70px] bg-[#f8f9fa] relative overflow-hidden"
            @click="collapseSidebarOnContentInteraction"
        >
            <!-- Background Accents -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#f9a825]/5 rounded-full blur-[100px] -mr-64 -mt-64 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#f9a825]/3 rounded-full blur-[80px] -ml-48 -mb-48 pointer-events-none"></div>

            <!-- Top Nav -->
            <header
                class="sticky top-0 h-16 bg-white/80 backdrop-blur-md flex items-center justify-between px-4 lg:px-8 border-b border-gray-200/80 z-40 transition-all duration-300"
            >
                <div class="flex items-center gap-4 min-w-0">
                    <button
                        class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-100 transition-all active:scale-95 relative z-50"
                        @click.stop="toggleMobileSidebar"
                        :aria-expanded="isSidebarOpen"
                        aria-controls="mobile-sidebar"
                        aria-label="Toggle Navigation"
                    >
                        <span class="material-symbols-outlined">{{ isSidebarOpen ? 'menu_open' : 'menu' }}</span>
                    </button>

                    <div v-if="$slots.header" class="truncate">
                        <div class="text-xl md:text-2xl font-bold tracking-tight text-gray-900 truncate">
                            <slot name="header" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <Link
                        :href="route('student.notifications.index')"
                        class="relative flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-100 text-gray-500 hover:text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition-all duration-200 shadow-sm group"
                        aria-label="Notifications"
                    >
                        <span class="material-symbols-outlined text-[22px] group-hover:scale-110 transition-transform">notifications</span>
                        <span
                            v-if="auth?.unreadNotifications > 0"
                            class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white shadow-sm ring-2 ring-white animate-pulse"
                        >
                            {{ auth.unreadNotifications > 99 ? "99+" : auth.unreadNotifications }}
                        </span>
                    </Link>
                </div>
            </header>

            <div
                v-if="!hideGradient"
                class="h-1 w-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 z-30 sticky top-16"
            ></div>

            <OnboardingNextStrip />

            <!-- Page content -->
            <main
                :class="
                    props.fullHeight
                        ? 'flex-1 flex min-h-0 flex-col overflow-hidden'
                        : props.compactContentTop
                          ? 'pt-1.5 pb-6 px-4 lg:px-8'
                          : 'py-6 px-4 lg:px-8'
                "
            >
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>

