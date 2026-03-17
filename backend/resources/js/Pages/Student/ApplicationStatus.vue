<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, useForm, Link, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import RevokeOrDeclineAdmissionModal from "@/Components/RevokeOrDeclineAdmissionModal.vue";
import LinkButton from "@/Components/LinkButton.vue";

const props = defineProps({
    user: Object,
    user_exam: Object,
    user_admission: Object,
    user_assessment: Object,
});

const collapse = ref([true, false, false, false, false]);
const showRevokeModal = ref(false);

function toggleCollapse(idx) {
    if (isStepReached(idx)) {
        collapse.value[idx] = !collapse.value[idx];
    }
}

function isStepReached(idx) {
    if (idx === 0) return true; // Step 1: Always reached
    if (idx === 1) return true; // Step 2: Assessment (Follows app submission)
    if (idx === 2) return props.user_assessment?.completed; // Step 3: Choose Course
    if (idx === 3) return props.user.registered_course; // Step 4: Shortlist
    if (idx === 4) return props.user.shortlist; // Step 5: Admission
    return false;
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
            <ol class="relative border-l border-green-500/30">
              <!-- Step 1: Application Submitted -->
              <li class="mb-10 ml-6">
                <span
                  class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white font-bold"
                  >1</span
                >
                <div class="flex items-center cursor-pointer" @click="toggleCollapse(0)">
                  <h3 class="font-bold text-lg text-gray-800">
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
                  We've received your application! Our team is now ready to guide you through the following steps of your journey.
                </div>
              </li>

              <!-- Step 2: Level Determination Test -->
              <li class="mb-10 ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                    props.user_assessment?.completed ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400',
                  ]"
                  >2</span>
                <div class="flex items-center" :class="isStepReached(1) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'" @click="toggleCollapse(1)">
                  <h3
                    :class="[
                      'font-bold text-lg',
                      props.user_assessment?.completed ? 'text-gray-800' : (isStepReached(1) ? 'text-gray-700' : 'text-gray-400'),
                    ]"
                  >
                    Level Determination Test
                  </h3>
                  <svg
                    v-if="isStepReached(1)"
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
                <div v-if="collapse[1] && isStepReached(1)" class="mt-2 text-sm text-gray-700 pl-2">
                  <template v-if="props.user_assessment?.completed">
                    Great job! Your assessment is complete and we've determined your starting level.
                    <span v-if="usePage().props.config?.SHOW_STUDENT_LEVEL"> Your current level is: <span class="font-bold uppercase text-indigo-600">{{ props.user.student_level }}</span></span>
                  </template>
                  <template v-else>
                    This assessment helps us understand your current skills so we can place you in the right course level. Please complete it to move forward.

                    <div class="mt-5">
                      <a :href="`${$page.props.quiz_frontend_url}/quiz/${props.user.userId}`" target="_blank" class="inline-flex items-center px-6 py-2.5 bg-[#f9a825] border border-transparent rounded-xl font-bold text-xs text-gray-900 uppercase tracking-widest hover:bg-[#e09621] transition duration-150 shadow-md shadow-yellow-500/10">
                        Take assessment now
                      </a>
                    </div>
                </div>

              <!-- Step 3: Choose Course -->
              <li class="mb-10 ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                    props.user.registered_course ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400',
                  ]"
                  >3</span>
                <div class="flex items-center" :class="isStepReached(2) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'" @click="toggleCollapse(2)">
                  <h3
                    :class="[
                      'font-bold text-lg',
                      props.user.registered_course ? 'text-gray-800' : (isStepReached(2) ? 'text-gray-700' : 'text-gray-400'),
                    ]"
                  >
                    Course Selection
                  </h3>
                  <svg
                    v-if="isStepReached(2)"
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
                <div v-if="collapse[2] && isStepReached(2)" class="mt-2 text-sm text-gray-700 pl-2">
                  <template v-if="props.user.registered_course">
                    You have successfully selected a course.
                  </template>
                  <template v-else>
                    Now that your level is determined, please select the course that best aligns with your interests and career goals.

                    <div class="mt-5">
                      <LinkButton :href="route('student.change-course')">
                        Choose a course
                      </LinkButton>
                    </div>
                  </template>
                </div>
              </li>

              <!-- Step 4: Shortlisted -->
              <li class="mb-10 ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                    props.user.shortlist ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400',
                  ]"
                  >4</span>
                <div class="flex items-center" :class="isStepReached(3) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'" @click="toggleCollapse(3)">
                  <h3
                    :class="[
                      'font-bold text-lg',
                      props.user.shortlist ? 'text-gray-800' : (isStepReached(3) ? 'text-gray-700' : 'text-gray-400'),
                    ]"
                  >
                    Shortlisted
                  </h3>
                  <svg
                    v-if="isStepReached(3)"
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
                <div v-if="collapse[3] && isStepReached(3)" class="mt-2 text-sm text-gray-700 pl-2">
                  <template v-if="props.user.shortlist">
                    Congratulations! You have been shortlisted for the next phase.
                  </template>
                  <template v-else>
                    Our admission team is reviewing your profile. If shortlisted, you will see it here.
                  </template>
                </div>
              </li>

              <!-- Step 5: Admission Confirmed -->
              <li class="ml-6">
                <span
                  :class="[
                    'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white font-bold',
                    props.user_admission?.confirmed ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400',
                  ]"
                  >5</span>
                <div class="flex items-center" :class="isStepReached(4) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'" @click="toggleCollapse(4)">
                  <h3
                    :class="[
                      'font-bold text-lg',
                      props.user_admission?.confirmed ? 'text-gray-800' : (isStepReached(4) ? 'text-gray-700' : 'text-gray-400'),
                    ]"
                  >
                    Admission Confirmed
                  </h3>
                  <svg
                    v-if="isStepReached(4)"
                    :class="[
                      'ml-2 w-4 h-4 text-gray-800 transition-transform',
                      collapse[4] ? 'rotate-90' : '',
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
                <div v-if="collapse[4] && isStepReached(4)" class="mt-2 text-sm text-gray-700 pl-2">
                  <div v-if="props.user_admission && props.user_admission.confirmed">
                    <div>You have successfully confirmed your admission.</div>

                    <div class="mt-5">
                      <RevokeOrDeclineAdmissionModal
                        v-if="props.user && props.user_admission"
                        :user="props.user"
                        :session="props.user_admission"
                      />
                    </div>
                  </div>
                  <div v-else-if="props.user.shortlist">
                    <p>
                      You have been shortlisted! Please select a session to officially confirm your admission.
                    </p>

                    <div class="mt-5 px-5 max-w-2xl">
                        <ol class="relative border-l border-green-700">
                            <!-- Step 1: Application Submitted -->
                            <li class="mb-10 ml-6">
                                <span
                                    class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white"
                                    >1</span
                                >
                                <div
                                    class="flex items-center cursor-pointer"
                                    @click="toggleCollapse(0)"
                                >
                                    <h3
                                        class="font-semibold text-lg text-green-700"
                                    >
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
                                <div
                                    v-if="collapse[0]"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    We've received your application! Our team is
                                    now ready to guide you through the following
                                    steps of your journey.
                                </div>
                            </li>

                            <!-- Step 2: Level Determination Test -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                                        props.user_assessment?.completed
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                    ]"
                                    >2</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(1)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(1)"
                                >
                                    <h3
                                        :class="[
                                            'font-semibold text-lg',
                                            props.user_assessment?.completed
                                                ? 'text-green-700'
                                                : isStepReached(1)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Level Determination Test
                                    </h3>
                                    <svg
                                        v-if="isStepReached(1)"
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
                                <div
                                    v-if="collapse[1] && isStepReached(1)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template
                                        v-if="props.user_assessment?.completed"
                                    >
                                        Great job! Your assessment is complete
                                        and we've determined your starting
                                        level.
                                        <span
                                            v-if="
                                                usePage().props.config
                                                    ?.SHOW_STUDENT_LEVEL
                                            "
                                        >
                                            Your current level is:
                                            <span
                                                class="font-bold uppercase text-indigo-600"
                                                >{{
                                                    props.user.student_level
                                                }}</span
                                            ></span
                                        >
                                    </template>
                                    <template v-else>
                                        This assessment helps us understand your
                                        current skills so we can place you in
                                        the right course level. Please complete
                                        it to move forward.

                                        <div class="mt-5">
                                            <a
                                                :href="`${$page.props.quiz_frontend_url}/quiz/${props.user.userId}`"
                                                target="_blank"
                                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
                                            >
                                                Take assessment now
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </li>

                            <!-- Step 3: Choose Course -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                                        props.user.registered_course
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                    ]"
                                    >3</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(2)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(2)"
                                >
                                    <h3
                                        :class="[
                                            'font-semibold text-lg',
                                            props.user.registered_course
                                                ? 'text-green-700'
                                                : isStepReached(2)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Course Selection
                                    </h3>
                                    <svg
                                        v-if="isStepReached(2)"
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
                                <div
                                    v-if="collapse[2] && isStepReached(2)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template
                                        v-if="props.user.registered_course"
                                    >
                                        You have successfully selected a course.
                                    </template>
                                    <template v-else>
                                        Now that your level is determined,
                                        please select the course that best
                                        aligns with your interests and career
                                        goals.

                                        <div class="mt-5">
                                            <LinkButton
                                                :href="
                                                    route(
                                                        'student.change-course',
                                                    )
                                                "
                                            >
                                                Choose a course
                                            </LinkButton>
                                        </div>
                                    </template>
                                </div>
                            </li>

                            <!-- Step 4: Shortlisted -->
                            <li class="mb-10 ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                                        props.user.shortlist
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                    ]"
                                    >4</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(3)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(3)"
                                >
                                    <h3
                                        :class="[
                                            'font-semibold text-lg',
                                            props.user.shortlist
                                                ? 'text-green-700'
                                                : isStepReached(3)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Shortlisted
                                    </h3>
                                    <svg
                                        v-if="isStepReached(3)"
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
                                <div
                                    v-if="collapse[3] && isStepReached(3)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <template v-if="props.user.shortlist">
                                        Congratulations! You have been
                                        shortlisted for the next phase.
                                    </template>
                                    <template v-else>
                                        Our admission team is reviewing your
                                        profile. If shortlisted, you will see it
                                        here.
                                    </template>
                                </div>
                            </li>

                            <!-- Step 5: Admission Confirmed -->
                            <li class="ml-6">
                                <span
                                    :class="[
                                        'absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white text-white',
                                        props.user_admission?.confirmed
                                            ? 'bg-green-500'
                                            : 'bg-gray-300',
                                    ]"
                                    >5</span
                                >
                                <div
                                    class="flex items-center"
                                    :class="
                                        isStepReached(4)
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-50'
                                    "
                                    @click="toggleCollapse(4)"
                                >
                                    <h3
                                        :class="[
                                            'font-semibold text-lg',
                                            props.user_admission?.confirmed
                                                ? 'text-green-700'
                                                : isStepReached(4)
                                                  ? 'text-gray-700'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        Admission Confirmed
                                    </h3>
                                    <svg
                                        v-if="isStepReached(4)"
                                        :class="[
                                            'ml-2 w-4 h-4 text-gray-800 transition-transform',
                                            collapse[4] ? 'rotate-90' : '',
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
                                <div
                                    v-if="collapse[4] && isStepReached(4)"
                                    class="mt-2 text-sm text-gray-700 pl-2"
                                >
                                    <div
                                        v-if="
                                            props.user_admission &&
                                            props.user_admission.confirmed
                                        "
                                    >
                                        <div>
                                            You have successfully confirmed your
                                            admission.
                                        </div>

                                        <div class="mt-5">
                                            <RevokeOrDeclineAdmissionModal
                                                v-if="
                                                    props.user &&
                                                    props.user_admission
                                                "
                                                :user="props.user"
                                                :session="props.user_admission"
                                            />
                                        </div>
                                    </div>
                                    <div v-else-if="props.user.shortlist">
                                        <p>
                                            You have been shortlisted! Please
                                            select a session to officially
                                            confirm your admission.
                                        </p>

                                        <div
                                            v-if="props.user?.shortlist"
                                            class="mt-5"
                                        >
                                            <LinkButton
                                                :href="
                                                    route(
                                                        'student.session.index',
                                                    )
                                                "
                                            >
                                                Choose a session
                                            </LinkButton>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p>
                                            Admission will be granted once you
                                            are shortlisted and have confirmed
                                            your session.
                                        </p>
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
