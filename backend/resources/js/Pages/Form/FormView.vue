<script>
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
import CourseSelect from "@/Components/CourseSelect.vue";
import FileInput from "@/Components/FileInput.vue";
import { PhoneInput } from "@lbgm/phone-number-input";
export default {
  components: {
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
    CourseSelect,
    FileInput,
    PhoneInput,
  },
  props: {
    errors: Object,
    admissionForm: Object,
    courses: Array,
    branches: Array,
    admin: Boolean,
    centres: Array,
  },
  data() {
    const formFields = {
      form_uuid: this.admissionForm.uuid,
      response_data: {},
    };

    let formIsActive = this.admin ? true : this.admissionForm.active;

    let showForm = this.admin ? true : formIsActive;
    let showFormMessage = false;

    this.admissionForm.schema.forEach((schema) => {
      if (
        ["text", "number", "email", "file", "password", "radio", "phonenumber"].includes(
          schema.type
        )
      ) {
        formFields.response_data[schema.field_name] = null;
      } else if (schema.type === "checkbox") {
        formFields.response_data[schema.field_name] = [];
      } else if (schema.type === "select") {
        formFields.response_data[schema.field_name] = "";
      } else if (schema.type === "select_course") {
        formFields.response_data.course_id = null;
      }
    });

    const form = useForm(formFields);


    const admissionInstructionUrl = route("application");

    // scrool to form in 4 seconds
    setTimeout(() => {
      const formElement = document.querySelector("#form-container");
      // if small screen
      const isSmallScreen = window.innerWidth < 1024;
      if (formElement && isSmallScreen) {
        this.smoothScrollToElement(formElement, 2000);
      }
    }, 1500);

    return {
      form,
      showFormMessage,
      showForm,
      formIsActive,
      phoneError: false,
      admissionInstructionUrl,
    };
  },
  methods: {
    submit() {
      this.form.post(route("admin.form_responses.store"), {
        onSuccess: () => {
          toastr.success("Entry successfully submitted");
          this.resetForm();
          this.showMessage();

          setTimeout(() => {
            window.location.href = route("application");
          }, 5000);
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
    showMessage() {
      this.showFormMessage = true;
      this.showForm = false;
      this.formIsActive = true;
    },
    validatePhone(data, field_name) {
      if (data.isValid) {
        this.form.response_data[field_name] = data.number;
        this.phoneError = false;
      } else if (data.isValid === false) {
        this.form.errors[field_name] = true;
        this.form.response_data[field_name] = null;
        this.phoneError = true;
      }
    },
 smoothScrollToElement(element, duration) {
    if (!element) return; // Exit if element is not found

    const startTime = performance.now();
    const startPosition = window.pageYOffset;
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
    const distance = targetPosition - startPosition;

    function scrollStep(timestamp) {
        const timeElapsed = timestamp - startTime;
        const progress = Math.min(timeElapsed / duration, 1); // Ensure progress doesn't exceed 1

        window.scrollTo(0, startPosition + distance * progress);

        if (timeElapsed < duration) {
        requestAnimationFrame(scrollStep);
        }
  }

  requestAnimationFrame(scrollStep);
}
  },
};
</script>
<style>

input:not(input[type="radio"]), select {
    background-color: #e8ffec !important;
}

div[data-children="inputcore"] {
  background-color: transparent;
  padding-top: 0;
  padding-bottom: 0;
  border-color: rgb(209 213 219 / var(--tw-border-opacity, 1));
  border-radius: 0.125rem;
  font-size: 0.875rem; /* 14px */
  line-height: 1.25rem; /* 20px */
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

div[data-children="inputcore"]:focus-within {
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(55 65 81 / var(--tw-ring-opacity));
  --tw-ring-offset-shadow: 0 0 #0000;
  --tw-ring-shadow: 0 0 0 1.7px var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), 0 0 #0000; /* Resets any other shadows */
  outline: none; /* Removes default browser outline */
}

input[data-children="htmlInput"] {
  font-size: 0.875rem; /* 14px */
  line-height: 1.25rem; /* 20px */
  outline: none;
}

input[data-children="htmlInput"]:focus {
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
}

div[data-widget-item="baseinput"].border-red-600 div[data-children="inputcore"] {
  border-color: rgb(220 38 38 / var(--tw-border-opacity, 1));
}

div[data-children="inputcore"].baseinput-core{
    /* padding-inline: 0px !important; */
    background-color: #e8ffec !important;

}

input#phone {
    border: 0 !important;
    background-color: #e8ffec !important;

}

.form-image {
    min-height: 55rem;
    max-height: 60rem;
    padding: 50px 0 0 50px;
}

@media (max-width: 1024px) {
    .form-image {
        height: 30rem;
        padding: 10px !important;

    }
}
</style>
<template>
  <Head title="Registration" />
  <div class="lg:py-12  bg-gray-200" v-if="showForm && formIsActive">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div>
        <div class="flex flex-col lg:flex-row">
          <!-- Image Section -->
          <div v-if="admissionForm.image" class="lg:order-2 w-full sticky top-0 lg:relative bg-white form-image">
            <img
              :src="admissionForm.image"
              alt=""
              class="object-contain w-full h-full"
              loading="lazy"
            />
          </div>

          <!-- Form Section -->
          <div class="lg:order-1 bg-white rounded-sm p-6 w-full lg:w-1/2 z-10">
            <div>
              <p class="text-2xl font-bold capitalize">{{ admissionForm.title }}</p>
              <p v-if="admissionForm.description" class="text-sm text-gray-600">
                {{ admissionForm.description }}
              </p>
            </div>

            <div id="form-container">
              <form @submit.prevent="submit" class="mt-4">
                <div class="space-y-5">
                  <div v-for="(question, index) in admissionForm.schema" :key="index">
                    <div>
                      <InputLabel
                        v-if="question.type != 'select_course'"
                        :for="`field-${index}`"
                        :value="question.title"
                        :required="question.validators.required"
                      />
                      <TextInput
                        v-if="
                          ['text', 'number', 'email', 'password'].includes(question.type)
                        "
                        :id="`field-${index}`"
                        :type="question.type"
                        class="mt-1 w-full"
                        v-model="form.response_data[question.field_name]"
                        :required="question.validators.required"
                        :placeholder="question.title"
                        :class="{
                          'border-red-600':
                            form.errors[`response_data.${question.field_name}`],
                        }"
                      />

                      <!-- File Input -->
                      <div v-else-if="question.type === 'file'">
                        <FileInput
                          class="mt-1"
                          :required="question.validators.required"
                          @input="
                            form.response_data[question.field_name] =
                              $event.target.files[0]
                          "
                          :maxSize="2 * 1024"
                          :accept="
                            question.options
                              ? question.options
                                  .split(',')
                                  .map((type) => '.' + type.trim())
                              : []
                          "
                          :class="{
                            'file:bg-red-600 hover:file:bg-red-500 file:text-white':
                              form.errors[`response_data.${question.field_name}`],
                          }"
                        />
                      </div>

                      <!-- Select Input -->
                      <div v-else-if="question.type === 'select'">
                        <SelectInput
                          :id="question.field_name"
                          v-model="form.response_data[question.field_name]"
                          class="mt-1 w-full"
                          :required="question.validators.required"
                          :class="{
                            'border-red-600':
                              form.errors[`response_data.${question.field_name}`],
                          }"
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

                      <!-- Phone Input -->
                      <div v-else-if="question.type === 'phonenumber'">
                        <phone-input
                          :has-error="phoneError"
                          :errorMessage="
                            phoneError ? 'You have entered an invalid phone number' : ''
                          "
                          :defaultCountry="'GH'"
                          :required="question.validators.required"
                          :id="question.field_name"
                          :name="question.field_name"
                          v-model="form.response_data[question.field_name]"
                          :placeholder="question.title"
                          @phoneData="validatePhone($event, question.field_name)"
                          :listHeight="250"
                          :allowed="['GH']"
                          class="mt-1"
                          :class="{
                            'border-red-600':
                              form.errors[`response_data.${question.field_name}`],
                          }"
                        />
                      </div>

                      <!-- Select Location and Course  -->
                      <div v-else-if="question.type === 'select_course'">
                        <CourseSelect
                          :branches="this.branches"
                          :courses="this.courses"
                          :centres="this.centres"
                          :form="form"
                          :id="question.field_name"
                          :required="true"
                        ></CourseSelect>
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
                            :required="question.validators.required"
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
                            :required="question.validators.required"
                            :value="option.trim()"
                          />
                          <InputLabel
                            :for="`field-${index}-option-${idx}`"
                            :value="option.trim()"
                          />
                        </div>
                      </div>
                      <div v-if="question.description" class="mt-1">
                        <p class="text-xs text-blue-400">{{ question.description }}</p>
                      </div>
                      <InputError
                        :message="form.errors[`response_data.${question.field_name}`]"
                      />
                    </div>
                  </div>

                  <div>
                    <PrimaryButton
                      v-if="!admin"
                      type="submit"
                      :disabled="form.processing || phoneError"
                      :class="{ 'opacity-25': form.processing, 'bg-green-800': true }"
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
  </div>

  <div v-if="showFormMessage && formIsActive">
    <div class="p-6">
      <p class="text-2xl font-bold capitalize text-green-500">
        {{ admissionForm.message_after_registration }}
      </p>
    </div>
    <iframe style="display: block; height: 98vh; width: 100vw;" :src="admissionInstructionUrl">Your browser isn't compatible</iframe>
  </div>

  <div class="p-6" v-if="!formIsActive">
    <div>
      <p class="text-2xl font-bold capitalize">
        {{ admissionForm.message_when_inactive }}
      </p>
    </div>
  </div>
</template>
