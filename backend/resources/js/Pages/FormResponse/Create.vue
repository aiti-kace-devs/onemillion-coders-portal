<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import LinkButton from "@/Components/LinkButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { ref } from "vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import DangerButton from "@/Components/DangerButton.vue";

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
  },
  props: {
    errors: Object,
  },
  data() {
    const selections = ref([]);

    const form = useForm({
      title: null,
      schema: [],
    });

    return {
      form,
      selections,
    };
  },
  watch: {
    selections: {
      handler(newSelections) {
        // Sync selections with form.schema
        this.form.schema = newSelections;
      },
      deep: true,
    },
  },
  methods: {
    addSelection() {
      // Add a new field with default values
      const newField = {
        id: `field_${this.selections.length + 1}`, // Unique ID
        label: `Field ${this.selections.length + 1}`, // Default label
        title: null,
        type: "text", // Default type
        placeholder: "Question", // Placeholder
        options: null, // Options for dropdown/select fields
        is_required: false, // Default required status
      };

      this.selections.push(newField);

      this.form.clearErrors("schema");
    },
    removeSelection(index) {
      this.selections.splice(index, 1);
    },
    changeSelectionType(index) {
      const selection = this.selections[index];
      if (selection.type !== "select") {
        selection.options = null;
      }
    },
    moveField(index, direction) {
      const swapIndex = direction === "up" ? index - 1 : index + 1;

      if (swapIndex < 0 || swapIndex >= this.selections.length) return;

      const temp = this.selections[index];
      this.selections[index] = this.selections[swapIndex];
      this.selections[swapIndex] = temp;
    },
    submit() {
      this.form.post(route("admin.form.store"), {
        onSuccess: () => {
          toastr.success("Form successfully saved");
          this.resetForm();
        },
        onError: (errors) => {
          toastr.error("Something went wrong");
        },
      });
    },
    resetForm() {
      // Clear form and selections after successful submission
      this.form.reset();
      this.form.clearErrors();
      this.selections = [];
    },
  },
};
</script>

<template>
  <Head title="Forms | Create Form" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center">
        Forms
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        Create Form
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div>
              <form @submit.prevent="submit">
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

                  <!-- Questions -->
                  <div
                    class="border border-gray-400 p-6 rounded-lg shadow-sm space-y-4"
                    v-for="(selection, row) in selections"
                    :key="row"
                  >
                    <div class="grid grid-cols-3 gap-x-4">
                      <div class="col-span-2">
                        <TextInput
                          :id="selection.id"
                          type="text"
                          class="w-full"
                          v-model="selection.title"
                          :placeholder="selection.placeholder"
                          :class="{
                            'border-red-600': form.errors[`schema.${row}.title`],
                          }"
                        />
                        <InputError :message="form.errors[`schema.${row}.title`]" />
                      </div>

                      <div>
                        <SelectInput
                          @change="changeSelectionType(row)"
                          :id="'input_type_' + row"
                          v-model="selection.type"
                          class="w-full"
                        >
                          <option value="text" selected>Text</option>
                          <option value="textarea">Textarea</option>
                          <option value="select">Select</option>
                          <option value="checkbox">Checkbox</option>
                          <option value="radio">Radio</option>
                          <option value="number">Number</option>
                        </SelectInput>
                      </div>
                    </div>

                    <div>
                      <div>
                        <TextInput
                          v-if="['select', 'radio', 'checkbox'].includes(selection.type)"
                          :id="selection.id"
                          type="text"
                          class="w-full"
                          v-model="selection.options"
                          :placeholder="'Options (comma-separated)'"
                          :class="{
                            'border-red-600': form.errors[`schema.${row}.options`],
                          }"
                        />
                        <InputError :message="form.errors[`schema.${row}.options`]" />
                      </div>
                    </div>

                    <div class="flex justify-between items-center">
                      <div>
                        <label class="inline-flex items-center cursor-pointer space-x-3 text-sm">
                          Required
                          <Checkbox
                            v-model:checked="selection.is_required"
                            class="sr-only peer"
                          />
                          <div
                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-700 peer-disabled:cursor-not-allowed"
                          ></div>
                        </label>
                      </div>

                      <div
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
                      </div>

                      <div>
                        <DangerButton
                          type="button"
                          @click="removeSelection(row)"
                          class="h-8 w-9 flex items-center justify-center"
                        >
                          <span class="material-symbols-outlined"> delete </span>
                        </DangerButton>
                      </div>
                    </div>
                  </div>

                  <div
                    v-if="form.errors.schema"
                    class="bg-red-100 rounded-md p-4 border border-red-200 text-red-700 text-md"
                  >
                    <span class="font-bold">Oops!</span> {{ form.errors.schema }}
                  </div>

                  <div>
                    <PrimaryButton @click="addSelection" type="button">
                      <span class="material-symbols-outlined mr-2"> add </span>
                      add question
                    </PrimaryButton>
                  </div>

                  <div>
                    <PrimaryButton
                      type="submit"
                      :disabled="form.processing"
                      :class="{ 'opacity-25': form.processing }"
                      >save
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
