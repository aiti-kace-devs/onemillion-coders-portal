<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    verification_status: {
        type: Object,
        required: true,
    },
    verification_embed_url: {
        type: String,
        default: null,
    },
    verification_embed_available: {
        type: Boolean,
        default: false,
    },
});

const status = ref(props.verification_status);
const iframeUnavailable = ref(!props.verification_embed_available);
const refreshCount = ref(0);
const isRefreshing = ref(false);
const fallbackMessage = ref(
    props.verification_embed_available
        ? ""
        : "Verification UI is currently unavailable. Please try again later.",
);
const canvasRef = ref(null);
const iframeHeight = ref(760);
const autoRefreshTimer = ref(null);
const submitSyncTimer = ref(null);
const MAX_AUTO_REFRESH = 3;
const IFRAME_HEIGHT_DESKTOP = 760;
const IFRAME_HEIGHT_MOBILE = 680;

const attempts = computed(() => status.value?.attempts ?? { used: 0, max: 5, remaining: 5 });
const profile = computed(() => status.value?.profile ?? {});
const imageInfo = computed(() => status.value?.image ?? { available: false, url: "" });
const latestAttempt = computed(() => status.value?.latest_attempt ?? null);
const latestAttemptMessage = computed(() => {
    if (!latestAttempt.value) return "";
    if (latestAttempt.value.user_message) return latestAttempt.value.user_message;
    return "Verification could not be completed right now. Please try again shortly.";
});
const latestAttemptStatusLabel = computed(() => {
    if (!latestAttempt.value) return "No attempt yet";
    if (latestAttempt.value.verified) return "Verified";
    if (latestAttempt.value.success) return "Processed";
    return "Failed";
});
const formattedLatestAttemptTime = computed(() => {
    const ts = latestAttempt.value?.response_timestamp;
    if (!ts) return "Not available";
    const parsed = new Date(ts);
    if (Number.isNaN(parsed.getTime())) return "Not available";
    return parsed.toLocaleString();
});
const isVerified = computed(() => !!status.value?.verified);
const isBlocked = computed(() => !!status.value?.blocked);
const blockMessage = computed(
    () =>
        status.value?.block?.message ||
        "Your verification is blocked. Please contact support or an administrator.",
);
const blockReasonLabel = computed(
    () => status.value?.block?.reason_label || "Verification access restricted",
);
const embedOrigin = computed(() => {
    if (!props.verification_embed_url) return null;
    try {
        return new URL(props.verification_embed_url).origin;
    } catch (error) {
        return null;
    }
});

function updateUniformIframeHeight() {
    if (typeof window === "undefined") return;
    iframeHeight.value = window.innerWidth < 768 ? IFRAME_HEIGHT_MOBILE : IFRAME_HEIGHT_DESKTOP;
}

async function refreshStatus() {
    isRefreshing.value = true;
    try {
        const response = await fetch(route("student.verification.status"), {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        });
        if (!response.ok) {
            throw new Error("Unable to refresh verification status.");
        }
        const payload = await response.json();
        if (payload?.success && payload?.data) {
            status.value = payload.data;
        }
    } catch (error) {
        fallbackMessage.value = "Unable to refresh verification status right now. Please try again later.";
    } finally {
        isRefreshing.value = false;
    }
}

function handleIframeLoad() {
    fallbackMessage.value = "";
}

function handleIframeError() {
    iframeUnavailable.value = true;
    fallbackMessage.value = "Verification UI is currently unavailable. Please try again later.";
}

async function handleIframePostMessage(event) {
    if (embedOrigin.value && event.origin !== embedOrigin.value) {
        return;
    }

    const payload = event.data;
    if (!payload || payload.source !== "omcp-verification") {
        return;
    }

    if (payload.type === "verification_submitted") {
        fallbackMessage.value = "Verification submitted. Refreshing your status...";
        await refreshStatus();
        startSubmitSync();
        return;
    }

    if (payload.type === "verification_failed") {
        fallbackMessage.value = "Verification failed. Please correct the issue and try again.";
        await refreshStatus();
    }
}

function startSubmitSync() {
    if (submitSyncTimer.value) {
        clearInterval(submitSyncTimer.value);
    }

    let attempts = 0;
    const MAX_SYNC_ATTEMPTS = 5;
    submitSyncTimer.value = setInterval(async () => {
        attempts += 1;
        await refreshStatus();

        if (isVerified.value || attempts >= MAX_SYNC_ATTEMPTS) {
            clearInterval(submitSyncTimer.value);
            submitSyncTimer.value = null;
        }
    }, 3000);
}

function startAutoRefresh() {
    if (autoRefreshTimer.value) {
        clearInterval(autoRefreshTimer.value);
    }

    autoRefreshTimer.value = setInterval(async () => {
        if (isVerified.value || refreshCount.value >= MAX_AUTO_REFRESH) {
            clearInterval(autoRefreshTimer.value);
            autoRefreshTimer.value = null;
            return;
        }
        refreshCount.value += 1;
        await refreshStatus();
    }, 8000);
}

onMounted(() => {
    updateUniformIframeHeight();
    window.addEventListener("resize", updateUniformIframeHeight);
    drawVerifiedImageOnCanvas();
    window.addEventListener("message", handleIframePostMessage);
    refreshStatus();
    if (!isVerified.value) {
        startAutoRefresh();
    }
});

watch(
    () => status.value,
    () => {
        drawVerifiedImageOnCanvas();
    },
    { deep: true },
);

onUnmounted(() => {
    window.removeEventListener("resize", updateUniformIframeHeight);
    window.removeEventListener("message", handleIframePostMessage);
    if (autoRefreshTimer.value) {
        clearInterval(autoRefreshTimer.value);
    }
    if (submitSyncTimer.value) {
        clearInterval(submitSyncTimer.value);
    }
});
</script>

<template>

    <Head title="Verification" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verification</h2>
        </template>

        <div class="py-6 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Ghana Card Verification Status</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Attempts used: <span class="font-semibold">{{ attempts.used }}</span> /
                            <span class="font-semibold">{{ attempts.max }}</span>
                            <span class="ml-2 text-gray-500">(remaining: {{ attempts.remaining }})</span>
                        </p>
                    </div>
                    <button v-if="!isVerified"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-60"
                        :disabled="isRefreshing" @click="refreshStatus">
                        {{ isRefreshing ? "Refreshing..." : "Refresh Status" }}
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <details class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <summary class="cursor-pointer font-semibold text-gray-800">
                            Last Attempt Details
                        </summary>
                        <div class="mt-3 space-y-2 text-sm text-gray-700">
                            <p>
                                <span class="font-semibold">Status:</span>
                                {{ latestAttemptStatusLabel }}
                            </p>
                            <p>
                                <span class="font-semibold">When:</span>
                                {{ formattedLatestAttemptTime }}
                            </p>
                            <p>
                                <span class="font-semibold">Message:</span>
                                {{ latestAttemptMessage || "No attempt yet." }}
                            </p>
                        </div>
                    </details>

                    <details class="rounded-lg border border-gray-200 bg-gray-50 p-4" open>
                        <summary class="cursor-pointer font-semibold text-gray-800">
                            Basic Info
                        </summary>
                        <div class="mt-3 space-y-2 text-sm text-gray-700">
                            <p><span class="font-semibold">Blocked:</span> {{ isBlocked ? "Yes" : "No" }}</p>
                            <p><span class="font-semibold">Name:</span> {{ profile.name || "N/A" }}</p>
                            <p><span class="font-semibold">Previous Name:</span> {{ profile.previous_name || "N/A" }}
                            </p>
                            <p><span class="font-semibold">Date of Birth:</span> {{ profile.date_of_birth || "N/A" }}
                            </p>
                        </div>
                    </details>
                </div>
            </div>

            <div v-if="isVerified" class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
                <div class="flex items-center gap-2 text-green-700 mb-4">
                    <span class="material-symbols-outlined">verified</span>
                    <span class="font-semibold">Verification successful</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="w-full h-30">
                        <img :src="status.image.url" alt="verified_image" style="height:320px;"
                            class="h-full rounded-lg border border-gray-200 bg-gray-50" />
                    </div>
                    <div class="space-y-3">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Full Name:</span> {{ profile.name || "N/A" }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Date of Birth:</span> {{ profile.date_of_birth || "N/A" }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">First Name:</span> {{ profile.first_name || "N/A" }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Middle Name:</span> {{ profile.middle_name || "N/A" }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Last Name:</span> {{ profile.last_name || "N/A" }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                <div v-if="isBlocked" class="rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                    <div class="font-semibold">Blocked: Yes</div>
                    <div class="mt-1">Reason: {{ blockReasonLabel }}</div>
                    <div class="mt-1">{{ blockMessage }}</div>
                    <div class="mt-2 text-xs text-red-600">
                        Verification interface is unavailable while your account is blocked.
                    </div>
                </div>

                <div v-if="fallbackMessage"
                    class="rounded-md border border-gray-200 bg-gray-50 text-gray-700 px-4 py-3 text-sm">
                    {{ fallbackMessage }}
                </div>

                <div v-if="!isBlocked && !iframeUnavailable && verification_embed_url" class="space-y-2">
                    <p class="text-sm text-gray-600">
                        Use the verification interface below. On success, this page will auto-refresh.
                    </p>
                    <iframe ref="verificationIframeRef" :src="verification_embed_url"
                        class="w-full rounded-lg border border-gray-200" :style="{ height: `${iframeHeight}px` }"
                        loading="lazy" allow="camera; microphone" referrerpolicy="strict-origin-when-cross-origin"
                        @load="handleIframeLoad" @error="handleIframeError" />
                </div>

                <div v-else-if="!isBlocked" class="space-y-2">
                    <p class="text-sm text-gray-600">
                        Verification UI is not available yet on this branch. Please retry later.
                    </p>
                    <button
                        class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-800 text-white text-sm font-semibold hover:bg-black"
                        @click="refreshStatus">
                        Try Again Later
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
