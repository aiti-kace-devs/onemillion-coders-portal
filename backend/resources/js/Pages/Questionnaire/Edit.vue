<script>
    import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
    import {
        Head,
        useForm
    } from "@inertiajs/vue3";
    import LinkButton from "@/Components/LinkButton.vue";
    import PrimaryButton from "@/Components/PrimaryButton.vue";
    import TextInput from "@/Components/TextInput.vue";
    import InputError from "@/Components/InputError.vue";
    import InputLabel from "@/Components/InputLabel.vue";
    import {
        ref
    } from "vue";
    import SelectInput from "@/Components/SelectInput.vue";
    import Checkbox from "@/Components/Checkbox.vue";
    import DangerButton from "@/Components/DangerButton.vue";
    import TextAreaInput from "@/Components/TextAreaInput.vue";
    import FileInput from "@/Components/FileInput.vue";

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
            TextAreaInput,
            FileInput,
        },
        props: {
            errors: Object,
            admissionForm: Object,
        },
        data() {
            const selections = ref([]);
            const fileInput = ref(null);

            const imageConfig = ref({
                image: this.admissionForm.image,
                isDirty: false,
                preview: this.admissionForm.image,
                original: this.admissionForm.image,
            });

            const form = useForm({
                title: this.admissionForm.title,
                description: this.admissionForm.description,
                image: this.admissionForm.image,
                code: this.admissionForm.code,
                message_when_inactive: this.admissionForm.message_when_inactive,
                message_after_registration: this.admissionForm.message_after_registration,
                active: this.admissionForm.active,
                schema: this.admissionForm.schema ?? [],
            });

            return {
                form,
                imageConfig,
                fileInput,
                selections,
                editContent: true,
            };
        },
        mounted() {
            this.admissionForm.schema.forEach((schema) => {
                const newField = {
                    id: `field_${this.selections.length + 1}`, // Unique ID
                    label: `Field ${this.selections.length + 1}`, // Default label
                    title: schema.title,
                    description: schema.description,
                    type: schema.type, // Default type
                    placeholder: "Question", // Placeholder
                    options: schema.options, // Options for dropdown/select fields
                    rules: schema.rules ?? null,
                    validators: {
                        required: schema.validators.required ?? false,
                        unique: schema.validators.unique ?? false,
                    },
                };

                this.selections.push(newField);
            });
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
                    description: null,
                    type: "text", // Default type
                    placeholder: "Question", // Placeholder
                    options: null, // Options for dropdown/select fields
                    rules: null,
                    validators: {
                        required: false,
                        unique: false,
                    },
                };

                this.selections.push(newField);

                this.form.clearErrors("schema");
            },
            removeSelection(index) {
                // Remove the field at the specified index
                this.selections.splice(index, 1);
            },
            changeSelectionType(index) {
                this.form.clearErrors(`schema.${index}.options`);

                const selection = this.selections[index];

                if (!["select", "radio", "checkbox", "file"].includes(selection.type)) {
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
                this.form.post(
                    route("admin.form.update", {
                        form: this.admissionForm.uuid,
                        isDirty: this.imageConfig.isDirty,
                        _method: "put",
                    }), {
                        onSuccess: () => {
                            toastr.success("Form successfully updated");
                            this.resetForm();
                        },
                        onError: (errors) => {
                            toastr.error("Something went wrong");
                        },
                    }
                );
            },
            resetForm() {
                // Clear form and selections after successful submission
                this.form.reset();
                this.form.clearErrors();
                this.selections = [];
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
        },
    };
</script>

<template>

    <Head title="Forms | Edit Form" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                Forms
                <span class="material-symbols-outlined text-gray-400">
                    keyboard_arrow_right
                </span>
                Edit Form
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
                                                <TextInput id="title" type="text" class="w-full"
                                                    v-model="form.title" :placeholder="'Title'" autocomplete="title"
                                                    :class="{ 'border-red-600': form.errors.title }" />
                                                <InputError :message="form.errors.title" />
                                            </div>

                                            <div>
                                                <InputLabel for="description" value="Description"
                                                    :required="false" />
                                                <TextAreaInput v-model="form.description" class="w-full"
                                                    :class="{ 'border-red-600': form.errors.description }" />

                                                <InputError :message="form.errors.description" />
                                            </div>

                                            <div>
                                                <InputLabel for="image" value="image" :required="false" />

                                                <div class="flex flex-col gap-6 mt-3">
                                                    <!-- preview section -->
                                                    <div v-if="imageConfig.preview"
                                                        class="relative h-28 w-28 md:w-56 md:h-36">
                                                        <img :src="imageConfig.preview" alt="Preview"
                                                            class="w-full h-full object-cover rounded-lg shadow-md" />

                                                        <button @click="removeImage"
                                                            class="inline-flex absolute top-0 right-0 bg-red-600 text-white p-1 rounded-full shadow-lg hover:bg-red-700">
                                                            <span class="material-symbols-outlined"> close </span>
                                                        </button>
                                                    </div>

                                                    <!-- Upload Button -->
                                                    <div>
                                                        <FileInput ref="fileInput" id="image-upload" class="hidden"
                                                            @change="handleImageOnChange" accept="image/*" />
                                                        <label for="image-upload"
                                                            class="cursor-pointer text-sm py-2 px-4 bg-gray-100 text-gray-700 rounded-md shadow hover:bg-gray-200">
                                                            Choose Image
                                                        </label>
                                                    </div>

                                                    <!-- Restore Button -->
                                                    <div v-if="this.editContent && imageConfig.isDirty">
                                                        <button @click="restoreImage"
                                                            class="py-2 px-4 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                                            Restore Original
                                                        </button>
                                                    </div>
                                                </div>

                                                <InputError class="mt-2" :message="form.errors.image" />
                                            </div>

                                            <div>
                                                <InputLabel for="code" value="Unique Code"
                                                    :required="true" />
                                                <TextInput id="code" type="text" class="w-full"
                                                    v-model="form.code" :placeholder="'Code'" autocomplete="code"
                                                    :class="{ 'border-red-600': form.errors.code }" />
                                                <InputError :message="form.errors.code" />
                                            </div>
                                            <!-- message after registration -->
                                            <div>
                                                <InputLabel for="message_after_registration"
                                                    value="Message After Registration" :required="true" />
                                                <TextInput id="message_after_registration" type="text" class="w-full"
                                                    v-model="form.message_after_registration"
                                                    :placeholder="'Message After Registration'"
                                                    :class="{
                                                        'border-red-600': form.errors.message_after_registration,
                                                    }" />
                                                <InputError :message="form.errors.message_after_registration" />
                                            </div>

                                            <!-- message when inactive -->
                                            <div>
                                                <InputLabel for="message_when_inactive" value="Message When Inactive"
                                                    :required="true" />
                                                <TextInput id="code" type="text" class="w-full"
                                                    v-model="form.message_when_inactive"
                                                    :placeholder="'Message When Inactive'"
                                                    :class="{ 'border-red-600': form.errors.message_when_inactive }" />
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
                                                        class="inline-flex items-center cursor-pointer space-x-3 text-sm">
                                                        Active (Accept Responses)
                                                        <Checkbox v-model:checked="form.active" class="sr-only peer" />
                                                        <div
                                                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-700 peer-disabled:cursor-not-allowed">
                                                        </div>
                                                    </label>
                                                </div>
                                                <InputError :message="form.errors.active" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions -->
                                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                    <div class="p-6">
                                        <div class="grid gap-5">
                                            <div class="border border-gray-400 p-6 rounded-lg shadow-sm space-y-4"
                                                v-for="(selection, row) in selections" :key="row">
                                                <div class="grid grid-cols-3 gap-4">
                                                    <div class="col-span-2">
                                                        <TextInput :id="selection.id" type="text" class="w-full"
                                                            v-model="selection.title"
                                                            :placeholder="selection.placeholder"
                                                            :class="{
                                                                'border-red-600': form.errors[`schema.${row}.title`],
                                                            }" />
                                                        <InputError :message="form.errors[`schema.${row}.title`]" />
                                                    </div>

                                                    <div>
                                                        <SelectInput @change="changeSelectionType(row)"
                                                            :id="'input_type_' + row" v-model="selection.type"
                                                            class="w-full">
                                                            <option value="text" selected>Text</option>
                                                            <option value="email">Email</option>
                                                            <option value="phonenumber">Phonenumber</option>
                                                            <option value="textarea">Textarea</option>
                                                            <option value="select">Select</option>
                                                            <option value="checkbox">Checkbox</option>
                                                            <option value="radio">Radio</option>
                                                            <option value="number">Number</option>
                                                            <option value="file">File</option>
                                                            <option value="select_course">Course Selection</option>
                                                        </SelectInput>
                                                    </div>

                                                    <div class="col-span-full">
                                                        <InputLabel for="description" value="Description"
                                                            :required="false" />
                                                        <TextAreaInput v-model="selection.description" class="w-full"
                                                            :class="{
                                                                'border-red-600': form.errors[
                                                                    `schema.${row}.description`],
                                                            }" />

                                                        <InputError
                                                            :message="form.errors[`schema.${row}.description`]" />
                                                    </div>
                                                </div>

                                                <div>
                                                    <div
                                                        v-if="
                              ['select', 'radio', 'checkbox', 'file'].includes(
                                selection.type
                              )
                            ">
                                                        <TextInput :id="selection.id" type="text" class="w-full"
                                                            v-model="selection.options"
                                                            :placeholder="(selection.type == 'file' ? 'File type' : 'Options') +
                                                            ' (comma-separated)'"
                                                            :class="{
                                                                'border-red-600': form.errors[`schema.${row}.options`],
                                                            }" />

                                                        <div class="mt-1" v-if="selection.type == 'file'">
                                                            <p class="text-sm text-gray-600">
                                                                Supported formats: jpg, jpeg, png, gif, docx, txt, pdf,
                                                                csv, xlsx and zip.
                                                            </p>
                                                        </div>

                                                        <InputError :message="form.errors[`schema.${row}.options`]" />
                                                    </div>
                                                </div>

                                                <div class="col-span-2">
                                                    <TextInput :id="selection.id" type="text" class="w-full"
                                                        v-model="selection.rules"
                                                        placeholder="Validation Rules"
                                                        :class="{
                                                            'border-red-600': form.errors[`schema.${row}.rules`],
                                                        }" />

                                                    <p class="text-sm text-gray-600">
                                                        Check out rules <a target="_blank" class="text-blue-600" href="https://laravel.com/docs/11.x/validation#available-validation-rules">here</a>
                                                    </p>
                                                    <InputError :message="form.errors[`schema.${row}.rules`]" />
                                                </div>

                                                <div class="flex justify-between items-center">
                                                    <div class="flex items-center gap-4">
                                                        <div>
                                                            <label
                                                                class="inline-flex items-center cursor-pointer space-x-3 text-sm">
                                                                Required
                                                                <Checkbox
                                                                    v-model:checked="selection.validators.required"
                                                                    class="sr-only peer" />
                                                                <div
                                                                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-700 peer-disabled:cursor-not-allowed">
                                                                </div>
                                                            </label>
                                                        </div>

                                                        <div>
                                                            <label
                                                                class="inline-flex items-center cursor-pointer space-x-3 text-sm">
                                                                Unique
                                                                <Checkbox v-model:checked="selection.validators.unique"
                                                                    class="sr-only peer" />
                                                                <div
                                                                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-700 peer-disabled:cursor-not-allowed">
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-col items-center"
                                                        :class="{
                                                            'gap-y-2.5': row !== 0 && row !== selections.length - 1,
                                                        }">
                                                        <div>
                                                            <button @click="moveField(row, 'up')" v-if="row !== 0"
                                                                type="button"
                                                                class="w-11 h-8 flex items-center justify-center border border-transparent bg-gray-100 rounded-sm shadow-sm p-1 disabled:opacity-25 disabled:cursor-not-allowed">
                                                                <span
                                                                    class="material-symbols-outlined text-2xl font-bold text-gray-800">
                                                                    keyboard_arrow_up
                                                                </span>
                                                            </button>
                                                        </div>

                                                        <div>
                                                            <button @click="moveField(row, 'down')"
                                                                v-if="row !== selections.length - 1" type="button"
                                                                class="w-11 h-8 flex items-center justify-center border border-transparent bg-gray-100 rounded-sm shadow-sm p-1 disabled:opacity-25 disabled:cursor-not-allowed">
                                                                <span
                                                                    class="material-symbols-outlined text-2xl font-bold text-gray-800">
                                                                    keyboard_arrow_down
                                                                </span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <DangerButton type="button" @click="removeSelection(row)"
                                                            class="h-8 w-9 flex items-center justify-center">
                                                            <span class="material-symbols-outlined"> delete </span>
                                                        </DangerButton>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="form.errors.schema"
                                                class="bg-red-100 rounded-md p-4 border border-red-200 text-red-700 text-md">
                                                <span class="font-bold">Oops!</span> {{ form . errors . schema }}
                                            </div>

                                            <div>
                                                <PrimaryButton @click="addSelection" type="button">
                                                    <span class="material-symbols-outlined mr-2"> add </span>
                                                    add question
                                                </PrimaryButton>
                                            </div>

                                            <div>
                                                <PrimaryButton type="submit" :disabled="form.processing"
                                                    :class="{ 'opacity-25': form.processing }">update
                                                </PrimaryButton>
                                            </div>
                                        </div>
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
