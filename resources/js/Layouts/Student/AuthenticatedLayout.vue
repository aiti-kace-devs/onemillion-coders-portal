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
const { component } = usePage().props;
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
        class="fixed hidden lg:block h-full bg-white border-r shadow-md flex flex-col duration-300 ease-in-out z-[1002] w-[70px] group-hover/sidebar-container:w-2/3 lg:group-hover/sidebar-container:w-64 group-hover/sidebar-container:block"
      >
        <div
          class="p-2 lg:py-2 lg:px-0 w-full flex justify-between items-start lg:flex-none"
        >
          <div class="w-full">
            <Link :href="route('admin.dashboard')">
              <ApplicationLogo
                :src="
                  isSidebarCollapsed
                    ? '/assets/images/logo-short.png'
                    : '/assets/images/logo-bt.png'
                "
                class="h-16 mx-auto group-hover/sidebar-container:ml-2 transition-all duration-300"
              />
            </Link>
          </div>

          <div class="lg:hidden">
            <button
              class="block cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-none"
              @click="isSidebarCollapsed = true"
              aria-label="Toggle Sidebar"
              aria-expanded="isSidebarCollapsed"
            >
              <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
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
        </div>

        <nav
          class="flex-1 flex-col py-2 space-y-3 h-[calc(100vh-100px)] overflow-x-hidden overflow-y-auto"
        >
          <SidebarNavLink
            :active="route().current('student.profile.edit')"
            :href="route('admin.dashboard')"
            :label="'home'"
            :redirect="true"
          >
            <span class="material-symbols-outlined">home</span>
          </SidebarNavLink>

          <SidebarNavLink
            :active="route().current('admin.dashboard')"
            :href="route('admin.dashboard')"
            :label="'home'"
            :redirect="true"
          >
            <span class="material-symbols-outlined">home</span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('admin.form.index')"
            :active="
              route().current('admin.form.*') || route().current('admin.form_responses.*')
            "
            :label="'forms'"
          >
            <span class="material-symbols-outlined">ballot</span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('admin.session.index')"
            :active="route().current('admin.session.*')"
            :label="'sessions'"
          >
            <span class="material-symbols-outlined">schedule</span>
          </SidebarNavLink>

          <SidebarNavLink
            :href="route('admin.lists.index')"
            :active="route().current('admin.lists.*')"
            :label="'lists'"
          >
            <span class="material-symbols-outlined">table</span>
          </SidebarNavLink>
        </nav>
      </aside>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col md:ml-[70px] bg-gray-100">
      <!-- Top Nav -->
      <header
        class="sticky top-0 h-16 bg-white flex items-center justify-between px-2 lg:px-6 shadow-sm"
      >
        <div class="flex items-center gap-x-3">
          <button
            class="block lg:hidden cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-none"
            @click="isSidebarCollapsed = false"
            aria-label="Toggle Sidebar"
            aria-expanded="isSidebarCollapsed"
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
      </header>

      <!-- Page content -->
      <main class="py-6">
        <slot />
      </main>
    </div>
  </div>
</template>
