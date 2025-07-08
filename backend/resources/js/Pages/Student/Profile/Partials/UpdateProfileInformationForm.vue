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
  card_type: props.user.card_type || "",
  ghcard: props.user.ghcard || "",
  gender: props.user.gender || "",
  network_type: props.user.network_type || "",
  mobile_no: props.user.mobile_no || "",
  email: props.user.email || "",
});

const confirmationModal = ref(false);

const detailsUpdated = computed(() => !!props.user.details_updated_at);

// Card verification status (mocked for now)
const cardVerified = computed(() => !!props.user.verification_date);

const showConfirmationModal = () => {
  confirmationModal.value = true;
};

const closeConfirmationModal = () => {
  if (form.processing) {
    form.cancel();
  }

  confirmationModal.value = false;
};

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
        <p v-if="detailsUpdated" class="text-sm text-green-600">
          You have already updated your details
        </p>

        <p v-else class="text-sm text-gray-600">
          Update your account's profile information and ID details. You can only update
          your details once, so verify all information before submitting.
        </p>
      </div>
    </header>

    <form class="mt-6 space-y-6">
      <div>
        <InputLabel
          for="name"
          value="Fullname (as appears on your Ghana Card / any National ID)"
        />
        <TextInput
          id="name"
          type="text"
          class="block w-full"
          v-model="form.name"
          required
          :disabled="detailsUpdated"
          :class="{ 'border-red-600': form.errors.name }"
        />
        <InputError :message="form.errors.name" />
      </div>

      <div>
        <InputLabel for="gender" value="Gender" />
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

      <div>
        <InputLabel for="card_type" value="Card Type" />
        <SelectInput
          id="card_type"
          v-model="form.card_type"
          :disabled="detailsUpdated"
          required
          class="block w-full"
          :class="{ 'border-red-600': form.errors.card_type }"
        >
          <option value="" disabled>-- Select Card Type --</option>
          <option value="ghcard">Ghana Card</option>
          <option value="voters_id">Voter's ID</option>
          <option value="drivers_license">Driver's License</option>
          <option value="passport">Passport</option>
        </SelectInput>
        <InputError :message="form.errors.card_type" />
      </div>

      <div>
        <InputLabel for="ghcard" value="Card ID" />
        <div class="flex">
          <span
            v-if="form.card_type === 'ghcard'"
            class="inline-flex items-center px-3 border border-r-0 text-sm rounded-l-sm h-10"
            id="ghcard-addon"
            :class="{
              'text-gray-700 border-green-600': cardVerified,
              'border-red-600': !cardVerified,
              'cursor-not-allowed bg-gray-200 text-gray-700': detailsUpdated,
              'border-red-600': form.errors.ghcard,
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
              'border-l-0': form.card_type === 'ghcard',
              'border-green-600': cardVerified,
              'border-red-600': !cardVerified,
              'rounded-none rounded-r-sm': form.card_type === 'ghcard',
              'border-red-600': form.errors.ghcard,
            }"
            placeholder="123456789-1"
            :aria-describedby="form.card_type === 'ghcard' ? 'ghcard-addon' : undefined"
          />
        </div>
        <InputError :message="form.errors.ghcard" />

        <div
          v-if="!form.errors.ghcard"
          class="mt-1 text-sm inline-flex items-center"
          :class="cardVerified ? 'text-green-600' : 'text-gray-700'"
        >
          {{
            cardVerified
              ? "Card verified successfully"
              : "Card not verified (This will be done manually by an administrator)"
          }}
        </div>
      </div>

      <div>
        <InputLabel for="network_type" value="Network Type" />
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
        <InputLabel for="mobile_no" value="Phone Number" />
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

      <div>
        <InputLabel for="email" value="Email" />
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
