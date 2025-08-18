<script setup>
import { computed, onMounted } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
  questionnaires: Object,
});

const page = usePage();
onMounted(() => {
  const flash = page.props.flash;
  if (flash) {
    if (flash.key === "success") {
      toastr.success(flash.message);
    } else if (flash.key === "error") {
      toastr.error(flash.message);
    } else if (flash.key === "info") {
      toastr.info(flash.message);
    } else if (flash.key === "warning") {
      toastr.warning(flash.message);
    } else if (flash.key === "message") {
      toastr.success(flash.message);
    }
  }
});

const user = computed(() => usePage().props.auth?.user || {});
</script>

<template>
  <Head title="Assessment" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assessment</h2>
    </template>

    <div class="pt-3">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <Link
            v-for="(questionnaire, key) in questionnaires"
            :key="questionnaire.id"
            :href="route('student.assessment.take-questionnaire', questionnaire.code)"
          >
            <div class="relative group bg-white rounded-xl shadow p-6 flex flex-col">
              <!-- Status badge -->
              <span
                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                :class="
                  questionnaire.is_submitted
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700'
                "
              >
                {{ questionnaire.is_submitted ? "Completed" : "Incomplete" }}
              </span>

              <!-- Icon and Title -->
              <div class="flex items-center gap-3 mb-2">
                <span
                  class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100"
                >
                  <span class="material-symbols-outlined text-gray-600">rate_review</span>
                </span>
                <div class="flex-1 text-left">
                  <h3 class="text-lg font-bold text-gray-800">
                    {{ questionnaire.title }}
                  </h3>
                </div>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped></style>
