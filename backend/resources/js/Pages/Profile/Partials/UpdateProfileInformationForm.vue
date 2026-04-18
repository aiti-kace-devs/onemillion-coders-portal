<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    network_type: user.network_type,
    mobile_no: user.mobile_no,
});

const networkTypes = [
    { value: 'mtn', label: 'MTN' },
    { value: 'telecel', label: 'Telecel' },
    { value: 'airteltigo', label: 'AirtelTigo' },
];
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your profile information.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-gray-100 cursor-not-allowed"
                    :model-value="user.name"
                    readonly
                    autocomplete="name"
                />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-gray-100 cursor-not-allowed"
                    :model-value="user.email"
                    readonly
                    autocomplete="username"
                />
            </div>

            <div>
                <InputLabel for="network_type" value="Network Type" />

                <select
                    id="network_type"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    v-model="form.network_type"
                    required
                >
                    <option value="" disabled>Select network</option>
                    <option v-for="network in networkTypes" :key="network.value" :value="network.value">
                        {{ network.label }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.network_type" />
            </div>

            <div>
                <InputLabel for="mobile_no" value="Phone Number" />

                <TextInput
                    id="mobile_no"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.mobile_no"
                    required
                    autofocus
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.mobile_no" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
