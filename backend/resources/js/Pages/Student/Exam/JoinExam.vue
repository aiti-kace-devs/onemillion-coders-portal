<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from "vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import RadioInput from "@/Components/RadioInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
  exam: Object,
  questions: {
    type: Array,
    default: () => [],
  },
  usedTime: Number,
});

const user = computed(() => usePage().props.auth?.user || {});

// Changed to refs for better reactivity
const modalShow = ref(false);
const modalType = ref(null); // 'instructions' | 'warning' | 'submission'
const modalMessage = ref(null);

const warn = ref(0);

const timeLeft = ref((props.exam.exam_duration - 21 - props.usedTime) * 60); // in seconds
const timerDisplay = computed(() => {
  const m = Math.floor(timeLeft.value / 60);
  const s = timeLeft.value % 60;
  return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
});
const timeUp = ref(false);
const fullscreenTarget = ref(null);
const answers = ref({});
const questions = ref({});

if (Array.isArray(props.questions)) {
  props.questions.forEach((q, idx) => {
    questions.value[`question${idx + 1}`] = q.id;
    answers.value[`ans${idx + 1}`] = null;
  });
}

const form = useForm({
  exam_id: props.exam.id,
  ...questions.value,
  ...answers.value,
  index: props.questions.length,
});

function openFullscreen() {
  const elem = fullscreenTarget.value;
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) {
    elem.msRequestFullscreen();
  }
}

function handleViolation() {
  warn.value++;
  modalType.value = "warning";

  if (warn.value >= 3) {
    modalMessage.value = "Test submitted due to repeated violations.";
    setTimeout(() => {
      timeLeft.value = 0;
      submit();
      modalShow.value = false;
    }, 2000);
  } else {
    modalMessage.value = `You are in violation of exam rules. Please DO NOT exit fullscreen or change tabs.<br>Warning Count: ${warn.value}.`;
  }

  modalShow.value = true;
}

function handleVisibilityChange() {
  if (document.hidden) {
    handleViolation();
  }
}

function handleFullscreenChange() {
  if (!document.fullscreenElement) {
    handleViolation();
  }
}

function handleModalContinue() {
  openFullscreen();
  setTimeout(() => {
    modalShow.value = false;
  }, 1000);
}

function handleStartTest() {
  openFullscreen();
  setTimeout(() => {
    modalShow.value = false;
    startTest();
  }, 400);
}

function startTest() {
  document.addEventListener("visibilitychange", handleVisibilityChange);
  document.addEventListener("fullscreenchange", handleFullscreenChange);

  axios
    .post(route("student.start-exam", { exam_id: form.exam_id }))
    .then((response) => {
      if (response.data.status) {
        $("#examination_form").show();
        startTimer();
      }
    })
    .catch((error) => toastr.error(error));
}

let timerInterval = null;
function startTimer() {
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    } else {
      clearInterval(timerInterval);
      timeUp.value = true;
      submit();
    }
  }, 1000);
}

function submit() {
  // Implement your form submission logic here
  form.post(route("student.submit-exam"), {
    preserveScroll: true,
    onSuccess: () => {
      toastr.success('Exam submitted successfully')
    },
    onError: () => {
      toastr.error('Something went wrong. Try again')
    },
  });
}

function confirmSubmit() {
  const questionCount = props.questions.length;
  // Only count answers where the key starts with 'ans' and value is not null/undefined/empty string
  const answerCount = Object.entries(form.data()).filter(
    ([key, value]) =>
      key.startsWith("ans") && value !== null && value !== undefined && value !== ""
  ).length;
  const remainder = questionCount - answerCount;

  modalType.value = "submission";
  modalMessage.value = `Are you sure you want to submit this test? This cannot be undone. ${
    remainder !== 0
      ? `${remainder} question${remainder !== 0 ? "s" : ""} left to answer.`
      : ""
  }`;

  modalShow.value = true;
}

onMounted(async () => {
  await nextTick();

  fullscreenTarget.value = document.getElementById("exam-content");
  // Show instructions modal on mount
  modalType.value = "instructions";
  modalShow.value = true;
});
</script>

<template>
  <Head title="Exam" />
  <div id="exam-content" class="bg-gray-50 py-6 px-2 sm:px-6 lg:px-8">
    <div v-if="!modalShow || modalType !== 'instructions'" class="max-w-4xl mx-auto">
      <!-- Rest of your exam content -->
      <div
        class="flex flex-wrap justify-between items-center bg-white rounded-lg shadow p-6 mb-6 text-gray-800 gap-y-5 md:gap-y-0"
      >
        <!-- Timer display -->
        <div
          class="order-1 flex items-center text-center sm:mb-0 space-x-1"
          :class="{ 'text-red-600': timeLeft < 660 }"
        >
          <span class="material-symbols-outlined"> timer_play </span>
          <span class="inline-block font-semibold text-lg min-w-14 text-center">
            {{ timerDisplay }}
          </span>
        </div>

        <!-- Submit button -->
        <div class="w-full md:w-auto text-center order-3 md:order-2">
          <PrimaryButton type="button" @click="confirmSubmit" :disabled="form.processing"
            >SUBMIT TEST</PrimaryButton
          >
        </div>

        <!-- Status indicator -->
        <div
          class="order-2 md:order-3 flex items-center text-center sm:text-right space-x-2"
        >
          <div>
            <span class="relative flex h-2 w-2">
              <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"
              ></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-green-600"></span>
            </span>
          </div>
          <h3 class="font-semibold text-green-600">Running</h3>
        </div>
      </div>

      <!-- Exam questions form -->
      <div>
        <form
          id="examination_form"
          class="hidden pb-12 space-y-6 overflow-x-hidden overflow-y-scroll scroll-smooth h-[calc(100vh-100px)]"
        >
          <div
            v-for="(q, idx) in props.questions"
            :key="q.id"
            class="border border-gray-300 rounded-lg p-4 mb-4 bg-white"
          >
            <p class="mb-2">{{ idx + 1 }}. {{ q.questions }}</p>
            <ul class="space-y-2">
              <li v-for="opt in ['option1', 'option2', 'option3', 'option4']" :key="opt">
                <label class="inline-flex items-center">
                  <RadioInput
                    :value="JSON.parse(q.options)[opt]"
                    v-model:checked="form[`ans${idx + 1}`]"
                    :disabled="form.processing || timeLeft === 0"
                  />
                  <span class="ml-2">{{ JSON.parse(q.options)[opt] }}</span>
                </label>
              </li>
            </ul>
            <InputError :message="form.errors[`ans${idx + 1}`]" />
          </div>
          <div class="text-center">
            <PrimaryButton type="button" @click="confirmSubmit"
              >SUBMIT TEST</PrimaryButton
            >
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal for Instructions, Warnings and Submission-->
  <Modal :show="modalShow" :closeable="false" :teleportTo="'#exam-content'">
    <div class="p-6">
      <template v-if="modalType === 'instructions'">
        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ exam.title }}</h3>
        <ol class="text-left space-y-1 my-4">
          <li class="font-semibold">Make sure you answer all questions</li>
          <li class="font-semibold">DO NOT exit fullscreen</li>
          <li class="font-semibold">DO NOT switch tabs</li>
          <li class="font-semibold">You will be warned when you violate these rules</li>
          <li class="font-semibold">
            Your test may be automatically SUBMITTED if you keep on violating rules
          </li>
          <li>Ensure you have good and stable internet</li>
          <li>
            The duration of the test is {{ exam.exam_duration }} mins from the time you
            click on 'START TEST'
          </li>
          <li>Click on 'START TEST' to begin the test</li>
          <li>Click on 'SUBMIT TEST' after you have completed</li>
        </ol>
        <div class="flex justify-center mt-6">
          <PrimaryButton @click="handleStartTest">START TEST</PrimaryButton>
        </div>
      </template>
      <template v-else-if="modalType === 'warning'">
        <h2 class="text-lg text-center font-medium text-gray-900">Violation!</h2>
        <p class="mt-1 text-sm text-center text-gray-600" v-html="modalMessage"></p>
        <div class="mt-6 flex justify-center">
          <PrimaryButton v-if="warn < 3" @click="handleModalContinue"
            >Continue</PrimaryButton
          >
          <PrimaryButton v-else @click="modalShow = false">Okay</PrimaryButton>
        </div>
      </template>
      <template v-else-if="modalType === 'submission'">
        <h2 class="text-lg text-center font-medium text-gray-900">Confirm Submission</h2>
        <p class="mt-1 text-sm text-center text-gray-600" v-html="modalMessage"></p>
        <div class="mt-6 flex justify-center">
          <SecondaryButton @click="modalShow = false" :disabled="form.processing">Cancel</SecondaryButton>

          <PrimaryButton class="ms-3" :disabled="form.processing" @click="submit">
            Submit
          </PrimaryButton>
        </div>
      </template>
    </div>
  </Modal>
</template>
