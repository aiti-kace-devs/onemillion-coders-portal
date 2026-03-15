<script setup>
import { ref, reactive, computed } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

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

function submit() {
  // ... (keep if needed for manual change, but user said "dont touch anything on the react front end")
}

const courseSelectionUrl = computed(() => {
    const baseUrl = usePage().props.quiz_frontend_url || '';
    const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    return `${base}/courses/${props.user.userId}`;
});
</script>

<template>
  <Head :title="pageTitle" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ pageTitle }}</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
        <div v-else class="bg-white shadow rounded-lg overflow-hidden border border-gray-100">
             <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-medium text-lg text-gray-900">{{ pageTitle }}</h3>
                <span v-if="!isRegistered" class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-full uppercase tracking-wider">Required</span>
                <span v-else class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full uppercase tracking-wider">Active</span>
            </div>
            <div class="relative w-full" style="height: 800px;">
                <iframe
                    :src="courseSelectionUrl"
                    class="absolute top-0 left-0 w-full h-full border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
