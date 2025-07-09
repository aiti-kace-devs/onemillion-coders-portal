<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  user: Object,
  admission: Object,
  course: Object,
  sessions: Array,
  session: Object,
});

const form = useForm({
  session_id: "",
});

const revokeForm = useForm({});

const handleSubmit = () => {
  form.post(route("session.select-session", props.user.userId));
};

const revokeAdmissionModal = ref(false);

const showRevokeAdmission = () => {
  revokeAdmissionModal.value = true;
};

const closeRevokeAdmision = () => {
  revokeAdmissionModal.value = false;
  revokeForm.cancel();
};

const revokeAdmission = () => {
  revokeForm.delete(
    route("student.session.destroy", {
      user: props.user.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toastr.success("Admission successfully revoked!");
        closeRevokeAdmision();
      },
      onError: (errors) => {
        toastr.error("Something went wrong");
      },
    }
  );
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
                >You have already selected
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
              dropdown and click the "Update Session" button.
            </p>

            <form @submit.prevent="handleSubmit" class="mt-6 space-y-6">
              <div>
                <InputLabel for="sessionSelect" :value="'Available Sessions'" />
                <SelectInput
                  id="sessionSelect"
                  v-model="form.session_id"
                  :disabled="admission.confirmed"
                  class="w-full"
                >
                  <option value="" disabled>-- Select a Session --</option>
                  <template v-for="s in sessions" :key="s.id">
                    <option
                      v-if="s.slotLeft > 0 && s.id !== (session?.id ?? '')"
                      :value="s.id"
                    >
                      {{ s.name }} ({{ s.slotLeft }} slots left) - {{ s.course_time }}
                    </option>
                  </template>
                </SelectInput>
              </div>
              <PrimaryButton
                type="submit"
                :disabled="admission.confirmed || form.processing"
              >
                <template v-if="admission.confirmed"
                  >{{ admission.confirmed ? "update" : "confirm" }} session</template
                >
              </PrimaryButton>
            </form>
          </div>
        </div>

        <div class="p-6 bg-white shadow sm:rounded-lg">
          <p class="font-medium text-lg text-gray-900">
            <template v-if="session?.id ?? false">Revoke</template>
            <template v-else>Decline</template> Admission
          </p>

          <div class="mt-1 max-w-xl">
            <p class="text-gray-700 text-sm">
              If the terms of this admission are unfavorable and you wish to decline,
              please click the button below. Revoking your current admission is a required
              step if you want to switch to a different course or be eligible for future
              admission opportunities.
            </p>

            <div class="mt-6">
              <DangerButton @click="showRevokeAdmission">
                <template v-if="session?.id ?? false">Revoke</template>
                <template v-else>Decline</template>
                Admission
              </DangerButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal
      :show="revokeAdmissionModal"
      :modalTitle="(session?.id ? 'Revoke' : 'Decline') + ' Admission'"
      @close="closeRevokeAdmision"
    >
      <div class="p-6">
        <h2 class="text-lg text-center font-medium text-gray-900">
          Are you sure you want to
          <template v-if="session?.id ?? false">revoke</template>
          <template v-else>decline</template> admission?
        </h2>

        <p class="mt-1 text-sm text-center text-gray-600">
          Please note, this action is irreversible and your slot
          <span class="font-bold">WILL NOT</span> be reserved!
        </p>

        <div class="mt-6 flex justify-center">
          <SecondaryButton @click="closeRevokeAdmision">Cancel</SecondaryButton>

          <DangerButton
            class="ms-3"
            :class="{ 'opacity-25': revokeForm.processing }"
            :disabled="revokeForm.processing"
            @click="revokeAdmission"
          >
            <template v-if="session?.id ?? false">Revoke</template>
            <template v-else>Decline</template>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
