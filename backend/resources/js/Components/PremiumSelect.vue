<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";

const props = defineProps({
    modelValue: [String, Number, Boolean],
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Select an option",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    }
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const containerRef = ref(null);

const selectedOption = computed(() => {
    return props.options.find(opt => opt.value === props.modelValue) || null;
});

const toggleDropdown = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
    }
};

const selectOption = (option) => {
    emit("update:modelValue", option.value);
    isOpen.value = false;
};

const closeDropdown = (e) => {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener("click", closeDropdown);
});
</script>

<template>
    <div class="relative w-full" ref="containerRef">
        <!-- Trigger -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            class="flex items-center justify-between w-full h-11 px-4 text-base font-medium border rounded-xl shadow-sm appearance-none focus:outline-none ring-offset-1"
            :class="[
                disabled ? 'bg-gray-50 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-200 text-gray-900 shadow-sm hover:border-amber-400',
                isOpen ? 'border-amber-500 ring-2 ring-amber-500/20' : '',
                error ? 'border-red-500 ring-red-500/20 ring-1' : ''
            ]"
        >
            <span :class="{ 'text-gray-400': !selectedOption }">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <span
                class="material-symbols-outlined text-gray-400 transition-transform duration-300"
                :class="{ 'rotate-180 text-amber-500': isOpen }"
            >
                expand_more
            </span>
        </button>

        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute z-[60] w-full mt-2 overflow-hidden bg-white/95 backdrop-blur-md border border-amber-100 rounded-xl shadow-xl max-h-60 overflow-y-auto"
            >
                <div class="p-1.5 space-y-0.5">
                    <button
                        v-for="option in options"
                        :key="option.value"
                        type="button"
                        @click="selectOption(option)"
                        class="flex items-center justify-between w-full px-3 py-2.5 text-base rounded-lg transition-all duration-150 group"
                        :class="[
                            modelValue === option.value
                                ? 'bg-amber-500 text-white font-bold shadow-sm'
                                : 'text-gray-700 hover:bg-amber-50 hover:text-amber-700'
                        ]"
                    >
                        <span>{{ option.label }}</span>
                        <span
                            v-if="modelValue === option.value"
                            class="material-symbols-outlined text-sm"
                        >
                            check
                        </span>
                    </button>

                    <!-- Empty State -->
                    <div v-if="options.length === 0" class="px-4 py-3 text-sm text-gray-500 italic text-center">
                        No options available
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Optional: Fine-tune the scrollbar for the dropdown */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #f3f4f6;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #e5e7eb;
}
</style>
