<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import LinkButton from "@/Components/LinkButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import DangerButton from "@/Components/DangerButton.vue";
import RadioInput from "@/Components/RadioInput.vue";

export default {
  components: {
    AuthenticatedLayout,
    Head,
    LinkButton,
    PrimaryButton,
    TextInput,
    InputError,
    InputLabel,
    SelectInput,
    Checkbox,
    DangerButton,
    RadioInput,
  },
  props: {
    errors: Object,
    admissionForm: Object,
  },
  data() {
    const formFields = {
      form_uuid: this.admissionForm.uuid,
      response_data: {},
    };

    this.admissionForm.schema.forEach((schema) => {
      if (
        ["text", "number", "email", "file", "password", "radio"].includes(schema.type)
      ) {
        formFields.response_data[schema.field_name] = null;
      } else if (schema.type === "checkbox") {
        formFields.response_data[schema.field_name] = [];
      } else if (schema.type === "select") {
        formFields.response_data[schema.field_name] = "";
      }
    });

    const form = useForm(formFields);

    return {
      form,
    };
  },
  methods: {
    submit() {
      this.form.post(route("form_responses.store"), {
        onSuccess: () => {
          toastr.success("Entry successfully submitted");
          this.resetForm();
        },
        onError: () => {
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
  <Head title="Forms | Preview Form" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center">
        Forms
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        Preview Form
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div>
              <p class="text-2xl font-bold capitalize">{{ admissionForm.title }}</p>
            </div>

            <div class="mt-4">
              <form @submit.prevent="submit">
                <div class="space-y-5">
                  <div v-for="(question, index) in admissionForm.schema" :key="index">
                    <div>
                      <InputLabel
                        :for="`field-${index}`"
                        :value="question.title"
                        :required="question.is_required"
                      />
                      <TextInput
                        v-if="
                          ['text', 'number', 'email', 'password'].includes(question.type)
                        "
                        :id="`field-${index}`"
                        :type="question.type"
                        class="mt-1 w-full"
                        v-model="form.response_data[question.field_name]"
                        :placeholder="question.title"
                        :class="{
                          'block w-full mt-2 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100':
                            question.type == 'file',
                          'file:bg-red-600 hover:file:bg-red-500 file:text-white':
                            question.type == 'file',
                          'border-red-600':
                            form.errors[`response_data.${question.field_name}`],
                        }"
                      />

                      <!-- Select Input -->
                      <div v-else-if="question.type === 'select'">
                        <SelectInput
                          :id="question.field_name"
                          v-model="form.response_data[question.field_name]"
                          class="mt-1 w-full"
                        >
                          <option value="" disabled selected>
                            -- Select an option --
                          </option>
                          <option
                            v-for="option in question.options.split(',')"
                            :key="option.trim()"
                            :value="option.trim()"
                          >
                            {{ option.trim() }}
                          </option>
                        </SelectInput>
                      </div>

                      <div
                        class="flex items-center space-x-4"
                        v-else-if="question.type === 'checkbox'"
                      >
                        <div
                          class="mt-1 flex items-center space-x-2"
                          v-for="(option, idx) in question.options.split(',')"
                          :key="idx"
                        >
                          <Checkbox
                            :id="`field-${index}-option-${idx}`"
                            v-model:checked="form.response_data[question.field_name]"
                            :value="option.trim()"
                          />
                          <InputLabel
                            :for="`field-${index}-option-${idx}`"
                            :value="option.trim()"
                          />
                        </div>
                      </div>

                      <div
                        class="flex items-center gap-4"
                        v-else-if="question.type == 'radio'"
                      >
                        <div
                          class="mt-1 flex items-center space-x-2"
                          v-for="(option, idx) in question.options.split(',')"
                          :key="idx"
                        >
                          <RadioInput
                            :id="`field-${index}-option-${idx}`"
                            v-model:checked="form.response_data[question.field_name]"
                            :value="option.trim()"
                          />
                          <InputLabel
                            :for="`field-${index}-option-${idx}`"
                            :value="option.trim()"
                          />
                        </div>
                      </div>
                      <InputError
                        :message="form.errors[`response_data.${question.field_name}`]"
                      />
                    </div>
                  </div>

                  <div>
                    <PrimaryButton
                      type="submit"
                      :disabled="form.processing"
                      :class="{ 'opacity-25': form.processing }"
                    >
                      Submit
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
