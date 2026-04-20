<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
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

const startAttempts = ref(props.verification_status?.attempts?.used ?? 0);
const currentAttempts = ref(props.verification_status?.attempts?.used ?? 0);
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
const verificationIframeRef = ref(null);
const iframeHeight = ref(760);
const autoRefreshTimer = ref(null);
const submitSyncTimer = ref(null);
const MAX_AUTO_REFRESH = 3;
const IFRAME_HEIGHT_DESKTOP = 760;
const IFRAME_HEIGHT_MOBILE = 680;
const localEmbedUrl = ref(props.verification_embed_url);

const attempts = computed(() => status.value?.attempts ?? { used: 0, max: 5, remaining: 5 });
const profile = computed(() => status.value?.profile ?? {});
const imageInfo = computed(() => status.value?.image ?? { available: false, url: "" });
const latestAttempt = computed(() => status.value?.latest_attempt ?? null);
const attemptsIncreased = computed(() => currentAttempts.value > startAttempts.value);

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

async function refreshStatus(sync = false, final = false) {
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
        const attemptTimestamp = latestAttempt.value?.response_timestamp;
        status.value = payload.data;
        latestAttempt.value = payload.data.latest_attempt;
        currentAttempts.value = status.value?.attempts?.used ?? 0;
        // determine if should sync
        // if the difference between the current time and attempt timestamp is less than 30 seconds, and sync is true, do not refresh
        if (sync && !final) {
            if (Date.now() - attemptTimestamp < 60000) {
                return;
            } else {
                for (let i = 0; i < MAX_SYNC_ATTEMPTS; i++) {
                    let isFinal = i === MAX_SYNC_ATTEMPTS - 1;
                    // wait 3 seconds
                    await new Promise(resolve => setTimeout(resolve, 3000));
                    await refreshStatus(false, isFinal);
                }
            }
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
        await refreshStatus(true);
        return;
    }

    if (payload.type === "verification_failed") {
        fallbackMessage.value = "Verification failed. Please correct the issue and try again.";
        await refreshStatus(true);
        return;
    }
}

const reloadIframe = async () => {
    await nextTick();

    if (verificationIframeRef.value) {
        // Use the localEmbedUrl.value as the source
        const url = new URL(localEmbedUrl.value);
        url.searchParams.set('t', Date.now().toString());
        // Update the local reactive variable
        localEmbedUrl.value = url.toString();
    } else {
        console.error("Verification iframe ref is still null.");
    }
};

onMounted(() => {
    updateUniformIframeHeight();
    window.addEventListener("resize", updateUniformIframeHeight);
    window.addEventListener("message", handleIframePostMessage);
    refreshStatus();
    currentAttempts.value = startAttempts.value;
});

watch(
    () => status.value,
    () => {
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
            <div class="flex items-center gap-2">
                <h2 class="font-black text-2xl text-gray-900 tracking-tight">
                    Identity Verification
                </h2>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
                <!-- Subtle side accent -->
                <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-400"></div>

                <div class="p-6 md:p-8 ml-1.5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                                <span class="material-symbols-outlined text-2xl">fingerprint</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Ghana Card Verification</h3>
                                <div class="flex flex-wrap items-center gap-y-2 gap-x-4 mt-1.5">
                                    <p class="text-gray-500 text-sm flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-sm text-gray-400">history</span>
                                        Failed attempts: <span class="font-bold text-gray-800">{{ attempts.used }} / {{
                                            attempts.max }}</span>
                                    </p>
                                    <span
                                        class="text-[10px] font-bold text-amber-700 uppercase tracking-widest px-2.5 py-1 rounded-lg bg-amber-100/50 border border-amber-200/50">
                                        {{ attempts.remaining }} retries remaining
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- use column on mobile -->
                        <div class="flex items-center gap-3 flex-col md:flex-row ">
                            <button v-if="!isVerified"
                                class="inline-flex items-center gap-2 justify-center px-5 py-2.5 rounded-xl bg-green-600 text-white font-semibold transition-all duration-200 hover:bg-green-700 hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed focus:ring-2 focus:ring-green-500 focus:ring-offset-1 border border-green-700/10"
                                :disabled="isRefreshing" @click="refreshStatus">
                                <span class="material-symbols-outlined text-[20px]"
                                    :class="{ 'animate-spin': isRefreshing }">refresh</span>
                                {{ isRefreshing ? "Refreshing..." : "Refresh Status" }}
                            </button>
                            <button v-if="attemptsIncreased && !isVerified"
                                class="inline-flex items-center gap-2 justify-center px-5 py-2.5 rounded-xl bg-orange-600 text-white font-semibold transition-all duration-200 hover:bg-orange-700 hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed focus:ring-2 focus:ring-orange-500 focus:ring-offset-1 border border-orange-700/10"
                                :disabled="isRefreshing" @click="reloadIframe">
                                <span class="material-symbols-outlined text-[20px]">refresh</span>
                                Retake Verification
                            </button>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Last Attempt Details Card -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 w-full">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-amber-500">info</span>
                                <h4 class="font-semibold text-gray-800">Last Attempt Details</h4>
                            </div>
                            <ul class="space-y-3">
                                <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Status</span>
                                    <span class="font-medium text-gray-900"
                                        :class="{ 'text-green-600': latestAttemptStatusLabel === 'Verified', 'text-amber-600': latestAttemptStatusLabel === 'Processed', 'text-red-600': latestAttemptStatusLabel === 'Failed' }">{{
                                            latestAttemptStatusLabel }}</span>
                                </li>
                                <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">When</span>
                                    <span class="font-medium text-gray-800">{{ formattedLatestAttemptTime }}</span>
                                </li>
                                <li class="flex flex-col gap-1 text-sm pt-1">
                                    <span class="text-gray-500">Message</span>
                                    <span class="font-medium text-gray-800">{{ latestAttemptMessage || "No attempt yet."
                                    }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Basic Info Card -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 w-full">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-blue-500">person</span>
                                <h4 class="font-semibold text-gray-800">Basic Info</h4>
                            </div>
                            <ul class="space-y-3">
                                <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Blocked</span>
                                    <span class="font-medium px-2 py-0.5 rounded text-xs"
                                        :class="isBlocked ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">{{
                                            isBlocked ? "Yes" : "No" }}</span>
                                </li>
                                <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Name</span>
                                    <span class="font-medium text-gray-800 truncate pl-4">{{ profile.name || "N/A"
                                    }}</span>
                                </li>
                                <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Previous Name</span>
                                    <span class="font-medium text-gray-800 truncate pl-4">{{ profile.previous_name ||
                                        "N/A"
                                    }}</span>
                                </li>
                                <li class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500">Date of Birth</span>
                                    <span class="font-medium text-gray-800">{{ profile.date_of_birth || "N/A" }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verified State Section -->
            <div v-if="isVerified" class="bg-white rounded-2xl shadow-sm border border-green-200 overflow-hidden">
                <div class="bg-green-50/50 p-6 border-b border-green-100 flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0 shadow-sm border border-green-200">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-green-800 text-lg">Verification Successful</h3>
                        <p class="text-green-600/80 text-sm">Your identity has been verified successfully.</p>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    <div class="flex flex-col lg:flex-row gap-8 items-start">
                        <!-- Profile Image -->
                        <div class="w-full lg:w-1/3 shrink-0">
                            <div
                                class="rounded-xl overflow-hidden shadow-md border border-gray-200 bg-gray-50 max-w-xs mx-auto lg:mx-0 relative w-full aspect-[3/4]">
                                <img :src="status.image.url" alt="verified_image" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 ring-1 ring-inset ring-black/10 rounded-xl"></div>
                            </div>
                        </div>

                        <!-- Profile Details -->
                        <div class="w-full flex-1">
                            <h4
                                class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                                Personal Identity Data</h4>

                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <span class="block text-xs font-medium text-gray-500 mb-1">Full Name</span>
                                    <span class="block text-base font-semibold text-gray-900">{{ profile.name || "N/A"
                                    }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="block text-xs font-medium text-gray-500 mb-1">First Name</span>
                                        <span class="block text-sm font-semibold text-gray-800">{{ profile.first_name ||
                                            "N/A"
                                        }}</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="block text-xs font-medium text-gray-500 mb-1">Last Name</span>
                                        <span class="block text-sm font-semibold text-gray-800">{{ profile.last_name ||
                                            "N/A"
                                        }}</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="block text-xs font-medium text-gray-500 mb-1">Middle Name</span>
                                        <span class="block text-sm font-semibold text-gray-800">{{ profile.middle_name
                                            || "N/A"
                                        }}</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <span class="block text-xs font-medium text-gray-500 mb-1">Date of Birth</span>
                                        <span class="block text-sm font-semibold text-gray-800">{{ profile.date_of_birth
                                            ||
                                            "N/A" }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unverified State Section -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-0 md:p-6">
                <div v-if="isBlocked" class="rounded-xl border border-red-200 bg-red-50 p-2 mb-6">
                    <div class="flex items-start gap-3 text-red-800">
                        <span class="material-symbols-outlined mt-0.5 text-red-600">block</span>
                        <div>
                            <h4 class="font-bold text-red-900">Verification Blocked</h4>
                            <p class="font-medium mt-1">Reason: {{ blockReasonLabel }}</p>
                            <p class="mt-1 text-sm">{{ blockMessage }}</p>
                            <p
                                class="mt-3 text-xs text-red-600/80 font-semibold bg-red-100 inline-block px-2 py-1 rounded">
                                Verification interface is unavailable while your account is blocked.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="fallbackMessage"
                    class="rounded-xl border border-gray-200 bg-gray-50 p-4 mb-6 flex items-center gap-3 text-gray-700">
                    <span class="material-symbols-outlined text-gray-400">info</span>
                    <span class="text-sm font-medium">{{ fallbackMessage }}</span>
                </div>

                <div v-if="!isBlocked && !iframeUnavailable && verification_embed_url" class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                        </div>
                        <p class="text-sm font-medium text-gray-700">
                            Use the secure interface below to verify your identity. The page will refresh automatically
                            upon
                            completion.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden shadow-inner p-1">
                        <iframe ref="verificationIframeRef" :src="localEmbedUrl" class="w-full rounded-lg bg-white"
                            :style="{ height: `${iframeHeight}px` }" loading="lazy" allow="camera; microphone"
                            referrerpolicy="strict-origin-when-cross-origin" @load="handleIframeLoad"
                            @error="handleIframeError" />
                    </div>
                </div>

                <div v-else-if="!isBlocked" class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
                    <span class="material-symbols-outlined text-4xl text-gray-300 mb-3 block">hourglass_empty</span>
                    <h3 class="text-gray-900 font-semibold text-lg mb-1">Interface Unavailable</h3>
                    <p class="text-sm text-gray-500 mb-5 max-w-md mx-auto">
                        Verification UI is not available yet on this branch. Please retry later.
                    </p>
                    <button
                        class="inline-flex items-center gap-2 justify-center px-6 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-black transition-colors focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                        @click="refreshStatus">
                        <span class="material-symbols-outlined text-sm">refresh</span> Try Again Later
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
