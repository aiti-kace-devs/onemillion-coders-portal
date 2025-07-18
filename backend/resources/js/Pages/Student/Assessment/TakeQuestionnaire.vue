<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { ref, reactive, computed, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import QuestionInput from "@/Components/QuestionInput.vue";
import InstructorSelect from "@/Components/InstructorSelect.vue";

// Props
const props = defineProps({
  questionnaire: Object,
  instructors: Array,
  responses: Object,
  instructorQuestions: Array,
  storeUrl: String, // e.g. route for submission
});

const activeTab = ref("section-0");
const loadingTab = ref(null);
const loading = ref(false);
const formErrors = reactive({});
const instructorButtonText = reactive({});

// Form state
const sectionForm = reactive({
  selected_instructors: props.responses?.selected_instructors || [],
  sections: props.questionnaire.schema.map(
    (section) => props.responses?.[section.type] || {}
  ),
});

const instructorForm = reactive({});
props.instructors.forEach((inst) => {
  instructorForm[inst.id] = props.responses?.instructors?.[inst.id] || {};
});

function setActiveTab(tab) {
  activeTab.value = tab;
}

async function submitSectionForm(i, section) {
  // console.log(section)
  loading.value = true;
  loadingTab.value = `section-${i}`;
  formErrors[`section-${i}`] = {};
  try {
    const payload = {
      section: i,
      response_data: section.type === 'instructors' ? sectionForm.selected_instructors : sectionForm.sections[i],
    };
    const { data } = await axios.post(
      route("student.assessment.store", props.questionnaire.code),
      payload
    );
    handleProgress(data.progress)
  } catch (err) {
    if (err.response && err.response.status === 422) {
      formErrors[`section-${i}`] = err.response.data.errors || {};
    }
  } finally {
    loading.value = false;
    loadingTab.value = null;
  }
}

async function submitInstructorForm(instructorId) {
  loading.value = true;
  loadingTab.value = `instructor-${instructorId}`;
  formErrors[`instructor-${instructorId}`] = {};
  try {
    const payload = {
      section: `instructor-${instructorId}`,
      response_data: instructorForm[instructorId],
      instructor_id: instructorId,
    };
    
    const { data } = await axios.post(
      route("student.assessment.store", props.questionnaire.code),
      payload
    );

    handleProgress(data.progress);
  } catch (err) {
    if (err.response && err.response.status === 422) {
      formErrors[`instructor-${instructorId}`] = err.response.data.errors || {};
    }
  } finally {
    loading.value = false;
    loadingTab.value = null;
  }
}

function handleProgress(progress) {
  if (progress.next_instructor) {
    instructorButtonText[progress.next_instructor] = progress.instructor_button_text;
    setActiveTab(`instructor-${progress.next_instructor}`);
  } else {
    setActiveTab(`section-${progress.next_section}`);
  }
}
</script>

<template>
  <Head title="Assessment" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assessment</h2>
    </template>

    <div class="py-3">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div>
          <h1 class="text-xl font-bold">{{ questionnaire.title }}</h1>
          <p v-if="questionnaire.description" class="text-gray-500 text-sm mt-1">
            {{ questionnaire.description }}
          </p>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
          <!-- Tabs -->
          <div class="md:w-1/4">
            <div class="flex flex-col gap-2">
              <button
                v-for="(section, i) in questionnaire.schema"
                :key="'section-' + i"
                :class="[
                  activeTab === `section-${i}`
                    ? 'text-xs inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold uppercase text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 tracking-wider disabled:cursor-not-allowed disabled:opacity-25'
                    : 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-wider shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150',
                ]"
                @click="setActiveTab(`section-${i}`)"
              >
                {{ section.title }}
              </button>
              <button
                v-for="instructor in instructors"
                :key="'instructor-' + instructor.id"
                v-show="false"
                :class="[
                  activeTab === `instructor-${instructor.id}`
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-white text-gray-700',
                  'w-full px-4 py-2 rounded text-left font-medium border hover:bg-blue-50 transition',
                ]"
                @click="setActiveTab(`instructor-${instructor.id}`)"
              >
                {{ instructor.name }}
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="md:w-3/4">
            <!-- Instructor Tabs -->
            <div
              v-for="instructor in instructors"
              :key="'tab-instructor-' + instructor.id"
              v-show="activeTab === `instructor-${instructor.id}`"
              class="bg-white rounded shadow p-6 animate-fade-in"
            >
              <form
                @submit.prevent="submitInstructorForm(instructor.id)"
                class="space-y-4"
              >
                <div>
                  <p class="block text-lg font-semibold mb-2"
                    >Review Instructor: {{ instructor.name }}</p>
                </div>
                <QuestionInput
                  section-title="instructors"
                  :section-questions="instructorQuestions"
                  :section-index="0"
                  :instructors="instructors"
                  v-model="instructorForm[instructor.id]"
                  :errors="formErrors['instructor-' + instructor.id] || {}"
                />
                <div class="pt-4">
                  <PrimaryButton
                    :loading="loadingTab === `instructor-${instructor.id}` || loading"
                    :disabled="loading"
                    type="submit"
                    class="w-full md:w-auto"
                  >
                    {{ instructorButtonText[instructor.id] || "Save & Next" }}
                  </PrimaryButton>
                </div>
              </form>
            </div>

            <!-- Section Tabs -->
            <div
              v-for="(section, i) in questionnaire.schema"
              :key="'tab-section-' + i"
              v-show="activeTab === `section-${i}`"
              class="bg-white rounded shadow p-5 animate-fade-in"
            >
              <form @submit.prevent="submitSectionForm(i, section)" class="space-y-4">
                <div v-if="section.description">
                  <p class="text-gray-500 text-sm mb-2">{{ section.description }}</p>
                </div>
                <input type="hidden" name="section" :value="i" />
                <div v-if="section.type === 'instructors'">
                  <InstructorSelect
                    :instructors="instructors"
                    v-model="sectionForm.selected_instructors"
                    :responses="responses.selected_instructors?.[i] || {}"
                    :errors="formErrors['section-' + i] || {}"
                  />
                </div>
                <div v-else>
                  <QuestionInput
                    :section-title="section.title"
                    :section-questions="section.questions"
                    :section-index="i"
                    :instructors="section.type === 'instructors' ? instructors : []"
                    v-model="sectionForm.sections[i]"
                    :errors="formErrors['section-' + i] || {}"
                  />
                </div>
                <div class="pt-4">
                  <PrimaryButton
                    :loading="loadingTab === `section-${i}` || loading"
                    :disabled="loading"
                    type="submit"
                    class="w-full md:w-auto"
                  >
                    {{
                      i === questionnaire.schema.length - 1 &&
                      section.type !== "instructors"
                        ? "Submit"
                        : "Save & Next"
                    }}
                  </PrimaryButton>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
</style>
