<script setup>
import { computed, onMounted } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import ExamCard from "@/Components/ExamCard.vue";

const props = defineProps({
    exams: {
        type: Object,
        required: true,
    },
    showResultsToStudents: Boolean,
});

const page = usePage();
onMounted(() => {
    const flash = page.props.flash;
    if (flash) {
        if (flash.key === "success") {
            toastr.success(flash.message);
        } else if (flash.key === "error") {
            toastr.error(flash.message);
        } else if (flash.key === "info") {
            toastr.info(flash.message);
        } else if (flash.key === "warning") {
            toastr.warning(flash.message);
        } else if (flash.key === "message") {
            toastr.success(flash.message);
        }
    }
});



function canTakeExam(examDate, registeredAt) {
    const now = new Date();
    const examDeadline = new Date(examDate + "T23:59:00");
    const registered = registeredAt ? new Date(registeredAt) : now;
    const studentDeadline = new Date(registered);
    studentDeadline.setDate(studentDeadline.getDate() + EXAM_DEADLINE_AFTER_REGISTRATION);
    const deadline = studentDeadline < examDeadline ? studentDeadline : examDeadline;
    return now <= deadline;
}


const examList = computed(() => props.exams || []);
const totalExams = computed(() => examList.value.length);
const completedExams = computed(() => examList.value.filter((e) => e.submitted).length);
const pendingExams = computed(
    () =>
        examList.value.filter((e) => !e.submitted && getExamStatus(e) === "pending").length
);
const overdueExams = computed(
    () => examList.value.filter((e) => getExamStatus(e) === "overdue").length
);
const overallProgress = computed(() =>
    totalExams.value === 0 ? 0 : Math.round((completedExams.value / totalExams.value) * 100)
);
</script>

<template>

    <Head title="Exam" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Exam</h2>
        </template>
        <div class="pt-3">
            <ExamCard :examList="examList" :showResultsToStudents="showResultsToStudents" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped></style>
