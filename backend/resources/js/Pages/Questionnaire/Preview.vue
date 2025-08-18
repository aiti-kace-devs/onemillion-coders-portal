<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FormView from "./FormView.vue";
import { Head } from "@inertiajs/vue3";

export default {
    components: {
        AuthenticatedLayout,
        FormView,
        Head,
    },
    props: {
        errors: Object,
        questionnaire: Object,
        courses: Array,
        branches: Array,
        withLayout: Boolean,
        centres: Array,

    },
};
</script>

<template>

    <Head title="Questionnaires | Preview Questionnaire" v-if="withLayout" />
    <AuthenticatedLayout v-if="withLayout">
        <template #header>
            <div class="flex items-center">
                Questionnaires
                <span class="material-symbols-outlined text-gray-400">
                    keyboard_arrow_right
                </span>
                Preview Questionnaire
            </div>
        </template>

        <FormView :questionnaire="questionnaire" :courses="courses" :branches="branches" :centres="centres"
            :admin="true" :errors="errors" />
    </AuthenticatedLayout>

    <div v-if="!withLayout">
        <FormView :questionnaire="questionnaire" :admin="false" :centres="centres" :courses="courses"
            :branches="branches" :errors="errors" />
    </div>
</template>
