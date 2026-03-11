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
    : "block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
);

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// Get the current route name for active link highlighting
const { auth, component } = usePage().props;

const user = auth?.user || {};
</script>

<template>
  <div
    class="group/container min-h-screen flex gap-4"
    :class="isSidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-not-collapsed'"
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
          <Link :href="route('student.dashboard')" aria-label="Dashboard Home">
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
            :label="'Overview'"
          >
            <span class="material-symbols-outlined">dashboard</span>
          </SidebarNavLink>

          <SidebarNavLink
            :active="route().current('student.exam.index')"
            :href="route('student.exam.index')"
            :label="'Exam'"
          >
            <span class="material-symbols-outlined">quiz</span>
          </SidebarNavLink>

          <SidebarNavLink
            v-if="user.isAdmitted"
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
            v-if="user.hasAdmission"
            :href="route('student.session.index')"
            :active="route().current('student.session.*')"
            :label="'Session'"
          >
            <span class="material-symbols-outlined"> calendar_clock </span>
          </SidebarNavLink>

          <SidebarNavLink
            v-if="!user.isAdmitted"
            :href="route('student.change-course')"
            :active="route().current('student.change-course')"
            :label="'Change Course'"
          >
            <span class="material-symbols-outlined"> swap_horiz </span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('student.application-status')"
            :active="route().current('student.application-status')"
            :label="'Application status'"
          >
            <span class="material-symbols-outlined"> contract </span>
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
              :active="route().current('student.assessment.*')"
              :href="route('student.assessment.index')"
              :label="'Course Assessment'"
            >
              <span class="material-symbols-outlined">rate_review</span>
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
    <div class="flex-1 flex flex-col md:ml-[70px] bg-gray-100">
      <!-- Top Nav -->
      <header
        class="sticky top-0 h-16 bg-white flex items-center justify-between px-4 lg:px-8 shadow-sm z-10"
        role="banner"
      >
        <div class="flex items-center gap-x-3">
          <button
            class="block lg:hidden cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-none"
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
            class="overflow-hidden whitespace-nowrap text-ellipsis font-semibold text-lg text-gray-800 leading-tight"
          >
            <slot name="header" />
          </div>
        </div>

        <!-- Notification Bell -->
        <Link
          :href="route('student.notifications.index')"
          class="relative p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors duration-200"
          aria-label="Notifications"
        >
          <span class="material-symbols-outlined text-[22px]">notifications</span>
          <span
            v-if="auth?.unreadNotifications > 0"
            class="absolute top-1 right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium text-white"
          >
            {{ auth.unreadNotifications > 99 ? '99+' : auth.unreadNotifications }}
          </span>
        </Link>

      </header>

      <!-- Page content -->
      <main class="py-6 px-4 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>
