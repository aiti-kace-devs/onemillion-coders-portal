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
import DangerButton from "@/Components/DangerButton.vue";

export default {
  components: {
    AuthenticatedLayout,
    Head,
    PrimaryButton,
    DangerButton,
    TextInput,
    InputError,
    InputLabel,
    SelectInput,
    Checkbox,
    FileInput,
    TextAreaInput,
  },
  props: {
    isCreateMethod: Boolean,
    errors: Object,
    questionnaire: Object,
  },
  data() {
    const sections = ref([]);
    const selections = ref([]);
    const fileInput = ref(null);

    const imageConfig = ref({
      image: this.questionnaire?.image ?? null,
      isDirty: this.isCreateMethod ?? false,
      preview: this.questionnaire?.image ?? null,
      original: this.questionnaire?.image ?? null,
    });

    const form = useForm({
      title: this.questionnaire?.title ?? null,
      description: this.questionnaire?.description ?? null,
      image: this.questionnaire?.image ?? null,
      code: this.questionnaire?.code ?? null,
      message_when_inactive: this.questionnaire?.message_when_inactive ?? null,
      message_after_submission: this.questionnaire?.message_after_submission ?? null,
      active: this.questionnaire?.active ?? false,
      schema: this.questionnaire?.schema ?? [],
    });

    return {
      form,
      imageConfig,
      fileInput,
      sections,
      selections,
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
        ? route("admin.questionnaire.store")
        : route("admin.questionnaire.update", {
            questionnaire: this.questionnaire.uuid,
            isDirty: this.imageConfig.isDirty,
            _method: "put",
          });
    },
    successMessage() {
      return this.isCreateMethod
        ? "Questionnaire successfully saved"
        : "Questionnaire successfully updated";
    },
  },
  mounted() {
    if (!this.questionnaire?.schema) return;

    this.questionnaire.schema.forEach((schema, sectionIndex) => {
      const newSection = {
        type: schema?.type,
        title: schema?.title,
        description: schema?.description,
        questions: [],
      };

      //   this.sections.push(newSection);
      this.addSection(newSection);

      schema?.questions?.forEach((question) => {
        const newField = {
          id: `field_${this.selections.length + 1}`, // Unique ID
          label: question?.label || `Field ${this.selections.length + 1}`, // Default label
          title: question?.title,
          description: question?.description,
          type: question?.type, // Default type
          placeholder: "Question", // Placeholder
          options: question?.options || null, // Options for dropdown/select fields
          rules: question?.rules || "",
          validators: {
            required: question?.validators.required || false,
            unique: question?.validators.unique || false,
          },
        };

        this.addSelection(sectionIndex, newField);

        // this.sections[sectionIndex].questions.push(newField);
        // this.selections.push(newField);
      });
    });
  },
  watch: {
    sections: {
      handler(newSections) {
        // Sync sections with form.schema
        this.form.schema = newSections;
      },
      deep: true,
    },
    selections: {
      handler(newSelections) {
        // Sync selections with form.schema
        this.form.schema = newSelections;
      },
      deep: true,
    },
  },
  methods: {
    addSection(sectionDetails = null) {
      const newSection = sectionDetails || {
        title: null,
        description: null,
        questions: [],
      };

      this.sections.push(newSection);
      this.form.clearErrors("schema");
    },

    addSelection(sectionIndex, fieldDetails = null) {
      if (!this.sections[sectionIndex]) return;

      if (!this.sections[sectionIndex].questions) {
        this.sections[sectionIndex].questions = [];
      }

      const newField = fieldDetails || {
        id: `field_${sectionIndex}_${this.sections[sectionIndex].questions.length + 1}`, // More robust unique ID
        label: `Field ${sectionIndex} ${
          this.sections[sectionIndex].questions.length + 1
        }`,
        title: null,
        description: null,
        type: "text",
        placeholder: "Question",
        options: null,
        validators: {
          required: true,
          unique: false,
        },
      };

      this.sections[sectionIndex].questions.push(newField);
      this.form.clearErrors("schema");
    },

    removeSection(index) {
      // Remove the field at the specified index
      this.sections.splice(index, 1);
    },
    removeSelection(sectionIndex, index) {
      this.sections[sectionIndex].questions.splice(index, 1);
    },

    changeSelectionType(sectionIndex, questionIndex) {
      const question = this.sections[sectionIndex]?.questions?.[questionIndex];
      if (!question) return;

      this.form.clearErrors(`schema.${sectionIndex}.questions.${questionIndex}.options`);

      question.options = ["radio", "checkbox"].includes(question.type)
        ? "very bad, bad, average, good, very good"
        : null;
    },
    moveField(index, direction) {
      const swapIndex = direction === "up" ? index - 1 : index + 1;

      if (swapIndex < 0 || swapIndex >= this.selections.length) return;

      const temp = this.selections[index];
      this.selections[index] = this.selections[swapIndex];
      this.selections[swapIndex] = temp;
    },

    handleImageOnChange(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.previewImage(file);
      this.imageConfig.image = file;
      this.imageConfig.isDirty = true;
      this.form.image = file;
    },
    previewImage(file) {
      // Use FileReader to read the file and generate a data URL
      const reader = new FileReader();

      reader.onload = (e) => {
        this.imageConfig.preview = e.target.result;
      };

      reader.readAsDataURL(file);
    },
    removeImage() {
      this.imageConfig.preview = null;
      this.imageConfig.image = null;
      this.imageConfig.isDirty = false;
      this.resetInput();
    },
    restoreImage() {
      this.imageConfig.preview = this.imageConfig.original;
      this.imageConfig.image = null;
      this.imageConfig.isDirty = false;
    },
    resetInput() {
      if (this.fileInput) {
        this.fileInput = ""; // Clear file input
      }
    },
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
  <Head :title="'Questionnaires | ' + this.mode + ' Questionnaire'" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center">
        Questionnaires
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        {{ this.mode }} Questionnaire
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-transparent overflow-hidden shadow-none sm:rounded-lg">
          <div class="p-6">
            <div>
              <form @submit.prevent="submit" class="space-y-5">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <div class="p-6">
                    <div class="grid gap-5">
                      <div>
                        <InputLabel for="title" value="Title" :required="true" />
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
                        <InputLabel
                          for="description"
                          value="Description"
                          :required="false"
                        />
                        <TextAreaInput
                          v-model="form.description"
                          class="w-full"
                          :class="{ 'border-red-600': form.errors.description }"
                        />

                        <InputError :message="form.errors.description" />
                      </div>

                      <div>
                        <InputLabel for="image" value="image" :required="false" />

                        <div class="flex flex-col gap-6 mt-3">
                          <!-- preview section -->
                          <div
                            v-if="imageConfig.preview"
                            class="relative h-28 w-28 md:w-56 md:h-36"
                          >
                            <img
                              :src="imageConfig.preview"
                              alt="Preview"
                              class="w-full h-full object-cover rounded-lg shadow-md"
                            />

                            <button
                              @click="removeImage"
                              class="inline-flex absolute top-0 right-0 bg-red-600 text-white p-1 rounded-full shadow-lg hover:bg-red-700"
                            >
                              <span class="material-symbols-outlined"> close </span>
                            </button>
                          </div>

                          <!-- Upload Button -->
                          <div>
                            <FileInput
                              ref="fileInput"
                              id="image-upload"
                              class="hidden"
                              @change="handleImageOnChange"
                              accept="image/*"
                            />
                            <label
                              for="image-upload"
                              class="cursor-pointer text-sm py-2 px-4 bg-gray-100 text-gray-700 rounded-md shadow hover:bg-gray-200"
                            >
                              Choose Image
                            </label>
                          </div>

                          <!-- Restore Button -->
                          <div v-if="this.mode == 'Edit' && imageConfig.isDirty">
                            <button
                              @click="restoreImage"
                              class="py-2 px-4 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                              Restore Original
                            </button>
                          </div>
                        </div>

                        <InputError class="mt-2" :message="form.errors.image" />
                      </div>

                      <div>
                        <InputLabel for="code" value="Unique Code" :required="true" />
                        <TextInput
                          id="code"
                          type="text"
                          class="w-full"
                          v-model="form.code"
                          :placeholder="'Unique Code'"
                          autocomplete="code"
                          :class="{ 'border-red-600': form.errors.code }"
                        />
                        <InputError :message="form.errors.code" />
                      </div>
                      <!-- message after registration -->
                      <div>
                        <InputLabel
                          for="message_after_submission"
                          value="Message After Submission"
                          :required="true"
                        />
                        <TextInput
                          id="message_after_submission"
                          type="text"
                          class="w-full"
                          v-model="form.message_after_submission"
                          :placeholder="'Message After Submission'"
                          :class="{
                            'border-red-600': form.errors.message_after_submission,
                          }"
                        />
                        <InputError :message="form.errors.message_after_submission" />
                      </div>

                      <!-- message when inactive -->
                      <div>
                        <InputLabel
                          for="message_when_inactive"
                          value="Message When Inactive"
                          :required="true"
                        />
                        <TextInput
                          id="code"
                          type="text"
                          class="w-full"
                          v-model="form.message_when_inactive"
                          :placeholder="'Message When Inactive'"
                          :class="{ 'border-red-600': form.errors.message_when_inactive }"
                        />
                        <InputError :message="form.errors.message_when_inactive" />
                      </div>

                      <!-- status -->
                      <div>
                        <!-- <InputLabel
                          for="active"
                          value="Form Accepting Responses"
                          :required="true"
                        /> -->
                        <div>
                          <label
                            class="inline-flex items-center cursor-pointer space-x-3 text-sm"
                          >
                            Active (Accept Responses)
                            <Checkbox
                              v-model:checked="form.active"
                              class="sr-only peer"
                            />
                            <div
                              class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-700 peer-disabled:cursor-not-allowed"
                            ></div>
                          </label>
                        </div>
                        <InputError :message="form.errors.active" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Sections -->
                <div
                  v-for="(section, row) in sections"
                  :key="row"
                  class="bg-white overflow-hidden shadow-sm sm:rounded-lg space-y-4"
                >
                  <div class="p-6">
                    <p class="font-semibold text-xl text-gray-400 mb-5">
                      Section {{ row + 1 }}
                    </p>
                    <div class="grid gap-5">
                      <div>
                        <div class="grid lg:grid-cols-3 gap-4">

                          <div class="lg:col-span-1">
                            <SelectInput
                              :id="section.id"
                              v-model="section.type"
                              class="w-full"
                            >
                              <option value="facility" selected>Facility</option>
                              <option value="course">Course</option>
                              <option value="instructor">Instructor</option>
                              <option value="others">Others</option>
                            </SelectInput>
                          </div>

                          <div v-if="section.type === 'others'" class="lg:col-span-full">
                            <InputLabel for="title" value="Title" :required="false" />
                            <TextInput
                              :id="section.id"
                              type="text"
                              class="w-full"
                              v-model="section.title"
                              :placeholder="section.placeholder"
                              :class="{
                                'border-red-600': form.errors[`schema.${row}.title`],
                              }"
                            />
                            <InputError :message="form.errors[`schema.${row}.title`]" />
                          </div>

                          <div class="col-span-full">
                            <InputLabel
                              for="description"
                              value="Description"
                              :required="false"
                            />
                            <TextAreaInput
                              v-model="section.description"
                              class="w-full"
                              :class="{
                                'border-red-600':
                                  form.errors[`schema.${row}.description`],
                              }"
                            />

                            <InputError
                              :message="form.errors[`schema.${row}.description`]"
                            />
                          </div>
                        </div>

                        <div class="grid gap-5 mt-6">
                          <div
                            class="border border-gray-400 p-6 rounded-lg shadow-sm space-y-4"
                            v-for="(selection, index) in section.questions"
                            :key="index"
                          >
                            <div class="grid grid-cols-3 gap-4">
                              <div class="col-span-2">
                                <TextInput
                                  :id="selection.id"
                                  type="text"
                                  class="w-full"
                                  v-model="selection.title"
                                  :placeholder="selection.placeholder"
                                  :class="{
                                    'border-red-600':
                                      form.errors[
                                        `schema.${row}.questions.${index}.title`
                                      ],
                                  }"
                                />
                                <InputError
                                  :message="
                                    form.errors[`schema.${row}.questions.${index}.title`]
                                  "
                                />
                              </div>

                              <div>
                                <SelectInput
                                  @change="changeSelectionType(row, index)"
                                  :id="'input_type_' + row"
                                  v-model="selection.type"
                                  class="w-full"
                                >
                                  <option value="text" selected>Short answer</option>
                                  <option value="textarea">Long answer</option>
                                  <option value="checkbox">Checkbox</option>
                                  <option value="radio">Radio</option>
                                </SelectInput>
                              </div>

                              <div class="col-span-full">
                                <InputLabel
                                  for="description"
                                  value="Description"
                                  :required="false"
                                />
                                <TextAreaInput
                                  v-model="selection.description"
                                  class="w-full"
                                  :class="{
                                    'border-red-600':
                                      form.errors[
                                        `schema.${row}.questions.${index}.description`
                                      ],
                                  }"
                                />

                                <InputError
                                  :message="
                                    form.errors[
                                      `schema.${row}.questions.${index}.description`
                                    ]
                                  "
                                />
                              </div>

                              <div
                                class="col-span-full"
                                v-if="['radio', 'checkbox'].includes(selection.type)"
                              >
                                <TextInput
                                  :id="selection.id"
                                  type="text"
                                  class="w-full"
                                  v-model="selection.options"
                                  :placeholder="'Options (comma-separated)'"
                                  :class="{
                                    'border-red-600':
                                      form.errors[
                                        `schema.${row}.questions.${index}.options`
                                      ],
                                  }"
                                />

                                <InputError
                                  :message="
                                    form.errors[
                                      `schema.${row}.questions.${index}.options`
                                    ]
                                  "
                                />
                              </div>
                            </div>

                            <div class="flex justify-end items-center">
                              <!-- <div
                                class="flex flex-col items-center"
                                :class="{
                                  'gap-y-2.5': row !== 0 && row !== selections.length - 1,
                                }"
                              >
                                <div>
                                  <button
                                    @click="moveField(row, 'up')"
                                    v-if="row !== 0"
                                    type="button"
                                    class="w-11 h-8 flex items-center justify-center border border-transparent bg-gray-100 rounded-sm shadow-sm p-1 disabled:opacity-25 disabled:cursor-not-allowed"
                                  >
                                    <span
                                      class="material-symbols-outlined text-2xl font-bold text-gray-800"
                                    >
                                      keyboard_arrow_up
                                    </span>
                                  </button>
                                </div>

                                <div>
                                  <button
                                    @click="moveField(row, 'down')"
                                    v-if="row !== selections.length - 1"
                                    type="button"
                                    class="w-11 h-8 flex items-center justify-center border border-transparent bg-gray-100 rounded-sm shadow-sm p-1 disabled:opacity-25 disabled:cursor-not-allowed"
                                  >
                                    <span
                                      class="material-symbols-outlined text-2xl font-bold text-gray-800"
                                    >
                                      keyboard_arrow_down
                                    </span>
                                  </button>
                                </div>
                              </div> -->

                              <div>
                                <DangerButton
                                  type="button"
                                  @click="removeSelection(row, index)"
                                  class="h-8 w-9 flex items-center justify-center"
                                >
                                  <span class="material-symbols-outlined"> delete </span>
                                </DangerButton>
                              </div>
                            </div>
                          </div>

                          <div>
                            <div class="flex justify-between items-center">
                              <PrimaryButton @click="addSelection(row)" type="button">
                                <span class="material-symbols-outlined mr-2"> add </span>
                                add question
                              </PrimaryButton>

                              <div>
                                <DangerButton
                                  type="button"
                                  @click="removeSection(row)"
                                  class="h-8 w-9 flex items-center justify-center"
                                >
                                  <span class="material-symbols-outlined"> delete </span>
                                </DangerButton>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-if="form.errors.schema"
                  class="bg-red-100 rounded-md p-4 border border-red-200 text-red-700 text-md"
                >
                  <span class="font-bold">Oops!</span>
                  {{ form.errors.schema }}
                </div>

                <div>
                  <PrimaryButton @click="addSection" type="button">
                    <span class="material-symbols-outlined mr-2"> add </span>
                    add section
                  </PrimaryButton>
                </div>

                <div>
                  <PrimaryButton
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-25': form.processing }"
                  >
                    {{ this.submitButtonText }}
                  </PrimaryButton>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
