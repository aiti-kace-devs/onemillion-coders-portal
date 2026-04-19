<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SelectInput from "@/Components/SelectInput.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  user: {
    type: Object,
  },
});

const form = useForm({
  network_type: props.user.network_type || "",
  mobile_no: props.user.mobile_no || "",
});

const confirmationModal = ref(false);

const cardVerified = computed(() => !!props.user.ghcard_verified);
const latestAttempt = computed(() => props.user.ghcard_latest_attempt ?? null);
const cardStatus = computed(() => {
  if (cardVerified.value || latestAttempt.value?.verified) {
    return "verified";
  }

  if (!latestAttempt.value) {
    return "pending";
  }

  if (latestAttempt.value.success) {
    return "processed";
  }

  return "failed";
});
const cardStatusLabel = computed(() => {
  switch (cardStatus.value) {
    case "verified":
      return "Card verified successfully";
    case "processed":
      return "Card processed";
    case "failed":
      return "Card verification failed";
    default:
      return "Card pending";
  }
});
const cardStatusClass = computed(() => {
  switch (cardStatus.value) {
    case "verified":
      return "text-green-600";
    case "failed":
      return "text-red-600";
    case "processed":
      return "text-amber-600";
    default:
      return "text-gray-700";
  }
});

const showConfirmationModal = () => {
  confirmationModal.value = true;
};

const closeConfirmationModal = () => {
  if (form.processing) {
    form.cancel();
  }

  confirmationModal.value = false;
};

const submit = () => {
  form.patch(route("student.profile.update"), {
    preserveScroll: true,
    onSuccess: () => {
      toastr.success("Your details have been updated successfully.");
      closeConfirmationModal();
      form.clearErrors();
    },
    onError: (errors) => {
      toastr.error("Something went wrong");
      closeConfirmationModal();
    },
  });
};
</script>

<template>
  <section>
    <header>
      <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

      <p class="mt-1 text-sm text-gray-600">
        You can update your network type and phone number below.
      </p>
    </header>

    <form class="grid lg:grid-cols-2 gap-6 mt-6">
      <!-- Read-only fields -->
        <div>
          <InputLabel for="first_name" value="First Name" />
          <TextInput
            id="first_name"
            type="text"
            class="block w-full bg-gray-50"
            :value="user.first_name || ''"
            disabled
          />
        </div>

        <div>
          <InputLabel for="last_name" value="Last Name" />
          <TextInput
            id="last_name"
            type="text"
            class="block w-full bg-gray-50"
            :value="user.last_name || ''"
            disabled
          />
        </div>

        <div>
          <InputLabel for="middle_name" value="Middle Name" />
          <TextInput
            id="middle_name"
            type="text"
            class="block w-full bg-gray-50"
            :value="user.middle_name || ''"
            disabled
          />
        </div>

        <div>
          <InputLabel for="gender" value="Gender" />
          <SelectInput
            id="gender"
            :value="user.gender || ''"
            disabled
            class="block w-full bg-gray-50"
          >
            <option value="" disabled>-- Select Gender --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </SelectInput>
        </div>


        <div class="col-span-full">
          <InputLabel for="ghcard" value="Ghana Card Number" />
          <div class="flex">
            <span
              v-if="!String(user.ghcard || '').startsWith('GHA-')"
              class="inline-flex items-center px-4 border border-r-0 text-gray-500 font-medium text-sm rounded-l-xl h-11 bg-gray-100 border-gray-200 cursor-not-allowed text-gray-400"
              id="ghcard-addon"
              :class="{
                'text-green-600 border-green-600': cardVerified,
              }"
              >GHA-</span
            >
            <TextInput
              id="ghcard"
              type="text"
              class="block w-full bg-gray-50"
              :value="user.ghcard || ''"
              disabled
              :class="{
                'border-l-0 rounded-l-none': !String(user.ghcard || '').startsWith('GHA-'),
                'border-green-600': cardVerified,
              }"
              placeholder="123456789-1"
              aria-describedby="ghcard-addon"
            />
          </div>

          <div
            class="mt-1 text-xs inline-flex items-center"
            :class="cardStatusClass"
          >
            {{ cardStatusLabel }}
          </div>
        </div>

      <!-- Editable fields -->
      <div>
        <InputLabel for="network_type" value="Network Type" :required="true" />
        <SelectInput
          id="network_type"
          v-model="form.network_type"
          required
          class="block w-full"
          :class="{ 'border-red-600': form.errors.network_type }"
        >
          <option value="" disabled>-- Select Network --</option>
          <option value="mtn">MTN</option>
          <option value="telecel">Telecel</option>
          <option value="airteltigo">AirtelTigo</option>
        </SelectInput>
        <InputError :message="form.errors.network_type" />
      </div>

      <div>
        <InputLabel for="mobile_no" value="Phone Number" :required="true" />
        <TextInput
          id="mobile_no"
          type="text"
          class="block w-full"
          :class="{ 'border-red-600': form.errors.mobile_no }"
          v-model="form.mobile_no"
          required
          placeholder="+233..."
        />
        <InputError :message="form.errors.mobile_no" />
      </div>

      <div class="col-span-full">
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          type="email"
          class="block w-full bg-gray-50"
          :value="user.email || ''"
          disabled
          autocomplete="username"
        />
      </div>

      <div class="flex items-center gap-4">
        <PrimaryButton type="button" @click="showConfirmationModal">Update</PrimaryButton>
      </div>
    </form>

    <Modal
      :show="confirmationModal"
      :closeable="true"
      :modalTitle="'Confirm Submission'"
      @close="closeConfirmationModal"
      :maxWidth="'md'"
    >
      <div class="p-6">
        <h2 class="text-lg text-center font-medium text-gray-900">
          Confirm Update
        </h2>

        <p class="mt-1 text-sm text-center text-gray-600">
          Are you sure you want to update your network type and phone number?
        </p>

        <div class="mt-6 flex justify-center">
          <SecondaryButton @click="closeConfirmationModal">Cancel</SecondaryButton>

          <PrimaryButton
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="submit"
          >
            Submit
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </section>
</template>
