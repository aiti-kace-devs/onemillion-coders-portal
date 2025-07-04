<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
  user: Object,
});
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <div
            class="bg-white rounded-xl flex flex-col md:flex-row items-center gap-6 relative"
          >
          <div>
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT7eIctknfvw2DNDOUAbE75S8yicWnxyInS2A&s" class="h-26 w-26 bg-gray-600 rounded object-cover" alt="profile photo">
          </div>
            <div class="flex-1 w-full text-center md:text-left">
              <div class="text-xl font-semibold text-gray-900">
                {{ user.student_name }}
              </div>
              <div class="text-sm text-gray-500">Team Manager</div>
              <div class="text-sm text-gray-400">Leeds, United Kingdom</div>
            </div>
            <button
              class="absolute top-4 right-4 bg-gray-100 hover:bg-gray-200 rounded-full p-2 shadow text-gray-600 focus:outline-none"
            >
              <span class="material-symbols-outlined">edit</span>
            </button>
          </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
            :user="user"
            class="max-w-xl"
          />
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <DeleteUserForm class="max-w-xl" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
