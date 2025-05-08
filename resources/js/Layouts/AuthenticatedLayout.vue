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
    <div class="min-h-screen">
        <!-- header -->
        <div
            class="sticky h-[65px] top-0 z-[1001] flex justify-between items-center border-b border-gray-200 bg-white px-4 py-2.5 transition-all">
            <div class="flex items-center gap-x-6">
                <div class="flex items-center lg:gap-2">
                    <button
                        class="block cursor-pointer rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-100 focus:outline-none"
                        @click="toggleSidebar" aria-label="Toggle Sidebar" aria-expanded="isSidebarCollapsed">
                        <svg v-if="isSidebarCollapsed" class="h-6 w-6" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>

                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <Link :href="route('admin.dashboard')">
                    <!-- <ApplicationLogo class="hidden lg:block h-10 w-auto fill-current" /> -->
                    </Link>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- page heading  -->
                    <div class="max-w-full" v-if="$slots.header">
                        <div
                            class="overflow-hidden whitespace-nowrap text-ellipsis font-semibold text-lg text-gray-800 leading-tight">
                            <slot name="header" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 items-center">
                <!-- Settings Dropdown -->
                <!-- <div class="relative">
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
                :href="route('profile.edit')"
                class="inline-flex items-center"
              >
                <span class="material-symbols-outlined me-1"> person </span> Profile
              </DropdownLink>
              <DropdownLink
                :href="route('admin.logout')"
                method="post"
                as="button"
                class="inline-flex items-center"
              >
                <span class="material-symbols-outlined me-1"> logout </span>
                Log Out
              </DropdownLink>
            </template>
</Dropdown>
</div> -->
            </div>
        </div>

        <div class="group/container flex gap-4"
            :class="[isSidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-not-collapsed']">
            <!-- sidebar -->
            <div
                class="bg-white duration-300 ease-in-out fixed left-0 top-[60px] z-[1002] h-full w-full md:w-2/3 lg:w-64 border-gray-200 pt-4 transition-all group-[.sidebar-collapsed]/container:w-[70px] hidden group-[.sidebar-not-collapsed]/container:block lg:block ltr:border-r rtl:border-l">
                <div @mouseenter="isSidebarCollapsed = false" @mouseleave="isSidebarCollapsed = true"
                    class="h-[calc(100vh-100px)] overflow-y-auto group-[.sidebar-collapsed]/container:overflow-visible">
                    <!-- navigation links -->
                    <nav
                        class="flex flex-col justify-center mx-3 space-y-3 md:space-y-5 lg:group-[.sidebar-not-collapsed]/container:mx-4 lg:group-[.sidebar-collapsed]/container:space-y-2">
                        <div>
                            <!-- <SidebarNavLink
                :href="route('admin.dashboard')"
                :active="route().current('admin.dashboard')"
                :label="'overview'"
              >
                <span class="material-symbols-outlined">dashboard</span>
              </SidebarNavLink> -->
                            <SidebarNavLink :active="route().current('admin.dashboard')"
                                :href="route('admin.dashboard')" :label="'home'" :redirect="true">
                                <span class="material-symbols-outlined">home</span>
                            </SidebarNavLink>

                            <SidebarNavLink :href="route('admin.form.index')" :active="route().current('admin.form.*') ||
                                route().current('admin.form_responses.*')
                                " :label="'forms'">
                                <span class="material-symbols-outlined">ballot</span>
                            </SidebarNavLink>

                            <SidebarNavLink :href="route('admin.questionnaire.index')" :active="route().current('admin.questionnaire.*') ||
                                route().current('admin.questionnaire_responses.*')
                                " :label="'questionnaires'">
                                <span class="material-symbols-outlined">rate_review</span>
                            </SidebarNavLink>

                            <SidebarNavLink :href="route('admin.session.index')"
                                :active="route().current('admin.session.*')" :label="'sessions'">
                                <span class="material-symbols-outlined">schedule</span>
                            </SidebarNavLink>

                            <SidebarNavLink :href="route('admin.lists.index')" :active="route().current('admin.lists.*')
                                " :label="'lists'">
                                <span class="material-symbols-outlined">table</span>
                            </SidebarNavLink>
                        </div>

                        <div>
                            <SidebarNavLink :href="route('admin.logout')" :active="route().current('admin.logout')"
                                :label="'log out'" :redirect="true">
                                <span class="material-symbols-outlined"> logout </span>
                            </SidebarNavLink>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- main content -->
            <div class="flex-1 lg:ml-[70px] min-h-screen bg-gray-100">
                <!-- Page Content -->
                <main class="py-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
