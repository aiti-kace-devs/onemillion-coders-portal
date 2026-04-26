<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import PremiumSelect from "@/Components/PremiumSelect.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  user: {
    type: Object,
  },
});

const genderOptions = [
    { label: "Male", value: "male" },
    { label: "Female", value: "female" },
];

const networkOptions = [
    { label: "MTN", value: "mtn" },
    { label: "Telecel (Vodafone)", value: "telecel" },
    { label: "AirtelTigo", value: "airteltigo" },
];

const form = useForm({
  first_name: props.user.first_name || "",
  last_name: props.user.last_name || "",
  middle_name: props.user.middle_name || "",
  gender: props.user.gender || "",
  ghcard: props.user.ghcard ? String(props.user.ghcard).replace(/^GHA-/, '') : "",
  network_type: props.user.network_type || "",
  mobile_no: props.user.mobile_no || "",
});

const confirmationModal = ref(false);

const cardVerified = computed(() => !!props.user.ghcard_verified);

const cardStatusLabel = computed(() => {
  return cardVerified.value ? "Verified" : "Not Verified";
});

const cardStatusClass = computed(() => {
  return cardVerified.value
    ? "text-green-700 bg-green-50 px-2 py-0.5 rounded-md border border-green-200"
    : "text-red-700 bg-red-50 px-2 py-0.5 rounded-md border border-red-200";
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

const isContactEditable = true;
const isIdentityEditable = computed(() => !cardVerified.value);

watch(() => form.mobile_no, (newVal) => {
  if (newVal) {
    const cleaned = newVal.replace(/[^0-9+]/g, "");
    if (cleaned !== newVal) {
      form.mobile_no = cleaned;
    }
  }
});

const onlyNumbers = (e) => {
  const charCode = e.which ? e.which : e.keyCode;
  // Allow numbers (48-57) and + (43)
  if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 43) {
    e.preventDefault();
  }
};
</script>

<template>
  <section>
    <header>
      <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

      <p class="mt-1 text-sm text-gray-600">
        You can update your personal and contact details below. Identification details are locked once you Ghana card has been verified.
      </p>
    </header>

    <div class="mt-8 max-w-4xl">
      <form class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6" @submit.prevent>
        <!-- Identity Section Header -->
        <div class="col-span-full mb-2">
          <div class="flex items-center gap-2 text-gray-400 mb-2">
            <span class="material-symbols-outlined text-sm">fingerprint</span>
            <span class="text-xs font-bold uppercase tracking-widest">Identity Details</span>
          </div>
          <div class="h-px bg-gray-100 w-full"></div>
        </div>

        <!-- Read-only fields -->
        <div class="space-y-1">
          <InputLabel for="first_name" value="First Name" :required="isIdentityEditable" :class="!isIdentityEditable ? 'text-gray-500' : ''" />
          <TextInput
            id="first_name"
            type="text"
            class="block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500"
            :class="{
                'bg-gray-50 border-gray-200 text-gray-500': !isIdentityEditable,
                'border-red-600': form.errors.first_name
            }"
            v-model="form.first_name"
            :disabled="!isIdentityEditable"
          />
          <InputError v-if="isIdentityEditable" :message="form.errors.first_name" />
        </div>

        <div class="space-y-1">
          <InputLabel for="last_name" value="Last Name" :required="isIdentityEditable" :class="!isIdentityEditable ? 'text-gray-500' : ''" />
          <TextInput
            id="last_name"
            type="text"
            class="block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500"
            :class="{
                'bg-gray-50 border-gray-200 text-gray-500': !isIdentityEditable,
                'border-red-600': form.errors.last_name
            }"
            v-model="form.last_name"
            :disabled="!isIdentityEditable"
          />
          <InputError v-if="isIdentityEditable" :message="form.errors.last_name" />
        </div>

        <div class="space-y-1">
          <InputLabel for="middle_name" value="Middle Name" :class="!isIdentityEditable ? 'text-gray-500' : ''" />
          <TextInput
            id="middle_name"
            type="text"
            class="block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500"
            :class="{
                'bg-gray-50 border-gray-200 text-gray-500': !isIdentityEditable,
                'border-red-600': form.errors.middle_name
            }"
            v-model="form.middle_name"
            :disabled="!isIdentityEditable"
          />
          <InputError v-if="isIdentityEditable" :message="form.errors.middle_name" />
        </div>

        <div class="space-y-1">
          <InputLabel for="gender" value="Gender" :required="isIdentityEditable" :class="!isIdentityEditable ? 'text-gray-500' : ''" />
          <PremiumSelect
            id="gender"
            v-model="form.gender"
            :options="genderOptions"
            :disabled="!isIdentityEditable"
            :error="form.errors.gender"
            placeholder="-- Select Gender --"
          />
          <InputError v-if="isIdentityEditable" :message="form.errors.gender" />
        </div>

        <div class="col-span-full mt-2">
          <InputLabel for="ghcard" value="Ghana Card Number" :required="isIdentityEditable" :class="!isIdentityEditable ? 'text-gray-500' : ''" />
          <div
            class="flex mt-1 rounded-xl shadow-sm border"
            :class="[
              form.errors.ghcard ? 'border-red-600' : 'border-gray-200',
              isIdentityEditable ? 'focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 bg-white' : 'bg-gray-50'
            ]"
          >
            <span
              class="inline-flex items-center px-4 border-r border-gray-200 text-gray-400 font-medium text-sm h-11 bg-gray-50 cursor-not-allowed rounded-l-xl"
              id="ghcard-addon"
              :class="{
                'text-green-600 bg-green-50 border-gray-100': cardVerified,
              }"
              >GHA-</span
            >
            <input
              id="ghcard"
              type="text"
              class="block w-full border-0 focus:ring-0 focus:outline-none h-11 rounded-r-xl px-3 text-sm"
              :class="{
                'bg-gray-50 text-gray-500 cursor-not-allowed': !isIdentityEditable,
                'bg-green-50 text-green-700': cardVerified,
                'bg-white': isIdentityEditable && !cardVerified
              }"
              v-model="form.ghcard"
              :disabled="!isIdentityEditable"
              placeholder="123456789-1"
              aria-describedby="ghcard-addon"
            />
          </div>
          <InputError v-if="isIdentityEditable" :message="form.errors.ghcard" />

          <div
            class="mt-2 text-xs font-semibold uppercase tracking-wider inline-flex items-center"
            :class="cardStatusClass"
          >
            {{ cardStatusLabel }}
          </div>
        </div>

        <!-- Contact Section Header -->
        <div class="col-span-full mt-6 mb-2">
          <div class="flex items-center gap-2 text-gray-400 mb-2">
            <span class="material-symbols-outlined text-sm">contact_page</span>
            <span class="text-xs font-bold uppercase tracking-widest">Contact Information</span>
          </div>
          <div class="h-px bg-gray-100 w-full"></div>
        </div>

        <!-- Editable fields -->
        <div class="space-y-1">
          <InputLabel for="network_type" value="Network Type" :required="true" />
          <PremiumSelect
            id="network_type"
            v-model="form.network_type"
            :options="networkOptions"
            :error="form.errors.network_type"
            placeholder="-- Select Network --"
          />
          <InputError :message="form.errors.network_type" />
        </div>

        <div class="space-y-1">
          <InputLabel for="mobile_no" value="Phone Number" :required="true" />
          <TextInput
            id="mobile_no"
            type="text"
            class="block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500"
            :class="{
              'border-red-600': form.errors.mobile_no,
              'bg-gray-50 text-gray-500 cursor-not-allowed': !isContactEditable
            }"
            v-model="form.mobile_no"
            @input="form.clearErrors('mobile_no')"
            @keypress="onlyNumbers"
            :required="isContactEditable"
            :disabled="!isContactEditable"
            placeholder="+233..."
          />
          <InputError v-if="isContactEditable" :message="form.errors.mobile_no" />
        </div>

        <div class="col-span-full space-y-1">
          <InputLabel for="email" value="Email Address" class="text-gray-500" />
          <TextInput
            id="email"
            type="email"
            class="block w-full bg-gray-50 border-gray-200 text-gray-500"
            :value="user.email || ''"
            disabled
            autocomplete="username"
          />
        </div>

        <div class="col-span-full pt-4">
          <PrimaryButton
            type="button"
            @click="showConfirmationModal"
            class="px-8 py-3 bg-amber-500 hover:bg-amber-600 text-black font-bold rounded-xl transition-all shadow-md shadow-amber-200"
          >
            Update
          </PrimaryButton>
        </div>
      </form>
    </div>

    <Modal
      :show="confirmationModal"
      :closeable="true"
      @close="closeConfirmationModal"
      :maxWidth="'sm'"
    >
      <div class="p-6">

        <div class="flex flex-col items-center text-center">
          <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 mb-4">
            <span class="material-symbols-outlined text-3xl">info</span>
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-1">
            Confirm Update
          </h2>

          <p class="text-sm text-gray-500 max-w-[240px] mx-auto text-balance">
            Are you sure you want to update your profile information?
          </p>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
          <SecondaryButton
            @click="closeConfirmationModal"
            class="w-full sm:w-auto px-6 py-2.5 justify-center border-gray-200 text-gray-600 hover:bg-gray-50"
          >
            Cancel
          </SecondaryButton>

          <PrimaryButton
            class="w-full sm:w-auto px-6 py-2.5 justify-center bg-amber-500 hover:bg-amber-600 text-black font-bold rounded-xl transition-all shadow-md shadow-amber-200"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="submit"
          >
            {{ form.processing ? 'Updating...' : 'Yes, Update' }}
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </section>
</template>
