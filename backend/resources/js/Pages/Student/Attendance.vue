<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onBeforeUnmount, nextTick } from "vue";

const props = defineProps({
  attendances: {
    type: Array,
    default: () => [],
  },
  totalSessions: {
    type: Number,
    default: 0,
  },
});

const { auth } = usePage().props;

const user = auth?.user || {};

const attendanceCount = computed(() => props.attendances.length);
const attendancePercent = computed(() => {
  if (!props.totalSessions) return 0;
  return Math.round((attendanceCount.value / props.totalSessions) * 100);
});


onMounted(async () => {
  await nextTick();
  // Assume jQuery and DataTable are globally available
  $("#data_table").DataTable({
    searching: false,
    responsive: true,
    language: {
      emptyTable: "No attendance records found.",
    },
    lengthMenu: [
      [10, 25, 50, 100, -1],
      ["10", "25", "50", "100", "All"],
    ],
    order: [[1, "desc"]],
  });
});

</script>

<template>
  <Head title="Attendance" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Attendance</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        {{ user }}
        <!-- Progress Bar -->
        <div v-if="totalSessions != 0" class="p-6 bg-white shadow sm:rounded-lg">
          <div class="mb-2">
            <div class="flex justify-between items-center mb-1">
              <span class="text-base font-medium text-green-700"
                >Attendance Progress</span
              >
              <span class="text-sm font-medium text-green-700"
                >{{ attendancePercent }}%</span
              >
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div
                class="bg-green-600 h-3 rounded-full transition-all duration-500"
                :style="{ width: attendancePercent + '%' }"
              ></div>
            </div>
            <div class="mt-2 text-sm text-gray-700">
              {{ attendanceCount }} of {{ totalSessions }} sessions attended
            </div>
          </div>
        </div>

        <!-- Attendance Table -->
        <div class="p-6 bg-white shadow sm:rounded-lg">
          <h3 class="font-medium text-lg text-gray-900 mb-4">Attendance Records</h3>
          <div class="overflow-x-auto">
            <table id="data_table" class="table-striped text-sm">
              <thead class="capitalize border-b bg-gray-100 font-medium">
                <tr>
                  <th class="text-gray-900 px-6 py-4 text-left">Course</th>
                  <th class="text-gray-900 px-6 py-4 text-left">Date</th>
                  <th class="text-gray-900 px-6 py-4 text-left">Confirmed At</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(attend, idx) in attendances"
                  :key="attend.id || idx"
                >
                  <td class="px-4 py-2">{{ attend.course_name }}</td>
                  <td class="px-4 py-2">{{ new Date(attend.date).toDateString() }}</td>
                  <td class="px-4 py-2">
                    {{ new Date(attend.created_at).toDateString() }},
                    {{ new Date(attend.created_at).toLocaleTimeString() }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
