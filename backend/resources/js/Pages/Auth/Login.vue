<script setup>
import { ref, onMounted } from "vue";
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { useReCaptcha } from 'vue-recaptcha-v3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const passwordVisible = ref(false);
const loaded = ref(false);

const { executeRecaptcha, recaptchaLoaded } = useReCaptcha();

onMounted(async () => {
    try {
        // Wait for the reCAPTCHA script to be fully loaded and ready
        await recaptchaLoaded();
        loaded.value = true;
    } catch (e) {
        console.error("reCAPTCHA failed to load", e);
    }
});

const submit = async () => {
    if (!loaded.value) {
        try {
            await recaptchaLoaded();
        } catch (e) {
            console.error("Submission blocked: reCAPTCHA not available");
            return;
        }
    }
    const token = await executeRecaptcha('student_login');
    form.transform((data) => ({ ...data, recaptcha_token: token }))
        .post(route("login"), {
            onFinish: () => setTimeout(() => form.reset("password"), 1500),
        });
};
</script>

<template>
    <GuestLayout>

        <Head title="Student Login" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <p class="text-lg font-bold text-gray-900 mb-4">Welcome Back. Log in to continue</p>
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autofocus
                    autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <div class="relative">
                    <TextInput id="password" :type="passwordVisible ? 'text' : 'password'"
                        class="mt-1 block w-full pr-12" v-model="form.password" required
                        autocomplete="current-password" />

                    <button type="button" @click="passwordVisible = !passwordVisible"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg v-if="!passwordVisible" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <!-- v-if="canResetPassword" -->
                <Link :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
            <div class="min-h-[20px] mt-1">
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100">
                    <span v-if="loaded" class="text-[10px] text-gray-400 flex items-center gap-1">
                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        This site is protected by Google reCAPTCHA
                    </span>
                </transition>
            </div>

        </form>
    </GuestLayout>
</template>
