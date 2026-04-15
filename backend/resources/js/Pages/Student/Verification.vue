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
const autoRefreshTimer = ref(null);
const MAX_AUTO_REFRESH = 3;

const attempts = computed(() => status.value?.attempts ?? { used: 0, max: 5, remaining: 5 });
const profile = computed(() => status.value?.profile ?? {});
const imageInfo = computed(() => status.value?.image ?? { available: false, url: "" });
const latestAttempt = computed(() => status.value?.latest_attempt ?? null);
const isVerified = computed(() => !!status.value?.verified);
const isBlocked = computed(() => !!status.value?.blocked);

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

function drawVerifiedImageOnCanvas() {
    const imageUrl = imageInfo.value?.url;
    const canvas = canvasRef.value;
    if (!imageUrl || !canvas) return;

    const context = canvas.getContext("2d");
    const image = new Image();
    image.crossOrigin = "anonymous";
    image.onload = () => {
        const width = canvas.width;
        const height = canvas.height;
        context.clearRect(0, 0, width, height);
        context.fillStyle = "#f8fafc";
        context.fillRect(0, 0, width, height);

        const imageRatio = image.width / image.height;
        const canvasRatio = width / height;
        let drawWidth = width;
        let drawHeight = height;
        let offsetX = 0;
        let offsetY = 0;

        if (imageRatio > canvasRatio) {
            drawHeight = height;
            drawWidth = image.width * (height / image.height);
            offsetX = (width - drawWidth) / 2;
        } else {
            drawWidth = width;
            drawHeight = image.height * (width / image.width);
            offsetY = (height - drawHeight) / 2;
        }

        context.drawImage(image, offsetX, offsetY, drawWidth, drawHeight);
    };
    image.src = imageUrl;
}

function handleIframeLoad() {
    fallbackMessage.value = "";
}

function handleIframeError() {
    iframeUnavailable.value = true;
    fallbackMessage.value = "Verification UI is currently unavailable. Please try again later.";
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
    drawVerifiedImageOnCanvas();
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
    if (autoRefreshTimer.value) {
        clearInterval(autoRefreshTimer.value);
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
                    <button
                        class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-60"
                        :disabled="isRefreshing"
                        @click="refreshStatus"
                    >
                        {{ isRefreshing ? "Refreshing..." : "Refresh Status" }}
                    </button>
                </div>
            </div>

            <div v-if="isVerified" class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
                <div class="flex items-center gap-2 text-green-700 mb-4">
                    <span class="material-symbols-outlined">verified</span>
                    <span class="font-semibold">Verification successful</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <canvas
                            ref="canvasRef"
                            width="480"
                            height="320"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50"
                        />
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
                <div
                    v-if="isBlocked"
                    class="rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"
                >
                    Your verification is blocked. Please contact support or an administrator.
                </div>

                <div
                    v-if="latestAttempt?.status_message"
                    class="rounded-md border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm"
                >
                    Latest update: {{ latestAttempt.status_message }}
                </div>

                <div
                    v-if="fallbackMessage"
                    class="rounded-md border border-gray-200 bg-gray-50 text-gray-700 px-4 py-3 text-sm"
                >
                    {{ fallbackMessage }}
                </div>

                <div v-if="!iframeUnavailable && verification_embed_url" class="space-y-2">
                    <p class="text-sm text-gray-600">
                        Use the verification interface below. On success, this page will auto-refresh.
                    </p>
                    <iframe
                        :src="verification_embed_url"
                        class="w-full min-h-[480px] rounded-lg border border-gray-200"
                        loading="lazy"
                        @load="handleIframeLoad"
                        @error="handleIframeError"
                    />
                </div>

                <div v-else class="space-y-2">
                    <p class="text-sm text-gray-600">
                        Verification UI is not available yet on this branch. Please retry later.
                    </p>
                    <button
                        class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-800 text-white text-sm font-semibold hover:bg-black"
                        @click="refreshStatus"
                    >
                        Try Again Later
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
