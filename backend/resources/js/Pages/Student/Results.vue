<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { computed } from "vue";

const props = defineProps({
  results: Array,
});

function getStatus(result) {
  if (!result.submitted) return "Not Attempted";
  if (result.yes_ans !== null && result.no_ans !== null) {
    const total = result.yes_ans + result.no_ans;
    if (total === 0) return "No Questions";
    const percent = Math.round((result.yes_ans / total) * 100);
    return percent >= 50 ? "Passed" : "Failed";
  }
  return "Attempted";
}

function getScore(result) {
  if (result.yes_ans !== null && result.no_ans !== null) {
    const total = result.yes_ans + result.no_ans;
    if (total === 0) return 0;
    return Math.round((result.yes_ans / total) * 100);
  }
  return 0;
}

function getEvaluation(score) {
  if (score >= 50) return "Passed";
  return "Failed";
}

// Get the latest result for display
const latestResult = computed(() => {
  if (!props.results || props.results.length === 0) return null;
  return props.results[0]; // Assuming the first result is the latest
});

const score = computed(() => (latestResult.value ? getScore(latestResult.value) : 0));
const evaluation = computed(() => getEvaluation(score.value));
</script>

<template>
  <Head title="Results" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-2">
        <h2 class="font-black text-2xl text-gray-900 tracking-tight">
          Exam Results
        </h2>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Single Result Card -->
        <div class="flex h-96 justify-center items-center">
          <div
            v-if="!true"
            class="text-center transform transition-all duration-500 ease-in-out animate-fade-in"
          >
            <!-- Title with animation -->
            <p class="text-xl font-bold text-gray-800">You scored</p>
            <!-- Score Circle with animation -->
            <div class="transform transition-all duration-700 ease-in-out animate-flip">
              <span class="font-bold text-gray-800 text-[35vw] lg:text-[15vw]">{{
                score
              }}</span>
            </div>

            <!-- Evaluation with slide animation -->
            <h2 class="text-2xl font-bold text-gray-800 animate-slide-up">
              {{ evaluation }}
            </h2>
          </div>
          <div v-else>
            <div class="text-center">
              <p class="font-medium text-gray-700">You have not taken any exam yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes flip {
  0% {
    transform: rotateY(0deg);
  }
  100% {
    transform: rotateY(360deg);
  }
}

@keyframes fill {
  from {
    width: 0%;
  }
  to {
    width: v-bind(score + "%");
  }
}

.animate-fade-in {
  animation: fade-in 0.8s ease-out;
}

.animate-slide-up {
  animation: slide-up 1s ease-out 0.3s both;
}

.animate-flip {
  animation: flip 1.5s ease-in-out;
}

.animate-fill {
  animation: fill 1.5s ease-out 0.5s both;
}

/* Hover effects */
.transform:hover\:scale-105:hover {
  transform: scale(1.05);
}

.transform:hover\:rotate-12:hover {
  transform: rotate(12deg);
}

.transform:hover\:scale-110:hover {
  transform: scale(1.1);
}
</style>
