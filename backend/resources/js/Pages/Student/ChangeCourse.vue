<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const { auth, quiz_frontend_url, quiz_jwt_token } = usePage().props;
const user = auth.user;

const props = defineProps({
  currentCourse: Object,
  flash: Object,
});

const isRegistered = computed(() => !!user.registered_course);
const isShortlisted = computed(() => !!user.shortlist);
const pageTitle = computed(() => {
    if (isShortlisted.value) return "Registered Course";
    return isRegistered.value ? "Change Course" : "Choose Course";
});

const courseSelectionUrl = computed(() => {
    const baseUrl = quiz_frontend_url || '';
    const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    const token = quiz_jwt_token;
    const path = `${base}/courses/${user.userId}`;
    return token ? `${path}?token=${token}` : path;
});

const allowedCoursePickerOrigins = computed(() => {
    const raw = quiz_frontend_url || "";
    if (!raw) {
        return [];
    }
    try {
        return [new URL(raw).origin];
    } catch {
        return [];
    }
});

function onCoursePickerMessage(event) {
    const messageType = event.data?.type;
    if (
        messageType !== "omcp-in-person-enrolled" &&
        messageType !== "omcp-student-enrolled"
    ) {
        return;
    }
    const redirectUrl = event.data.redirectUrl;
    if (typeof redirectUrl !== "string" || !redirectUrl) {
        return;
    }
    let redirectOrigin = "";
    try {
        redirectOrigin = new URL(redirectUrl).origin;
    } catch {
        return;
    }
    if (redirectOrigin !== window.location.origin) {
        return;
    }
    const allowed = allowedCoursePickerOrigins.value;
    const originOk =
        allowed.length > 0
            ? allowed.includes(event.origin)
            : event.origin === window.location.origin;
    if (!originOk) {
        return;
    }
    window.location.assign(redirectUrl);
}

onMounted(() => {
    window.addEventListener("message", onCoursePickerMessage);
});

onUnmounted(() => {
    window.removeEventListener("message", onCoursePickerMessage);
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
