<script setup>
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head } from "@inertiajs/vue3";
import { ref, watch, nextTick, onMounted } from "vue";

const props = defineProps({
  user: Object,
});

const qrCodeModal = ref(false);
const idCardModal = ref(false);
const idCardCanvas = ref(null);

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

const showIDCardModal = () => {
  idCardModal.value = true;
  nextTick(() => {
    generateIDCard();
  });
};

onMounted(() => {
  generateIDCard();
});

const closeIDCardModal = () => {
  idCardModal.value = false;
};

// Helper to wrap text in canvas
function wrapText(ctx, text, x, y, maxWidth, lineHeight, font) {
  ctx.font = font;
  const words = text.split(" ");
  let line = "";
  for (let n = 0; n < words.length; n++) {
    const testLine = line + words[n] + " ";
    const metrics = ctx.measureText(testLine);
    const testWidth = metrics.width;
    if (testWidth > maxWidth && n > 0) {
      ctx.fillText(line, x, y);
      line = words[n] + " ";
      y += lineHeight;
    } else {
      line = testLine;
    }
  }
  ctx.fillText(line, x, y);
}

const generateIDCard = () => {
  const canvas = idCardCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  const scale = 3;
  const w = 340;
  const h = 214;
  canvas.width = w * scale;
  canvas.height = h * scale;
  ctx.scale(scale, scale);
  ctx.clearRect(0, 0, w, h);

  // Draw diagonal gradient background
  const gradient = ctx.createLinearGradient(0, 0, w, h);
  gradient.addColorStop(0, "#fff");
  gradient.addColorStop(1, "#fafafa");
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, w, h);

  // Draw 6 semi-transparent watermark logos evenly distributed
  const watermark = new Image();
  watermark.onload = () => {
    ctx.save();
    ctx.globalAlpha = 0.09;
    const wmRows = 2;
    const wmCols = 3;
    const wmSize = 80;
    const xSpacing = (w - wmCols * wmSize) / (wmCols + 1);
    const ySpacing = (h - wmRows * wmSize) / (wmRows + 1);
    for (let row = 0; row < wmRows; row++) {
      for (let col = 0; col < wmCols; col++) {
        const x = xSpacing + col * (wmSize + xSpacing);
        const y = ySpacing + row * (wmSize + ySpacing);
        ctx.drawImage(watermark, x, y, wmSize, wmSize);
      }
    }
    ctx.globalAlpha = 1.0;
    ctx.restore();
    drawForeground();
  };
  watermark.src = "/assets/images/logo-short.png";

  function drawForeground() {
    // Draw header
    ctx.fillStyle = "#374151";
    ctx.fillRect(0, 0, w, 60);

    // Draw logo image in the header
    const logo = new Image();
    logo.onload = () => {
      const logoWidth = 80;
      const logoHeight = 50;
      const logoX = 10;
      const logoY = 10 + (40 - logoHeight) / 2;
      ctx.drawImage(logo, logoX, logoY, logoWidth, logoHeight);

      // Draw diagonal-opposite image in bottom-right
      const diagImg = new Image();
      diagImg.onload = () => {
        const diagWidth = 100;
        const diagHeight = 35;
        const diagX = w - 115;
        const diagY = h - diagHeight - 10;
        ctx.save();
        ctx.globalAlpha = 0.7;
        ctx.drawImage(diagImg, diagX, diagY, diagWidth, diagHeight);
        ctx.globalAlpha = 1.0;
        ctx.restore();
        drawProfileAndText();
      };
      diagImg.src = "/assets/home/images/c-black.png";
    };
    logo.src = "/assets/images/logo.png";
  }

  function drawProfileAndText() {
    // Draw profile image (circular)
    const img = new Image();
    img.crossOrigin = "Anonymous";
    
    // Create text & details drawing function
    const drawTextDetailsAndQR = () => {
      // Add text content with wrapping
      ctx.fillStyle = "#fff";
      ctx.font = "bold 14px Figtree";
      ctx.textAlign = "left";
      ctx.textBaseline = "top";
      ctx.save();
      wrapText(ctx, "STUDENT ID CARD", 145, 25, 200, 18, "bold 14px Figtree");
      ctx.restore();

      ctx.fillStyle = "#000";
      ctx.font = "bold 15px Figtree";
      wrapText(
        ctx,
        (props.user.student_name || "N/A").toUpperCase(),
        105,
        70,
        220,
        18,
        "bold 15px Figtree"
      );

      ctx.fillStyle = "#000";
      ctx.font = "bold 10px Figtree";
      wrapText(
        ctx,
        (props.user.course_name || "N/A").toUpperCase(),
        105,
        105,
        220,
        14,
        "bold 10px Figtree"
      );

      // Details section: stack Index No, Cohort, Validity vertically
      const detailsX = 15;
      const labelWidth = 55;
      const valueX = detailsX + labelWidth;
      let detailsY = 158;
      const rowHeight = 16;

      // Index No.
      ctx.fillStyle = "#374151";
      ctx.font = "bold 9px Figtree";
      ctx.fillText("Index No:", detailsX, detailsY);
      ctx.fillStyle = "#000";
      ctx.font = "bold 10px Figtree";
      ctx.fillText(props.user.student_id || "N/A", valueX, detailsY);

      // Cohort
      detailsY += rowHeight;
      ctx.fillStyle = "#374151";
      ctx.font = "bold 9px Figtree";
      ctx.fillText("Cohort:", detailsX, detailsY);
      ctx.fillStyle = "#000";
      ctx.font = "bold 9px Figtree";
      ctx.fillText((props.user.session_dates || "N/A").toUpperCase(), valueX, detailsY);

      // Validity
      detailsY += rowHeight;
      ctx.fillStyle = "#374151";
      ctx.font = "bold 9px Figtree";
      ctx.fillText("Validity:", detailsX, detailsY);
      ctx.fillStyle = "#000";
      ctx.font = "bold 9px Figtree";
      ctx.fillText((props.user.validity_period || "N/A").toUpperCase(), valueX, detailsY);

      ctx.fillStyle = "#374151";
      ctx.fillRect(0, h - 8, w, 5);

      // Draw QR code (using QRCode library to canvas)
      const qrCanvas = document.createElement("canvas");
      new window.QRCode(qrCanvas, {
        text: props.user.userId || "0000000000",
        width: 56 * scale,
        height: 56 * scale,
        colorDark: "#000",
        colorLight: "#fff",
        correctLevel: window.QRCode.CorrectLevel.H,
      });
      setTimeout(() => {
        ctx.drawImage(qrCanvas, 250, 120, 56, 56);
      }, 100); // Wait for QR to render
    };

    img.onload = () => {
      const centerX = 50;
      const centerY = h / 2;
      const radius = 35;
      ctx.save();
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
      ctx.clip();
      const imgSize = radius * 2;
      const imgX = centerX - imgSize / 2;
      const imgY = centerY - imgSize / 2;
      ctx.drawImage(img, imgX, imgY, imgSize, imgSize);
      ctx.restore();
      
      drawTextDetailsAndQR();
    };

    img.onerror = () => {
        // Skip drawing the circular image if it fails (CORS block or 404)
        drawTextDetailsAndQR();
    };

    img.src = props.user.ghcard_image_url || "/assets/images/Oval.png";
  }
};

const downloadIDCard = () => {
  const canvas = idCardCanvas.value;
  if (!canvas) return;

  // Create a temporary link element
  const link = document.createElement("a");
  link.download = `ID_Card_${props.user.student_name || props.user.userId}.png`;
  link.href = canvas.toDataURL("image/png");
  link.click();
};
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-2">
        <h2 class="font-black text-2xl text-gray-900 tracking-tight">
          Profile
        </h2>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div v-if="user.isAdmitted" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <div class="flex flex-col md:flex-row items-center gap-6 relative">
            <div class="rounded-full shadow w-24 h-24 overflow-hidden">
              <img
                :src="user.ghcard_image_url || `/assets/images/Oval.png`"
                class="h-full w-full object-cover rounded-full"
                alt="profile photo"
              />
            </div>
            <div class="flex-1 w-full text-center md:text-left">
              <div class="text-xl font-semibold text-gray-900">
                {{ user.student_name }}
              </div>
              <div class="text-sm text-gray-500">{{ user.course_name }}</div>
              <div class="text-sm text-gray-400">
                {{ user.session_dates }} {{ user.session_name }} Session {{ user.session_time ? `(${user.session_time})` : '' }}
              </div>
            </div>
            <div class="flex items-center gap-4">
              <button
                @click="showIDCardModal"
                type="button"
                class="w-14 h-14 flex justify-center items-center bg-gray-100 hover:bg-gray-200 rounded-full p-2 shadow text-gray-800 focus:outline-none"
              >
                <span class="material-symbols-outlined"> id_card </span>
              </button>

              <button
                @click="showQRCodeModal"
                type="button"
                class="w-14 h-14 flex justify-center items-center bg-gray-100 hover:bg-gray-200 rounded-full p-2 shadow text-gray-800 focus:outline-none"
              >
                <span class="material-symbols-outlined"> qr_code </span>
              </button>
            </div>
          </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdateProfileInformationForm class="max-w-xl" :user="user" />
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
        <PrimaryButton @click="downloadQRCode" id="downloadQRCode" type="button">
          Download
        </PrimaryButton>
      </div>
    </Modal>

    <Modal
      :show="idCardModal"
      :closeable="true"
      @close="closeIDCardModal"
      :maxWidth="'lg'"
      :bgColor="'bg-transparent text-white'"
    >
      <!-- ID Card Canvas Container -->
      <div class="flex justify-center mt-4">
        <canvas
          ref="idCardCanvas"
          class="rounded shadow-sm"
          style="width: 510px; height: 321px; max-width: 100%"
        ></canvas>
      </div>

      <!-- Download Button -->
      <div class="flex justify-center mt-6">
        <PrimaryButton @click="downloadIDCard" type="button"> Download </PrimaryButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
