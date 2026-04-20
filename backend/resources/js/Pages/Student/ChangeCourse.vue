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
    const baseUrl = quiz_frontend_url || "";
    const base = baseUrl.endsWith("/") ? baseUrl.slice(0, -1) : baseUrl;
    if (!base) {
        return "";
    }
    const token = quiz_jwt_token;
    const path = `${base}/courses/${user.userId}`;
    try {
        const u = new URL(path);
        if (token) {
            u.searchParams.set("token", token);
        }
        u.searchParams.set("embed", "true");
        return u.toString();
    } catch {
        const qs = new URLSearchParams();
        if (token) {
            qs.set("token", token);
        }
        qs.set("embed", "true");
        return `${path}?${qs.toString()}`;
    }
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
    const t = event.data?.type;
    if (t !== "omcp-in-person-enrolled" && t !== "omcp-student-enrollment-complete") {
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
      <div class="flex items-center gap-2">
        <h2 class="font-black text-2xl text-gray-900 tracking-tight">
          {{ pageTitle }}
        </h2>
      </div>
    </template>

    <div v-if="isShortlisted" class="py-12 px-4">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="max-w-xl">
            <h3 class="font-medium text-lg text-gray-900 border-b pb-2 mb-4">Registered Course</h3>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded text-blue-700">
                <p class="text-lg font-semibold">You are currently registered for:</p>
                <p class="text-2xl font-bold mt-1 text-blue-900">{{ currentCourse?.course_name }}</p>
                <p class="mt-4 text-sm italic">You have been shortlisted for this course. Your selection is now locked. If you need assistance, please contact support.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="relative w-full flex-1 min-h-[38vh]">
      <p
        v-if="!courseSelectionUrl"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
      >
        Course selection is not configured (missing quiz frontend URL). Set
        <code class="rounded bg-white/80 px-1">QUIZ_FRONTEND_URL</code>
        in the portal environment and reload.
      </p>
      <iframe
        v-else
        :src="courseSelectionUrl"
        class="absolute inset-0 h-full w-full border-0 bg-gray-100"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowfullscreen
      ></iframe>
    </div>
  </AuthenticatedLayout>
</template>
