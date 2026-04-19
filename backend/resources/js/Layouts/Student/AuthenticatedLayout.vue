<script setup>
import { ref, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import SidebarNavLink from "@/Components/SidebarNavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";
import SidebarSectionHeader from "@/Components/SidebarSectionHeader.vue";

const showingNavigationDropdown = ref(false);
const isSidebarCollapsed = ref(true);
const sidebarNavIcon = computed(() =>
    isSidebarCollapsed.value
        ? "block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 bg-gray-100 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
        : "block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out",
);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const collapseSidebarOnContentInteraction = () => {
    // For desktop and mobile, collapse sidebar when users interact with page body.
    isSidebarCollapsed.value = true;
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
});

const auth = computed(() => page.props.auth ?? {});
const config = computed(() => page.props.config ?? {});
const user = computed(() => auth.value.user ?? {});

</script>

<template>
    <div
        class="group/container min-h-screen flex gap-4"
        :class="
            isSidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-not-collapsed'
        "
    >
        <!-- Sidebar -->
        <div
            class="fixed z-[1002] h-full w-2/3 lg:w-64 border-gray-200 bg-white duration-300 transition-transform ease-in-out lg:translate-x-0 group-[.sidebar-collapsed]/container:w-[70px] border-r shadow-md"
            :class="
                isSidebarCollapsed
                    ? '-translate-x-full max-lg:block'
                    : 'translate-x-0 max-lg:block'
            "
            @mouseenter="isSidebarCollapsed = false"
            @mouseleave="isSidebarCollapsed = true"
            @click.away="isSidebarCollapsed = true"
        >
            <div
                class="h-[calc(100vh-100px)] overflow-hidden group-[.sidebar-collapsed]/container:overflow-visible"
            >
                <div
                    class="p-2 lg:py-2 lg:px-0 flex items-start justify-between lg:flex-none w-full"
                >
                    <Link
                        :href="route('student.dashboard')"
                        aria-label="Dashboard Home"
                    >
                        <ApplicationLogo
                            :src="
                                isSidebarCollapsed
                                    ? '/assets/images/logo-short.png'
                                    : '/assets/images/logo-bt.png'
                            "
                            class="h-16 transition-all duration-300 mx-auto group-[.sidebar-not-collapsed]/container:ml-2"
                        />
                    </Link>
                    <!-- Close button for sidebar (visible on small screens) -->
                    <button
                        class="block cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-none lg:hidden"
                        @click="isSidebarCollapsed = true"
                        aria-label="Close Sidebar"
                        type="button"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <nav class="mt-3 grid w-full space-y-2">
                    <SidebarNavLink
                        :active="route().current('student.dashboard')"
                        :href="route('student.dashboard')"
                        :label="'Dashboard'"
                    >
                        <span class="material-symbols-outlined">dashboard</span>
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
                        <span class="material-symbols-outlined"
                            >user_attributes</span
                        >
                    </SidebarNavLink>


                    <SidebarNavLink
                        v-if="
                            !user.isAdmitted &&
                            !user.shortlist &&
                            user.assessment_completed
                        "
                        :href="route('student.change-course')"
                        :active="route().current('student.change-course')"
                        :label="
                            user.registered_course
                                ? 'Change Course'
                                : 'Choose Course'
                        "
                    >
                        <span class="material-symbols-outlined">
                            swap_horiz
                        </span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.application-status')"
                        :active="route().current('student.application-status')"
                        :label="'Application status'"
                    >
                        <span class="material-symbols-outlined">
                            contract
                        </span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.verification.index')"
                        :active="route().current('student.verification.*')"
                        :label="'Verification'"
                    >
                        <span class="material-symbols-outlined">
                            verified_user
                        </span>
                    </SidebarNavLink>

                    <SidebarNavLink
                        :href="route('student.course-history')"
                        :active="route().current('student.course-history')"
                        :label="'Course History'"
                    >
                        <span class="material-symbols-outlined">
                            history
                        </span>
                    </SidebarNavLink>

                    <template v-if="user.isAdmitted">
                        <SidebarNavLink
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
                            <span class="material-symbols-outlined"
                                >rate_review</span
                            >
                        </SidebarNavLink>
                    </template>

                    <SidebarNavLink
                        :href="route('auth.logout')"
                        :label="'Log Out'"
                        :method="'post'"
                        :as="'button'"
                    >
                        <span class="material-symbols-outlined">logout</span>
                    </SidebarNavLink>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div
            class="flex-1 flex flex-col md:ml-[70px] bg-[#f8f9fa] relative overflow-hidden"
            @click="collapseSidebarOnContentInteraction"
        >
            <!-- Background Accents -->
            <div
                class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#f9a825]/5 rounded-full blur-[100px] -mr-64 -mt-64 pointer-events-none"
            ></div>
            <div
                class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#f9a825]/3 rounded-full blur-[80px] -ml-48 -mb-48 pointer-events-none"
            ></div>

            <!-- Top Nav -->
            <header
                class="sticky top-0 h-16 bg-white/90 backdrop-blur-lg flex items-center justify-between px-6 lg:px-8 border-b border-gray-200/80 z-50 transition-all duration-300 shadow-sm"
                role="banner"
            >
                <div class="flex items-center gap-x-4">
                    <button
                        class="block lg:hidden cursor-pointer rounded-lg p-2 hover:bg-gray-100 text-gray-500 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500"
                        @click="isSidebarCollapsed = false"
                        aria-label="Open Sidebar"
                        :aria-expanded="!isSidebarCollapsed"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            ></path>
                        </svg>
                    </button>

                    <div
                        v-if="$slots.header"
                        class="overflow-hidden whitespace-nowrap text-ellipsis"
                    >
                        <!-- Give context to the slot title -->
                        <div class="text-xl md:text-2xl font-bold tracking-tight text-gray-900 drop-shadow-sm">
                            <slot name="header" />
                        </div>
                    </div>
                </div>

                <!-- Notification Bell -->
                <Link
                    :href="route('student.notifications.index')"
                    class="relative p-2.5 rounded-full text-gray-500 hover:text-amber-600 hover:bg-amber-50 bg-gray-50 border border-gray-200 transition-all duration-200 shadow-sm"
                    aria-label="Notifications"
                >
                    <span class="material-symbols-outlined text-[20px] block"
                        >notifications</span
                    >
                    <span
                        v-if="auth?.unreadNotifications > 0"
                        class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white shadow-sm ring-2 ring-white"
                    >
                        {{
                            auth.unreadNotifications > 99
                                ? "99+"
                                : auth.unreadNotifications
                        }}
                    </span>
                </Link>
            </header>

            <div
                v-if="!hideGradient"
                class="h-1.5 w-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 z-40 sticky top-16"
            ></div>
            <!-- Page content -->
            <main :class="props.fullHeight ? '' : 'py-8 px-4 lg:px-8 max-w-7xl mx-auto w-full'">
                <slot />
            </main>
        </div>
    </div>
</template>
