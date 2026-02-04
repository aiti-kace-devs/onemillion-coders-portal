<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { computed, onMounted } from "vue";

const props = defineProps({
  exam: {
    type: Object,
    required: true,
  },
  result: {
    type: Object,
    required: true,
  },
  student: {
    type: Object,
    required: true,
  },
});

const page = usePage();

onMounted(() => {
  const flash = page.props.flash;
  if (flash && flash.message) {
    if (flash.key === "success") {
      toastr.success(flash.message);
    } else if (flash.key === "error") {
      toastr.error(flash.message);
    } else if (flash.key === "info") {
      toastr.info(flash.message);
    } else if (flash.key === "warning") {
      toastr.warning(flash.message);
    } else {
      toastr.info(flash.message);
    }
  }
});

const totalQuestions = computed(() => {
  const yes = props.result.yes_ans ?? 0;
  const no = props.result.no_ans ?? 0;
  return yes + no;
});

const percentageScore = computed(() => {
  if (!totalQuestions.value) return 0;
  return Math.round(((props.result.yes_ans ?? 0) / totalQuestions.value) * 100);
});
</script>

<template>
  <Head title="Result" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Result</h2>
    </template>

    <div class="py-8">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Student & Exam Info -->
        <div class="bg-white shadow sm:rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Student information
          </h3>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
              <dt class="font-medium text-gray-500">Name</dt>
              <dd class="mt-1 text-gray-900">
                {{ student.name }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Email</dt>
              <dd class="mt-1 text-gray-900">
                {{ student.email }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Exam name</dt>
              <dd class="mt-1 text-gray-900">
                {{ exam.title }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Exam date</dt>
              <dd class="mt-1 text-gray-900">
                {{ exam.exam_date }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Result Info -->
        <div class="bg-white shadow sm:rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Result information
          </h3>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
              <dt class="font-medium text-gray-500">
                Number of correct answers
              </dt>
              <dd class="mt-1 text-gray-900">
                {{ result.yes_ans }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">
                Number of wrong answers
              </dt>
              <dd class="mt-1 text-gray-900">
                {{ result.no_ans }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">
                Total marks
              </dt>
              <dd class="mt-1 text-gray-900">
                {{ result.yes_ans }}/{{ totalQuestions }}
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">
                Percentage score
              </dt>
              <dd class="mt-1 text-gray-900">
                {{ percentageScore }}%
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

