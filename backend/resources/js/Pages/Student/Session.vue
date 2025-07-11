<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import RevokeOrDeclineAdmissionModal from "@/Components/RevokeOrDeclineAdmissionModal.vue";

const props = defineProps({
  user: Object,
  admission: Object,
  course: Object,
  sessions: Array,
  session: Object,
  flash: Object,
});

const form = useForm({
  session_id: "",
});

form.defaults({
  session_id: "",
});

const revokeForm = useForm({});

const selectKey = ref(0);

const submit = () => {
  form.post(route("student.session.store"), {
    preserveScroll: true,
    onSuccess: () => {
      toastr.success(props.flash.message);
      form.reset();
      form.clearErrors();
      selectKey.value++; // Force re-render
    },
    onError: (errors) => {
      toastr.error("Something went wrong");
    },
  });
};
</script>

<template>
  <Head title="Session" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Session</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-6 bg-white shadow sm:rounded-lg">
          <div
            class="inline-flex space-x-2 items-center text-green-600 font-semibold text-lg"
          >
            <span class="material-symbols-outlined"> check_circle </span>
            <span class="font-semibold">Congratulations, {{ user.name }}!</span>
          </div>

          <div class="mt-1 text-gray-700">
            <div v-if="admission.confirmed">
              <span
                >You have selected
                <span class="font-bold"
                  >{{ session?.name }} - {{ session?.course_time }}</span
                >.
              </span>
            </div>
            <div v-else>
              <span
                >Please select a session for <strong>{{ course.course_name }}</strong
                >.</span
              >
            </div>
          </div>
        </div>

        <div class="p-6 bg-white shadow sm:rounded-lg">
          <p class="font-medium text-lg text-gray-900">Available Sessions</p>

          <div class="mt-1 max-w-xl">
            <p class="text-gray-700 text-sm">
              If you would like to switch to a different session, choose one from the
              dropdown and click the button below.
            </p>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
              <div>
                <InputLabel for="sessionSelect" :value="'Available Sessions'" />
                <SelectInput
                  :key="selectKey"
                  id="sessionSelect"
                  v-model="form.session_id"
                  class="w-full"
                  :class="{ 'border-red-600': form.errors.session_id }"
                >
                  <option value="" disabled>-- Select a Session --</option>
                  <template v-for="s in sessions" :key="s.id">
                    <option
                      v-if="s.slotLeft > 0 && s.id !== (session?.id ?? '')"
                      :value="s.id"
                    >
                      {{ s.name }} - {{ s.course_time }} ({{ s.slotLeft }} slot{{
                        s.slotLeft != 1 ? "s" : ""
                      }}
                      left)
                    </option>
                  </template>
                </SelectInput>
                <InputError :message="form.errors.session_id" />
              </div>
              <PrimaryButton type="submit" :disabled="form.processing"
                >{{ admission.confirmed ? "update" : "confirm" }} session
              </PrimaryButton>
            </form>
          </div>
        </div>

        <div class="p-6 bg-white shadow sm:rounded-lg">
          <p class="font-medium text-lg text-gray-900">
            {{ session?.id ?? false ? "Revoke" : "Decline" }} Admission
          </p>

          <div class="mt-1 max-w-xl">
            <RevokeOrDeclineAdmissionModal :user="user" :session="session" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
