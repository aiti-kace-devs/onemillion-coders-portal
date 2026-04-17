<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch, nextTick, onMounted } from "vue";
import SelectInput from "@/Components/SelectInput.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  user: {
    type: Object,
  },
});

const form = useForm({
  name: props.user.student_name || props.user.name || "",
  first_name: props.user.first_name || "",
  middle_name: props.user.middle_name || "",
  last_name: props.user.last_name || "",
  card_type: "ghcard",
  ghcard: props.user.ghcard || "",
  gender: props.user.gender || "",
  network_type: props.user.network_type || "",
  mobile_no: props.user.mobile_no || "",
  email: props.user.email || "",
});

const confirmationModal = ref(false);

const detailsUpdated = computed(() => !!props.user.details_updated_at);
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

// always collect first/middle/last name from inputs; no switching UI

watch(
  () => form.card_type,
  async (newVal) => {
    await nextTick();
    const ghcardInput = $("#ghcard");
    if (newVal === "ghcard") {
      ghcardInput.inputmask({
        mask: "555555555-5",
        definitions: {
          5: {
            validator: "[0-9]",
          },
        },
        oncomplete: function () {
          form.ghcard = ghcardInput.val();
        },
      });
      // Sync on every input, not just on complete
      ghcardInput.on("input", function () {
        form.ghcard = $(this).val();
      });
    } else {
      ghcardInput.inputmask("remove");
      ghcardInput.off("input");
    }
  }
);

onMounted(async () => {
  await nextTick();
  const ghcardInput = $("#ghcard");
  if (form.card_type === "ghcard") {
    ghcardInput.inputmask({
      mask: "555555555-5",
      definitions: {
        5: {
          validator: "[0-9]",
        },
      },
      oncomplete: function () {
        form.ghcard = ghcardInput.val();
      },
    });
    ghcardInput.on("input", function () {
      form.ghcard = $(this).val();
    });
  } else {
    ghcardInput.inputmask("remove");
    ghcardInput.off("input");
  }
});

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

      <div class="mt-1">
        <p v-if="detailsUpdated" class="text-sm text-red-600">
          You have already updated your details
        </p>

        <p v-else class="text-sm text-gray-600">
          Update your account's profile information and ID details. You can only update
          your details once, so verify all information before submitting.
        </p>
      </div>
    </header>

    <form class="grid lg:grid-cols-2 gap-6 mt-6">
      <!-- Always show separate name fields -->
        <div>
          <InputLabel for="first_name" value="First Name" :required="true" />
          <TextInput
            id="first_name"
            type="text"
            class="block w-full"
            v-model="form.first_name"
            required
            :disabled="detailsUpdated"
            :class="{ 'border-red-600': form.errors.first_name }"
          />
          <InputError :message="form.errors.first_name" />
        </div>

        <div>
          <InputLabel for="last_name" value="Last Name" :required="true" />
          <TextInput
            id="last_name"
            type="text"
            class="block w-full"
            v-model="form.last_name"
            required
            :disabled="detailsUpdated"
            :class="{ 'border-red-600': form.errors.last_name }"
          />
          <InputError :message="form.errors.last_name" />
        </div>

        <div>
          <InputLabel for="middle_name" value="Middle Name (Optional)" />
          <TextInput
            id="middle_name"
            type="text"
            class="block w-full"
            v-model="form.middle_name"
            :disabled="detailsUpdated"
            :class="{ 'border-red-600': form.errors.middle_name }"
          />
          <InputError :message="form.errors.middle_name" />
        </div>

        <div>
          <InputLabel for="gender" value="Gender" :required="true" />
          <SelectInput
            id="gender"
            v-model="form.gender"
            :disabled="detailsUpdated"
            required
            class="block w-full"
            :class="{ 'border-red-600': form.errors.gender }"
          >
            <option value="" disabled>-- Select Gender --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </SelectInput>
          <InputError :message="form.errors.gender" />
        </div>


        <div class="col-span-full">
          <InputLabel for="ghcard" value="Ghana Card Number" :required="true" />
          <div class="flex">
            <span
              class="inline-flex items-center px-4 border border-r-0 text-gray-500 font-medium text-sm rounded-l-xl h-11 bg-gray-50 border-gray-200"
              id="ghcard-addon"
              :class="{
                'text-green-600 border-green-600': cardVerified,
                'border-red-600': form.errors.ghcard,
                'cursor-not-allowed bg-gray-100 text-gray-400': detailsUpdated,
              }"
              >GHA-</span
            >
            <TextInput
              id="ghcard"
              type="text"
              class="block w-full"
              v-model="form.ghcard"
              required
              :disabled="detailsUpdated"
              :class="{
                'border-l-0 rounded-l-none': true,
                'border-green-600': cardVerified,
                'border-red-600': form.errors.ghcard,
              }"
              placeholder="123456789-1"
              aria-describedby="ghcard-addon"
            />
          </div>
          <InputError :message="form.errors.ghcard" />
          
          <div
            v-if="!form.errors.ghcard"
            class="mt-1 text-xs inline-flex items-center"
            :class="cardStatusClass"
          >
            {{ cardStatusLabel }}
          </div>
        </div>

      <div>
        <InputLabel for="network_type" value="Network Type" :required="true" />
        <SelectInput
          id="network_type"
          v-model="form.network_type"
          :disabled="detailsUpdated"
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
          :disabled="detailsUpdated"
          placeholder="+233..."
        />
        <InputError :message="form.errors.mobile_no" />
      </div>

      <div class="col-span-full">
        <InputLabel for="email" value="Email" :required="true" />
        <TextInput
          id="email"
          type="email"
          class="block w-full"
          :class="{ 'border-red-600': form.errors.email }"
          v-model="form.email"
          :disabled="detailsUpdated"
          required
          autocomplete="username"
        />
        <InputError :message="form.errors.email" />
      </div>

      <div class="flex items-center gap-4" v-if="!detailsUpdated">
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
          Are you sure you want to submit this update?
        </h2>

        <p class="mt-1 text-sm text-center text-gray-600">
          This cannot be undone. Make sure all details are correct.
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
