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
    return token ? `${path}?token=${token}&embed=true` : `${path}?embed=true`;
});

const iframeRef = ref(null);
const hasStarted = ref(false);
const isComplete = ref(false);

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



function handleParentFullscreen() {
    if (document.fullscreenElement === iframeRef.value) {
        hasStarted.value = true;
    } else if (!document.fullscreenElement && hasStarted.value && !isComplete.value) {
        // Exited fullscreen! Tell React to trigger a violation
        if (iframeRef.value?.contentWindow) {
            iframeRef.value.contentWindow.postMessage({ type: 'VIOLATION_TRIGGERED' }, '*');
        }
    }
}

function handleVisibility() {
    if (document.hidden && hasStarted.value && !isComplete.value) {
        // Tab switch! Tell React to trigger a violation
        if (iframeRef.value?.contentWindow) {
            iframeRef.value.contentWindow.postMessage({ type: 'VIOLATION_TRIGGERED' }, '*');
        }
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
    } else if (event.data?.type === "LARAVEL_IFRAME_DETECTED") {
        isComplete.value = true;
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
</script>

<template>

    <Head title="Level Assessment" />
    <AuthenticatedLayout :fullHeight="true">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Level Assessment</h2>
        </template>

        <div class="h-[calc(100vh-70px)] overflow-hidden relative">
            <iframe ref="iframeRef" :src="assessmentUrl" class="w-full h-full border-0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                allowfullscreen @load="handleIframeLoad"></iframe>

        </div>
    </AuthenticatedLayout>
</template>
