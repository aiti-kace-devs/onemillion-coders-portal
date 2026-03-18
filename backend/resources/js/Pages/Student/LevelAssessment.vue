<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import axios from "axios";

const props = defineProps({
    user: Object,
});

const assessmentUrl = computed(() => {
    const baseUrl = usePage().props.quiz_frontend_url || '';
    const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    const token = usePage().props.quiz_jwt_token;
    const path = `${base}/quiz/${props.user.userId}`;
    return token ? `${path}?token=${token}` : path;
});

const iframeRef = ref(null);
const hasStarted = ref(false);
const isComplete = ref(false);
const showViolation = ref(false);
let violationCooldown = false;

onMounted(() => {
    window.addEventListener("message", handleMessage);
    document.addEventListener("fullscreenchange", handleParentFullscreen);
    document.addEventListener("visibilitychange", handleVisibility);
});

onUnmounted(() => {
    window.removeEventListener("message", handleMessage);
    document.removeEventListener("fullscreenchange", handleParentFullscreen);
    document.removeEventListener("visibilitychange", handleVisibility);
});

function recordViolation() {
    if (violationCooldown) return;
    violationCooldown = true;
    setTimeout(() => { violationCooldown = false; }, 1000);

    showViolation.value = true;

    // API call to record the violation in the backend
    const token = usePage().props.quiz_jwt_token;
    if (token) {
        axios.post('/api/tiered-assessment/record-violation', {}, {
            headers: { Authorization: `Bearer ${token}` }
        }).catch(err => console.error("Error recording violation:", err));
    }
}

function handleParentFullscreen() {
    if (document.fullscreenElement === iframeRef.value) {
        hasStarted.value = true;
    } else if (!document.fullscreenElement && hasStarted.value && !isComplete.value) {
        // Exited fullscreen!
        recordViolation();
    }
}

function handleVisibility() {
    if (document.hidden && hasStarted.value && !isComplete.value) {
        // Tab switch!
        recordViolation();
    }
}

function handleMessage(event) {
    if (event.data?.type === "REQUEST_FULLSCREEN" && iframeRef.value) {
        const el = iframeRef.value;
        if (el.requestFullscreen) {
            el.requestFullscreen().catch(err => console.warn(err));
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        } else if (el.msRequestFullscreen) {
            el.msRequestFullscreen();
        }
    } else if (event.data?.type === "EXIT_FULLSCREEN") {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => console.warn(err));
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    } else if (event.data?.type === "ASSESSMENT_COMPLETE") {
        isComplete.value = true;
        // go to /student/choose-course
        router.visit('/student/change-course');
    }
}

function handleIframeLoad() {
    if (!iframeRef.value) return;
    try {
        const href = iframeRef.value.contentWindow.location.href;
        const pathname = iframeRef.value.contentWindow.location.pathname;

        if (pathname && (pathname.includes('/student/change-course') || pathname.includes('/student/choose-course'))) {
            isComplete.value = true; // Stop tracking violations
            router.visit(pathname);
        }
    } catch (e) {
        // Ignore CORS errors
    }
}

function resumeAssessment() {
    showViolation.value = false;
    if (iframeRef.value && iframeRef.value.requestFullscreen) {
        iframeRef.value.requestFullscreen().catch(err => console.warn(err));
    }
}
</script>

<template>

    <Head title="Level Assessment" />
    <AuthenticatedLayout :fullHeight="true">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Level Assessment</h2>
        </template>

        <div class="h-[calc(100vh-64px)] overflow-hidden relative">
            <iframe ref="iframeRef" :src="assessmentUrl" class="w-full h-full border-0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                allowfullscreen @load="handleIframeLoad"></iframe>

            <!-- Vue Violation Overlay -->
            <div v-if="showViolation"
                class="absolute inset-0 bg-black/95 z-[9999] flex flex-col items-center justify-center text-center px-4">
                <div class="w-20 h-20 rounded-full bg-red-500/20 flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-5xl text-red-500">warning</span>
                </div>
                <h2 class="text-3xl font-black text-white mb-4">Violation Detected</h2>
                <p class="text-white/70 text-lg mb-8 max-w-md">
                    You exited fullscreen mode or switched tabs during an active assessment. This has been recorded in
                    our
                    system.
                </p>
                <button @click="resumeAssessment"
                    class="px-8 py-4 bg-red-600 hover:bg-red-700 transition font-bold text-white rounded-lg shadow-lg">
                    I Understand, Return to Assessment
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
