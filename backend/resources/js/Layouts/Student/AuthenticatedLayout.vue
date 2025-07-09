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

const hardRedirect = (url, event) => {
  window.location.href = url;
  // event.stopPropagation();
  event.stopImmediatePropagation();
  return false;
};

// Get the current route name for active link highlighting
const { auth, component } = usePage().props;

const user = auth?.user || {};
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <div
      class="group/sidebar-container relative"
      @mouseenter="isSidebarCollapsed = false"
      @mouseleave="isSidebarCollapsed = true"
      @click.away="isSidebarCollapsed = true"
    >
      <aside
        class="fixed h-full bg-white border-r shadow-md flex flex-col transition-all duration-300 ease-in-out z-[1002] overflow-hidden"
        :class="{
          'w-0 lg:w-[70px]': isSidebarCollapsed,
          'w-2/3 lg:w-64': !isSidebarCollapsed,
        }"
        aria-label="Sidebar Navigation"
      >
        <!-- Logo -->
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
              class="h-16 transition-all duration-300 mx-auto group-hover/sidebar-container:ml-2"
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

        <!-- Navigation -->
        <nav
          class="flex-1 flex-col py-4 space-y-2 h-[calc(100vh-80px)] overflow-x-hidden overflow-y-auto"
          role="navigation"
        >
          <SidebarNavLink
            :active="route().current('student.profile.edit')"
            :href="route('student.profile.edit')"
            :label="'Profile'"
          >
            <span class="material-symbols-outlined">user_attributes</span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('student.session.index')"
            :active="route().current('student.session.*')"
            :label="'Session'"
          >
            <span class="material-symbols-outlined"> calendar_clock </span>
          </SidebarNavLink>

          <SidebarNavLink
            :active="route().current('student.profile.edit')"
            :href="route('student.profile.edit')"
            :label="'Profile'"
          >
            <span class="material-symbols-outlined">person</span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('admin.form.index')"
            :active="route().current('admin.form.*')"
            :label="'Forms'"
          >
            <span class="material-symbols-outlined">ballot</span>
          </SidebarNavLink>
        </nav>
      </aside>
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

        <div class="relative">
          <Dropdown align="right" width="48">
            <template #trigger>
              <span class="inline-flex rounded-md">
                <button
                  type="button"
                  class="inline-flex items-center p-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-transparent hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  <span class="material-symbols-outlined"> account_circle </span>

                  <svg
                    class="-me-0.5 h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <DropdownLink
                :href="route('student.profile.edit')"
                class="inline-flex items-center"
              >
                <span class="material-symbols-outlined me-1"> person </span> Profile
              </DropdownLink>
              <DropdownLink
                :href="route('logout')"
                method="post"
                as="button"
                class="inline-flex items-center"
              >
                <span class="material-symbols-outlined me-1"> logout </span>
                Log Out
              </DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <!-- Page content -->
      <main class="py-6 px-4 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>
