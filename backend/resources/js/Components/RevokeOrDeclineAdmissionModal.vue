<script setup>
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  user: Object,
  session: {
    type: Object,
    default: null,
  },
});

const form = useForm({});

const revokeOrDeclineModal = ref(false);

const showModal = () => {
  revokeOrDeclineModal.value = true;
};

const closeModal = () => {
  revokeOrDeclineModal.value = false;
};

const submit = () => {
  form.delete(
    route("student.session.destroy", {
      user: props.user.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toastr.success("Admission successfully revoked!");
        closeModal();
      },
      onError: (errors) => {
        toastr.error("Something went wrong");
      },
    }
  );
};
</script>

<template>
  <p class="text-gray-700 text-sm">
    If the terms of this admission are unfavorable and you wish to decline, please click
    the button below. Revoking your current admission is a required step if you want to
    switch to a different course or be eligible for future admission opportunities.
  </p>

  <div class="mt-6">
    <DangerButton @click="showModal">
      {{ session?.id ?? false ? "Revoke" : "Decline" }}
      Admission
    </DangerButton>
  </div>

  <Modal
    :show="revokeOrDeclineModal"
    :modalTitle="(session?.id ? 'Revoke' : 'Decline') + ' Admission'"
    @close="closeModal"
  >
    <div class="p-6">
      <h2 class="text-lg text-center font-medium text-gray-900">
        Are you sure you want to
        {{ session?.id ?? false ? "revoke" : "decline" }} admission?
      </h2>

      <p class="mt-1 text-sm text-center text-gray-600">
        Please note, this action is irreversible and your slot
        <span class="font-bold">WILL NOT</span> be reserved!
      </p>

      <div class="mt-6 flex justify-center">
        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>

        <DangerButton
          class="ms-3"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
          @click="submit"
        >
          <template v-if="session?.id ?? false">Revoke</template>
          <template v-else>Decline</template>
        </DangerButton>
      </div>
    </div>
  </Modal>
</template>
