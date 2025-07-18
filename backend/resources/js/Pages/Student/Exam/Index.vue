<script setup>
import { computed } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
  exams: {
    type: Object,
    required: true,
  },
  flash: Object,
});

const user = computed(() => usePage().props.auth?.user || {});
const EXAM_DEADLINE_AFTER_REGISTRATION = 2;

function getExamDeadline(examDate, registeredAt) {
  const now = new Date();
  const examDeadline = new Date(examDate);
  const registered = registeredAt ? new Date(registeredAt) : now;
  const studentDeadline = new Date(registered);
  studentDeadline.setDate(studentDeadline.getDate() + EXAM_DEADLINE_AFTER_REGISTRATION);
  let deadline = examDeadline;
  let hoursLeft = Math.round((deadline - now) / (1000 * 60 * 60));
  const studentHoursLeft = Math.round((studentDeadline - now) / (1000 * 60 * 60));

  if (studentHoursLeft < hoursLeft) {
    deadline = studentDeadline;
    hoursLeft = studentHoursLeft;
  }
  return { deadline, hoursLeft };
}

function canTakeExam(examDate, registeredAt) {
  const now = new Date();
  const examDeadline = new Date(examDate + "T23:59:00");
  const registered = registeredAt ? new Date(registeredAt) : now;
  const studentDeadline = new Date(registered);
  studentDeadline.setDate(studentDeadline.getDate() + EXAM_DEADLINE_AFTER_REGISTRATION);
  const deadline = studentDeadline < examDeadline ? studentDeadline : examDeadline;
  return now <= deadline;
}

function getExamStatus(exam) {
  const { hoursLeft } = getExamDeadline(exam.exam_date, user.value.created_at);
  if (exam.submitted) return "completed";
  if (hoursLeft < 0) return "overdue";
  return "pending";
}

const examList = computed(() => props.exams || []);
const totalExams = computed(() => examList.value.length);
const completedExams = computed(() => examList.value.filter((e) => e.submitted).length);
const pendingExams = computed(
  () =>
    examList.value.filter((e) => !e.submitted && getExamStatus(e) === "pending").length
);
const overdueExams = computed(
  () => examList.value.filter((e) => getExamStatus(e) === "overdue").length
);
const overallProgress = computed(() =>
  totalExams.value === 0 ? 0 : Math.round((completedExams.value / totalExams.value) * 100)
);
</script>

<template>
  <Head title="Exam" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Exam</h2>
    </template>

    <div class="pt-3">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <Link
            v-for="(exam, key) in examList"
            :key="exam.exam_id"
            :href="route('student.join-exam', exam.exam_id)"
          >
            <div class="relative group bg-white rounded-xl shadow p-6 flex flex-col">
              <!-- Status badge -->
              <span
                class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-700': getExamStatus(exam) === 'completed',
                  'bg-yellow-100 text-yellow-700': getExamStatus(exam) === 'pending',
                  'bg-red-100 text-red-700': getExamStatus(exam) === 'overdue',
                }"
              >
                {{
                  getExamStatus(exam).charAt(0).toUpperCase() +
                  getExamStatus(exam).slice(1)
                }}
              </span>

              <!-- Icon and Title -->
              <div class="flex items-center gap-3 mb-2">
                <span
                  class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100"
                >
                  <span class="material-symbols-outlined text-gray-600">quiz</span>
                </span>
                <div class="flex-1 text-left">
                  <h3 class="text-lg font-bold text-gray-800">{{ exam.title }}</h3>
                  <p class="text-xs text-gray-500">Category: {{ exam.category_name }}</p>
                </div>
              </div>

              <!-- Exam Details -->
              <div class="mt-2 space-y-1 text-left">
                <p class="text-sm">
                  <strong>Duration:</strong> {{ exam.exam_duration }} mins
                </p>
                <p class="text-sm"><strong>Total Questions:</strong> 30</p>

                <div v-if="getExamStatus(exam) === 'completed'">
                  <p class="text-sm">
                    <strong>Submitted on:</strong>
                    {{ new Date(exam.submitted).toDateString() }},
                    {{ new Date(exam.submitted).toLocaleTimeString() }}
                  </p>
                </div>
                <div v-else>
                  <p class="text-sm">
                    <strong
                      >{{
                        getExamDeadline(exam.exam_date, user.created_at).hoursLeft < 0
                          ? "Elapsed Since"
                          : "Deadline"
                      }}:
                    </strong>
                    <span
                      >{{
                        getExamDeadline(
                          exam.exam_date,
                          user.created_at
                        ).deadline.toDateString()
                      }},
                      {{
                        getExamDeadline(
                          exam.exam_date,
                          user.created_at
                        ).deadline.toLocaleTimeString()
                      }}</span
                    >
                    <span
                      v-if="
                        getExamDeadline(exam.exam_date, user.created_at).hoursLeft > 0
                      "
                      class="text-gray-500"
                    >
                      in
                      {{ getExamDeadline(exam.exam_date, user.created_at).hoursLeft }}
                      hour(s)</span
                    >
                  </p>
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
