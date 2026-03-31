<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
  courses: Array,
  user: Object,
  currentCourse: Object,
  flash: Object,
});

const isRegistered = computed(() => !!props.user.registered_course);
const isShortlisted = computed(() => !!props.user.shortlist);
const pageTitle = computed(() => {
    if (isShortlisted.value) return "Registered Course";
    return isRegistered.value ? "Change Course" : "Choose Course";
});

const courseSelectionUrl = computed(() => {
    const baseUrl = usePage().props.quiz_frontend_url || '';
    const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    const token = usePage().props.quiz_jwt_token;
    const path = `${base}/courses/${props.user.userId}`;
    return token ? `${path}?token=${token}` : path;
});
</script>

<template>
  <Head :title="pageTitle" />
  <AuthenticatedLayout :fullHeight="!isShortlisted">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ pageTitle }}</h2>
    </template>

    <div :class="isShortlisted ? 'py-12 px-4' : ''">
      <div :class="isShortlisted ? 'max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6' : ''">
        <!-- Case 1: Shortlisted - Show Read-Only -->
        <div v-if="isShortlisted" class="bg-white shadow rounded-lg p-6">
          <div class="max-w-xl">
            <h3 class="font-medium text-lg text-gray-900 border-b pb-2 mb-4">Registered Course</h3>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded text-blue-700">
                <p class="text-lg font-semibold">You are currently registered for:</p>
                <p class="text-2xl font-bold mt-1 text-blue-900">{{ currentCourse?.course_name }}</p>
                <p class="mt-4 text-sm italic">You have been shortlisted for this course. Your selection is now locked. If you need assistance, please contact support.</p>
            </div>
          </div>
        </div>

        <!-- Case 2: Not Shortlisted - Show Iframe (for both Choose and Change) -->
        <div v-else class="h-[calc(100vh-70px)] overflow-hidden relative">
            <div class="-mt-[70px]">
                <iframe
                    :src="courseSelectionUrl"
                    class="w-full h-[calc(100vh+6px)] border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
