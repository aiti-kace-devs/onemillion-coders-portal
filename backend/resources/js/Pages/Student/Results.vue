<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Student/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
  results: Array,
});

function getStatus(result) {
  if (!result.submitted) return 'Not Attempted';
  if (result.yes_ans !== null && result.no_ans !== null) {
    const total = result.yes_ans + result.no_ans;
    if (total === 0) return 'No Questions';
    const percent = Math.round((result.yes_ans / total) * 100);
    return percent >= 50 ? 'Passed' : 'Failed';
  }
  return 'Attempted';
}

function getScore(result) {
  if (result.yes_ans !== null && result.no_ans !== null) {
    const total = result.yes_ans + result.no_ans;
    if (total === 0) return '-';
    return `${result.yes_ans} / ${total}`;
  }
  return '-';
}
</script>

<template>
  <Head title="Results" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Results</h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        
            <div v-for="(result, idx) in results" :key="idx" class="relative group bg-white rounded-xl shadow p-6 flex flex-col">
              <!-- Status badge -->
              <span
                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                :class="getStatus(result) === 'Passed' ? 'bg-green-100 text-green-700' : getStatus(result) === 'Failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'"
              >
              {{ getStatus(result) }}
              </span>

              <!-- Icon and Title -->
              <div class="flex items-center gap-3 mb-2">
                <span
                  class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100"
                >
                  <span class="material-symbols-outlined text-gray-600">quiz</span>
                </span>
                <div class="flex-1 text-left">
                  <h3 class="text-lg font-bold text-gray-800">{{ result.exam_title }}</h3>
                 </div>
              </div>

              <!-- Exam Details -->
              <div class="mt-2 space-y-1 text-left">
                <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium"
                      :class="getStatus(result) === 'Passed' ? 'bg-green-100 text-green-700' : getStatus(result) === 'Failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'">
                      {{ getScore(result) }}
                    </span>
              </div>
            </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template> 