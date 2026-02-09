<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from "vue";
import PrimaryButton from "./PrimaryButton.vue";


const props = defineProps({
    examList: Array,
});

const EXAM_DEADLINE_AFTER_REGISTRATION = usePage().props.config?.['EXAM_DEADLINE_AFTER_REGISTRATION'] || 14;
const SHOW_RESULTS_TO_STUDENTS = usePage().props.config?.['SHOW_RESULTS_TO_STUDENTS'] || false;
const user = usePage().props.auth?.user || {};


const getExamStatus = (exam) => {
    const { hoursLeft } = getExamDeadline(exam.exam_date, user.created_at);
    if (exam.submitted) return "completed";
    if (hoursLeft < 0) return "overdue";
    return "pending";
}

const getExamDeadline = (examDate, registeredAt) => {
    const now = new Date();
    const examDeadline = new Date(examDate);
    const registered = registeredAt ? new Date(registeredAt) : now;
    const studentDeadline = new Date(registered);
    studentDeadline.setDate(studentDeadline.getDate() + EXAM_DEADLINE_AFTER_REGISTRATION);
    let deadline = examDeadline;
    let hoursLeft = Math.round((deadline - now) / (1000 * 60 * 60));
    const studentHoursLeft = Math.round((studentDeadline - now) / (1000 * 60 * 60));

    if (studentHoursLeft < hoursLeft) {
        deadline = studentDeadline;
        hoursLeft = studentHoursLeft;
    }
    return { deadline, hoursLeft };
}

</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="(exam, key) in examList" :key="exam.exam_id"
            class="relative group bg-white rounded-xl shadow p-6 flex flex-col h-full">
            <Link :href="route('student.join-exam', exam.exam_id)" class="block flex-1">
                <!-- Status badge -->
                <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold" :class="{
                    'bg-green-100 text-green-700': getExamStatus(exam) === 'completed',
                    'bg-yellow-100 text-yellow-700': getExamStatus(exam) === 'pending',
                    'bg-red-100 text-red-700': getExamStatus(exam) === 'overdue',
                }">
                    {{
                        getExamStatus(exam).charAt(0).toUpperCase() +
                        getExamStatus(exam).slice(1)
                    }}
                </span>

                <!-- Icon and Title -->
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100">
                        <span class="material-symbols-outlined text-gray-600">quiz</span>
                    </span>
                    <div class="flex-1 text-left">
                        <h3 class="text-lg font-bold text-gray-800">{{ exam.title }}</h3>
                        <p class="text-xs text-gray-500">
                            Category: {{ exam.category_name }}
                        </p>
                    </div>
                </div>

                <!-- Exam Details -->
                <div class="mt-2 space-y-1 text-left">
                    <p class="text-sm">
                        <strong>Duration:</strong> {{ exam.exam_duration }} mins
                    </p>
                    <p class="text-sm"><strong>Total Questions:</strong> 30</p>

                    <div v-if="getExamStatus(exam) === 'completed'">
                        <p class="text-sm">
                            <strong>Submitted on:</strong>
                            {{ new Date(exam.submitted).toDateString() }},
                            {{ new Date(exam.submitted).toLocaleTimeString() }}
                        </p>
                    </div>
                    <div v-else>
                        <p class="text-sm">
                            <strong>{{
                                getExamDeadline(exam.exam_date, user.created_at).hoursLeft < 0 ? "Elapsed Since"
                                    : "Deadline" }}: </strong>
                                    <span>{{
                                        getExamDeadline(
                                            exam.exam_date,
                                            user.created_at
                                        ).deadline.toDateString()
                                    }},
                                        {{
                                            getExamDeadline(
                                                exam.exam_date,
                                                user.created_at
                                            ).deadline.toLocaleTimeString()
                                        }}</span>
                                    <span v-if="
                                        getExamDeadline(exam.exam_date, user.created_at).hoursLeft > 0
                                    " class="text-gray-500">
                                        in
                                        {{ getExamDeadline(exam.exam_date,
                                            user.created_at).hoursLeft }}
                                        hour(s)</span>
                        </p>
                    </div>
                </div>
            </Link>

            <!-- View Results Button -->
            <div v-if="getExamStatus(exam) === 'completed' && SHOW_RESULTS_TO_STUDENTS"
                class="mt-4 pt-3 border-t border-gray-100">

                <PrimaryButton>
                    <Link :href="`/view_result/${exam.exam_id}`">
                        View Results
                    </Link>
                </PrimaryButton>

            </div>
        </div>
    </div>
</template>
