<script setup>
import { computed, onMounted, onUnmounted, watch } from "vue";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  modalTitle: {
    type: String,
  },
  maxWidth: {
    type: String,
    default: "2xl",
  },
  bgColor: {
    type: String,
    default: "bg-white",
  },
  closeable: {
    type: Boolean,
    default: true,
  },
  teleportTo: {
    type: String,
    default: "body",
  },
});

const emit = defineEmits(["close"]);

watch(
  () => props.show,
  () => {
    if (props.show) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = null;
    }
  }
);

const close = () => {
  if (props.closeable) {
    emit("close");
  }
};

const closeOnEscape = (e) => {
  if (e.key === "Escape" && props.show) {
    close();
  }
};

onMounted(() => document.addEventListener("keydown", closeOnEscape));

onUnmounted(() => {
  document.removeEventListener("keydown", closeOnEscape);
  document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
  return {
    sm: "sm:max-w-sm",
    md: "sm:max-w-md md:max-w-lg",
    lg: "sm:max-w-lg lg:max-w-4xl",
    xl: "sm:max-w-xl",
    "2xl": "sm:max-w-2xl",
  }[props.maxWidth];
});
</script>

<template>
  <teleport :to="props.teleportTo">
    <transition leave-active-class="duration-200">
      <div
        v-show="show" id="modale"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[10003]"
        scroll-region
      >
        <transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-show="show" class="fixed inset-0 transform transition-all">
            <div class="absolute inset-0 bg-gray-500 opacity-75" />
          </div>
        </transition>

        <transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-show="show"
            class="mb-6 p-5 rounded-sm overflow-hidden shadow-sm transform transition-all sm:w-full sm:mx-auto"
            :class="[maxWidthClass, bgColor]"
          >
            <div 
              class="flex items-center" 
              :class="props.modalTitle ? 'justify-between mb-6' : 'justify-end mb-2'"
            >
              <p v-if="props.modalTitle" class="text-xl font-normal capitalize">{{ props.modalTitle }}</p>
              <button 
                @click="close" 
                v-if="props.closeable"
                class="hover:opacity-75 transition-opacity"
              >
                <span class="material-symbols-outlined text-2xl"> close </span>
              </button>
            </div>
            <slot />
          </div>
        </transition>
      </div>
    </transition>
  </teleport>
</template>
