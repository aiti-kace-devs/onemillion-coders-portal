<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import RevokeOrDeclineAdmissionModal from "@/Components/RevokeOrDeclineAdmissionModal.vue";
import LinkButton from "@/Components/LinkButton.vue";

const props = defineProps({
  user: Object,
  user_exam: Object,
  user_admission: Object,
});

const collapse = ref([true, false, false, false]);
const showRevokeModal = ref(false);

function toggleCollapse(idx) {
  collapse.value[idx] = !collapse.value[idx];
}

function openRevokeModal() {
  showRevokeModal.value = true;
}
function closeRevokeModal() {
  showRevokeModal.value = false;
}
</script>

<template>
  <Head title="Application Status" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Application Status
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div v-if="props.user_admission && props.user_admission.confirmed" class="p-6 bg-white shadow sm:rounded-lg">
          <div
            class="inline-flex space-x-2 items-center text-green-600 font-semibold text-lg"
          >
            <span class="material-symbols-outlined"> check_circle </span>
            <span class="font-semibold"
              >Congratulations, {{ user.name }}! You have been admitted.</span
            >
          </div>
        </div>

        <div class="p-6 bg-white sm:rounded-lg shadow">
          <p class="font-medium text-lg text-gray-900">Application Status</p>

          <div class="mt-5 px-5 max-w-2xl">
            <ol class="relative border-l border-green-700">
              <!-- Step 1: Application Submitted -->
              <li class="mb-10 ml-6">
                <span
                  class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white"
                  >1</span
                >
                <div class="flex items-center cursor-pointer" @click="toggleCollapse(0)">
                  <h3 class="font-semibold text-lg text-green-700">
                    Application Submitted
                  </h3>
                  <svg
                    :class="[
                      'ml-2 w-4 h-4 text-gray-800 transition-transform',
                      collapse[0] ? 'rotate-90' : '',
                    ]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </div>
                <div v-if="collapse[0]" class="mt-2 text-sm text-gray-700 pl-2">
                  Your application has been successfully received and all details have
                  been captured.
                </div>
              </li>
              <!-- Step 2: Aptitude Test -->
              <li class="mb-10 ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                    props.user_exam?.submitted ? 'bg-green-500' : 'bg-gray-300',
                  ]"
                  >2</span
                >
                <div class="flex items-center cursor-pointer" @click="toggleCollapse(1)">
                  <h3
                    :class="[
                      'font-semibold text-lg',
                      props.user_exam?.submitted ? 'text-green-700' : 'text-gray-400',
                    ]"
                  >
                    Aptitude Test
                  </h3>
                  <svg
                    :class="[
                      'ml-2 w-4 h-4 text-gray-800 transition-transform',
                      collapse[1] ? 'rotate-90' : '',
                    ]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </div>
                <div v-if="collapse[1]" class="mt-2 text-sm text-gray-700 pl-2">
                  <template v-if="props.user_exam?.submitted">
                    Test submitted on
                    {{ new Date(props.user_exam.submitted).toDateString() }} at
                    {{ new Date(props.user_exam.submitted).toLocaleTimeString() }}
                  </template>
                  <template v-else>
                    You must complete the aptitude test to proceed to the next stage.
                    Click “Take Test Now” button to begin.

                    <div class="mt-5">
                      <LinkButton :href="`/student/join_exam/${props.user_exam?.exam_id}`"
                        >Take test now</LinkButton
                      >
                    </div>
                  </template>
                </div>
              </li>
              <!-- Step 3: Shortlisted -->
              <li class="mb-10 ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                    props.user_admission ? 'bg-green-500' : 'bg-gray-300',
                  ]"
                  >3</span
                >
                <div class="flex items-center cursor-pointer" @click="toggleCollapse(2)">
                  <h3
                    :class="[
                      'font-semibold text-lg',
                      props.user_admission ? 'text-green-700' : 'text-gray-400',
                    ]"
                  >
                    Shortlisted
                  </h3>
                  <svg
                    :class="[
                      'ml-2 w-4 h-4 text-gray-800 transition-transform',
                      collapse[2] ? 'rotate-90' : '',
                    ]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </div>
                <div v-if="collapse[2]" class="mt-2 text-sm text-gray-700 pl-2">
                  <template v-if="props.user_admission">
                    Your application has been reviewed and you have been selected for
                    admission. Kindly confirm your session.
                  </template>
                  <template v-else>
                    Our team will review your application, and if selected, you will
                    receive a notification via email and SMS. Please check your inbox and
                    messages regularly.
                  </template>
                </div>
              </li>
              <!-- Step 4: Confirm Admission -->
              <li class="ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                    props.user_admission?.session ? 'bg-green-500' : 'bg-gray-300',
                  ]"
                  >4</span
                >
                <div class="flex items-center cursor-pointer" @click="toggleCollapse(3)">
                  <h3
                    :class="[
                      'font-semibold text-lg',
                      props.user_admission?.confirmed
                        ? 'text-green-700'
                        : 'text-gray-400',
                    ]"
                  >
                    Confirm Admission
                  </h3>
                  <svg
                    :class="[
                      'ml-2 w-4 h-4 text-gray-800 transition-transform',
                      collapse[3] ? 'rotate-90' : '',
                    ]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </div>
                <div v-if="collapse[3]" class="mt-2 text-sm text-gray-700 pl-2">
                  <div v-if="props.user_admission && props.user_admission.confirmed">
                    <div>Congratulations, you have been admitted.</div>

                    <div class="mt-5">
                      <RevokeOrDeclineAdmissionModal
                        v-if="props.user && props.user_admission"
                        :user="props.user"
                        :session="props.user_admission"
                      />
                    </div>

                    <!-- Revoke/Decline Modal -->
                  </div>
                  <div v-else>
                    <p>
                      If shortlisted, you must select a session to confirm your admission.
                      Further instructions will be provided upon selection.
                    </p>

                    <div class="mt-5">
                      <LinkButton :href="route('student.session.index')">
                        Choose a session
                      </LinkButton>
                    </div>
                  </div>
                </div>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
