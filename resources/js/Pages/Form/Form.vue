<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FileInput from "@/Components/FileInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";

export default {
  components: {
    AuthenticatedLayout,
    Head,
    PrimaryButton,
    TextInput,
    InputError,
    InputLabel,
    SelectInput,
    Checkbox,
    FileInput,
    TextAreaInput
  },
  props: {
    isCreateMethod: Boolean,
    errors: Object,
    admissionForm: Object,
  },
  data() {
    const selections = ref([]);
            const fileInput = ref(null);

            const imageConfig = ref({
                image: this.admissionForm?.image,
                isDirty: false,
                preview: this.admissionForm?.image,
                original: this.admissionForm?.image,
            });

    const form = useForm({
      title: this.category?.title ?? null
    });

    return {
      form
    };
  },
  computed: {
    // Determine mode
    mode() {
      return this.isCreateMethod ? "Create" : "Edit";
    },
    submitMethod() {
      return this.isCreateMethod ? "post" : "put";
    },
    submitButtonText() {
      return this.isCreateMethod ? "save" : "update";
    },
    submitRoute() {
      return this.isCreateMethod
        ? route("admin.course_category.store")
        : route("admin.course_category.update", {
            category: this.category.id,
            _method: "put",
          });
    },
    successMessage() {
      return this.isCreateMethod
        ? "Category successfully saved!"
        : "Category successfully updated!";
    },
  },
  methods: {
    submit() {
      this.form.post(this.submitRoute, {
        onSuccess: () => {
          toastr.success(this.successMessage);
          this.resetForm();
        },
        onError: (errors) => {
          toastr.error("Something went wrong");
        },
      });
    },
    resetForm() {
      this.form.reset();
      this.form.clearErrors();
    },
  },
};
</script>

<template>
  <Head :title="'Course Categories | ' + this.mode + ' Category'" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center">
        Course Categories
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        {{ this.mode }} Category
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-none sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submit">
              <div class="flex flex-col lg:w-1/2 gap-5">
                <div>
                  <InputLabel for="title" value="title" :required="true" />
                  <TextInput
                    id="title"
                    type="text"
                    class="w-full"
                    v-model="form.title"
                    :placeholder="'Title'"
                    autocomplete="title"
                    :class="{ 'border-red-600': form.errors.title }"
                  />
                  <InputError :message="form.errors.title" />
                </div>

                <div>
                  <PrimaryButton
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-25': form.processing }"
                    >{{ this.submitButtonText }}
                  </PrimaryButton>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
