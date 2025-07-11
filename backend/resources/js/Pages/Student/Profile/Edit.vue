<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
  user: Object,
});

const qrCodeModal = ref(false);

watch(qrCodeModal, (newVal) => {
  if (newVal) {
    setTimeout(() => {
      const qrcodeContainer = document.getElementById("qrcode");
      if (qrcodeContainer) {
        qrcodeContainer.innerHTML = ""; // Clear previous QR code
        const innerWidth = Math.floor(window.innerWidth * (7 / 9));
        const width = innerWidth > 400 ? 400 : innerWidth;
        const qrcode = new QRCode(qrcodeContainer, {
          text: props.user.userId, // or user.userId
          width: width,
          height: width,
          colorDark: "black",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H,
          quietZone: 20,
          logo: "../../assets/images/logo-bt.png",
          logoWidth: 170,
          logoHeight: 80,
        });
        window._qrcode = qrcode;
      }
    }, 0);
  }
});

const showQRCodeModal = () => {
  qrCodeModal.value = true;
};

const closeQRCodeModal = () => {
  qrCodeModal.value = false;
};

const downloadQRCode = () => {
  if (window._qrcode) {
    window._qrcode.download(`StudentName-${props.user.userId}`);
  }
};
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div v-if="user.isAdmitted" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <div class="flex flex-col md:flex-row items-center gap-6 relative">
            <div class="rounded-full shadow w-24 h-24 overflow-hidden">
              <img
                :src="`/assets/images/Oval.png`"
                class="h-full w-full object-cover rounded-full"
                alt="profile photo"
              />
            </div>
            <div class="flex-1 w-full text-center md:text-left">
              <div class="text-xl font-semibold text-gray-900">
                {{ user.student_name }}
              </div>
              <div class="text-sm text-gray-500">{{ user.course_name }}</div>
              <div class="text-sm text-gray-400">{{ user.selected_session }} Session</div>
            </div>
            <button
              @click="showQRCodeModal"
              type="button"
              class="w-14 h-14 flex justify-center items-center bg-gray-100 hover:bg-gray-200 rounded-full p-2 shadow text-gray-800 focus:outline-none"
            >
              <span class="material-symbols-outlined"> qr_code </span>
            </button>
          </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdateProfileInformationForm  class="max-w-xl" :user="user" />
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdatePasswordForm class="max-w-xl" />
        </div>
      </div>
    </div>

    <Modal
      :show="qrCodeModal"
      :closeable="true"
      @close="closeQRCodeModal"
      :maxWidth="'lg'"
      :bgColor="'bg-transparent text-white'"
    >
      <div id="qrcode" class="flex justify-center mt-4"></div>

      <div class="flex justify-center mt-6 gap-4">
        <PrimaryButton
          @click="downloadQRCode"
          id="downloadQRCode"
          type="button"
          class="block items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:cursor-not-allowed"
        >
          Download
        </PrimaryButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
