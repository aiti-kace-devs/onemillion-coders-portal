<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectInput from "@/Components/SelectInput.vue";

export default {
  components: {
    AuthenticatedLayout,
    Head,
    PrimaryButton,
    TextInput,
    InputError,
    InputLabel,
    SelectInput,
  },
  props: {
    errors: Object,
    session: Object,
    courses: Object,
  },
  data() {
    const form = useForm({
      course_id: this.session.course_id,
      limit: this.session.limit,
      course_time: this.session.course_time,
      session: this.session.session,
      link: this.session.link,
    });

    return {
      form,
      editContent: true,
    };
  },
  methods: {
    submit() {
      this.form.put(route("admin.session.update", { session: this.session.uuid }), {
        onSuccess: () => {
          toastr.success("Session successfully updated");
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
  <Head title="Sessions | Edit Session" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center">
        Sessions
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        Edit Session
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-none sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submit">
              <div class="grid lg:grid-cols-12 gap-5">
                <div class="lg:col-span-6">
                  <InputLabel for="course_id" value="course" :required="true" />
                  <SelectInput
                    id="'course_id"
                    v-model="form.course_id"
                    class="w-full"
                    :class="{ 'border-red-600': form.errors.course_id }"
                  >
                    <option :value="''" disabled>-- Select Course --</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                      {{ course.course_name }}
                    </option>
                  </SelectInput>
                  <InputError :message="form.errors.course_id" />
                </div>

                <div class="lg:col-span-6">
                  <InputLabel for="limit" value="limit" :required="true" />
                  <TextInput
                    id="limit"
                    type="number"
                    class="w-full"
                    v-model="form.limit"
                    :placeholder="'Limit'"
                    autocomplete="limit"
                    :class="{ 'border-red-600': form.errors.limit }"
                  />
                  <InputError :message="form.errors.limit" />
                </div>

                <div class="lg:col-span-6">
                  <InputLabel for="session" value="Session" :required="true" />
                  <TextInput
                    id="session"
                    type="text"
                    class="w-full"
                    v-model="form.session"
                    :placeholder="'Session'"
                    autocomplete="false"
                    :class="{ 'border-red-600': form.errors.session }"
                  />
                  <InputError :message="form.errors.session" />
                </div>

                <div class="lg:col-span-6">
                  <InputLabel for="course_time" value="Duration" :required="true" />
                  <TextInput
                    id="course_time"
                    type="text"
                    class="w-full"
                    v-model="form.course_time"
                    :placeholder="'Duration'"
                    autocomplete="false"
                    :class="{ 'border-red-600': form.errors.course_time }"
                  />
                  <InputError :message="form.errors.course_time" />
                </div>

                <div class="lg:col-span-6">
                  <InputLabel for="link" value="WhatsApp Link" :required="false" />
                  <TextInput
                    id="link"
                    type="text"
                    class="w-full"
                    v-model="form.link"
                    :placeholder="'WhatsApp Link'"
                    autocomplete="false"
                    :class="{ 'border-red-600': form.errors.link }"
                  />
                  <InputError :message="form.errors.link" />
                </div>

                <div class="col-span-full">
                  <PrimaryButton
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-25': form.processing }"
                    >update
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
