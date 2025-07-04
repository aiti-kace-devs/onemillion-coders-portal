<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import SelectInput from "@/Components/SelectInput.vue";

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
  user: {
    type: Object
  }
});

const user = usePage().props.auth.user;

const form = useForm({
  name: user.student_name || user.name || "",
  card_type: user.card_type || "",
  ghcard: user.ghcard || "",
  gender: user.gender || "",
  network_type: user.network_type || "",
  mobile_no: user.mobile_no || "",
  email: user.email || "",
});

const detailsUpdated = computed(() => !!user.details_updated_at);
const isAdmitted = computed(() => user.isAdmitted);

// Card verification status (mocked for now)
const cardVerified = computed(() => !!user.verification_date);
</script>

<template>
  <section>
    <header>
      <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>   
      
      <p v-if="detailsUpdated"
        class="text-sm text-green-600"
      >
        You have already updated your details
      </p>

      <p v-else class="mt-1 text-sm text-gray-600">
        Update your account's profile information and ID details. You can only update your
        details once, so verify all information before submitting.
      </p>
    </header>

    <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
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
        />
        <InputError :message="form.errors.name" />
      </div>

      <div>
        <InputLabel for="gender" value="Gender" />
        <SelectInput
          id="gender"
          v-model="form.gender"
          :disabled="!!user.gender"
          required
          class="block w-full"
        >
          <option value="">Select Gender</option>
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
        >
          <option value="">Select Card Type</option>
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
            :class="
              detailsUpdated
                ? 'cursor-not-allowed bg-gray-200 text-gray-700 border-green-600'
                : 'bg-gray-50 text-gray-500 border-red-600'
            "
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
              'border-l-0 border-green-600': cardVerified,
              'border-l-0 border-red-600': cardVerified,
              'rounded-none rounded-r-sm': form.card_type === 'ghcard',
            }"
            placeholder="123456789-1"
            :aria-describedby="form.card_type === 'ghcard' ? 'ghcard-addon' : undefined"
          />
        </div>
        <InputError :message="form.errors.ghcard" />

        <div
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
          :disabled="!!user.network_type"
          required
          class="block w-full"
        >
          <option value="">Select Network</option>
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
          v-model="form.mobile_no"
          required
          :disabled="!!user.mobile_no"
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
          v-model="form.email"
          :disabled="!!user.email"
          required
          autocomplete="username"
        />
        <InputError :message="form.errors.email" />
      </div>

      <div class="flex items-center gap-4" v-if="!detailsUpdated">
        <PrimaryButton :disabled="form.processing">Update</PrimaryButton>
      </div>
    </form>

    <div v-if="isAdmitted" class="mt-8">
      <div class="text-md">Location : {{ user.location }}</div>
      <div class="text-md">Course : {{ user.course_name }}</div>
      <div class="text-md">Session : {{ user.selected_session }}</div>
      <div class="text-lg font-bold mt-2">Student ID for Attendance</div>
      <!-- QR code placeholder -->
      <div class="my-4 flex justify-center">
        <div class="w-56 h-56 bg-gray-200 flex items-center justify-center rounded">
          <!-- QR code will be rendered here -->
          <span class="text-gray-400">[QR CODE]</span>
        </div>
      </div>
      <button type="button" class="btn btn-primary">Download</button>
    </div>
  </section>
</template>
