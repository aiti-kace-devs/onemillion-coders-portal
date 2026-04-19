<script setup>
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    user: Object,
    session: {
        type: Object,
        default: null,
    },
    showIntroText: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({});

const revokeOrDeclineModal = ref(false);
const confirmationInput = ref("");

const confirmationPhrase = computed(() =>
    props.session?.id ? "REVOKE" : "DECLINE",
);

const canConfirm = computed(
    () => confirmationInput.value.trim() === confirmationPhrase.value,
);

const confirmationLabel = computed(
    () => `Type ${confirmationPhrase.value} to confirm`,
);

watch(revokeOrDeclineModal, (open) => {
    if (open) {
        confirmationInput.value = "";
    }
});

const showModal = () => {
    revokeOrDeclineModal.value = true;
};

const closeModal = () => {
    revokeOrDeclineModal.value = false;
    confirmationInput.value = "";
};

const submit = () => {
    form.delete(
        route("student.session.destroy", {
            user: props.user.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toastr.success("Admission successfully revoked!");
                closeModal();
            },
            onError: (errors) => {
                toastr.error("Something went wrong");
            },
        },
    );
};
</script>

<template>
    <p v-if="showIntroText" class="text-gray-700 text-sm">
        If the terms of this admission are unfavorable and you wish to decline,
        please click the button below. Revoking your current admission is a
        required step if you want to switch to a different course or be eligible
        for future admission opportunities.
    </p>

    <div :class="showIntroText ? 'mt-6' : ''">
        <button @click="showModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
           bg-red-50 text-red-700 border border-red-200
           hover:bg-red-100 hover:border-red-300
           transition-colors duration-150">
            <span class="material-symbols-outlined text-sm leading-none">cancel</span>
            {{ (session?.id ?? false) ? "Revoke" : "Decline" }}
            Admission
        </button>
    </div>

    <Modal :show="revokeOrDeclineModal" :modalTitle="(session?.id ? 'Revoke' : 'Decline') + ' Admission'"
        @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg text-center font-medium text-gray-900">
                Are you sure you want to
                {{ (session?.id ?? false) ? "revoke" : "decline" }} admission?
            </h2>

            <p class="mt-1 text-sm text-center text-gray-600">
                Please note, this action is irreversible and your slot
                <span class="font-bold">WILL NOT</span> be reserved!
            </p>

            <div class="mt-6 text-left">
                <InputLabel :value="confirmationLabel" />
                <TextInput v-model="confirmationInput" type="text" class="mt-1 block w-full" autocomplete="off"
                    autocapitalize="characters" />
            </div>

            <div class="mt-6 flex justify-center">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>

                <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing || !canConfirm }"
                    :disabled="form.processing || !canConfirm" @click="submit">
                    <template v-if="session?.id ?? false">Revoke</template>
                    <template v-else>Decline</template>
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
