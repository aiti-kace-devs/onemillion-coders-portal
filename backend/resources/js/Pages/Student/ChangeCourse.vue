<script setup>
import { ref, reactive } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
  courses: Array, // [{ id, course_name }]
  user: Object, // { exam: course_id }
  flash: Object,
});

const form = useForm({
  course_id: "",
});

function submit() {
  form.post(route("student.update-course"), {
    onSuccess: () => {
      if (props.flash.key === "error") {
        toastr.error(props.flash.message);
      } else {
        toastr.success("Course changed successfully!");
      }
      //   router.visit(route("student.dashboard"));
    },
    onError: () => {
      toastr.error("Something went wrong. Please try again.");
    },
  });
}
</script>

<template>
  <Head title="Change Course" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Change Course</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="max-w-xl">
            <h3 class="font-medium text-lg text-gray-900">Select New Course</h3>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
              <div>
                <InputLabel for="course_id" value="Course" :required="true" />
                <SelectInput
                  id="course_id"
                  v-model="form.course_id"
                  class="w-full"
                  :class="{ 'border-red-600': form.errors.course_id }"
                >
                  <option value="" disabled>-- Select Course --</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_name }}
                  </option>
                </SelectInput>

                <InputError :message="form.errors.course_id" />
              </div>

              <div
                class="bg-yellow-50 border-l-4 border-yellow-600 p-4 rounded flex items-start gap-2"
              >
                <svg
                  class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"
                  />
                </svg>
                <span class="text-sm text-yellow-600">
                  <strong>Please Note:</strong> Changing your course may affect your exam
                  schedule and progress. Make sure this is the right decision.
                </span>
              </div>

              <div class="pt-2">
                <PrimaryButton :disabled="form.processing" type="submit">
                  Change course
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
