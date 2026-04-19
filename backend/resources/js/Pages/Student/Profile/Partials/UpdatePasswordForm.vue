<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const successMessage = ref('');

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const updatePassword = () => {
    successMessage.value = '';
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            successMessage.value = 'Password updated successfully.';
            setTimeout(() => { successMessage.value = ''; }, 5000);
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Password</h2>

            <p class="mt-1 text-sm text-gray-600">
                Password must be 6–64 characters with at least one uppercase letter, one lowercase letter, and one number.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6 max-w-xl">
            <div>
                <InputLabel for="current_password" value="Current Password" />

                <div class="relative mt-1">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="block w-full pr-10"
                        @input="form.clearErrors('current_password')"
                        autocomplete="current-password"
                    />
                    <button 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-700 transition-colors"
                        @click="showCurrentPassword = !showCurrentPassword"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            {{ showCurrentPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="New Password" />

                <div class="relative mt-1">
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showNewPassword ? 'text' : 'password'"
                        class="block w-full pr-10"
                        @input="form.clearErrors('password')"
                        autocomplete="new-password"
                    />
                    <button 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-700 transition-colors"
                        @click="showNewPassword = !showNewPassword"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            {{ showNewPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <div class="relative mt-1">
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        class="block w-full pr-10"
                        @input="form.clearErrors('password_confirmation')"
                        autocomplete="new-password"
                    />
                    <button 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-700 transition-colors"
                        @click="showConfirmPassword = !showConfirmPassword"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            {{ showConfirmPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="successMessage" class="text-sm text-green-600 font-medium">{{ successMessage }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
