<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  modelValue: File, // Bound file
  preview: [String, File], // Initial preview URL
  maxSize: { type: Number, default: 2 * 1024 * 1024 }, // Default 2MB
  allowedTypes: {
    type: Array,
    default: () => ["image/*"],
  },
});

const emit = defineEmits(["update:modelValue", "handleImageOnChange"]);

const fileInput = ref(null); // Ref for file input

const imageConfig = ref({
  image: props.modelValue || null,
  preview: props.preview || null,
  isDirty: false,
  original: props.preview || null,
  error: null,
});

// Watch for changes in the preview prop
watch(
  () => props.preview,
  (newPreview) => {
    imageConfig.value.preview = newPreview;
    imageConfig.value.original = newPreview;
  }
);

const handleChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file type
  if (!props.allowedTypes.some((type) => file.type.match(type))) {
    imageConfig.value.error = "Invalid file type.";
    return;
  }

  // Validate file size
  if (file.size > props.maxSize) {
    imageConfig.value.error = `File is too large. Max size: ${
      props.maxSize / 1024 / 1024
    }MB`;
    return;
  }

  // Set file and preview
  previewImage(file);
  imageConfig.value.image = file;
  imageConfig.value.isDirty = true;
  imageConfig.value.error = null;

  emit("update:modelValue", file);
  emit("handleImageOnChange", file);
};

const previewImage = (file) => {
  const reader = new FileReader();
  reader.onload = (e) => {
    imageConfig.value.preview = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  imageConfig.value.preview = null;
  imageConfig.value.image = null;
  imageConfig.value.isDirty = false;
  imageConfig.value.error = null;

  emit("update:modelValue", null);
  resetInput();
};

const restoreImage = () => {
  imageConfig.value.preview = imageConfig.value.original;
  imageConfig.value.image = null;
  imageConfig.value.isDirty = false;
  imageConfig.value.error = null;

  emit("update:modelValue", null);
};

const resetInput = () => {
  if (fileInput.value) {
    fileInput.value.value = ""; // Clear file input
  }
};
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Preview Section -->
    <div v-if="imageConfig.preview" class="relative h-28 w-28 md:w-56 md:h-36">
      <img
        :src="imageConfig.preview"
        alt="Preview"
        class="w-full h-full object-cover rounded-lg shadow-md"
      />
      <button
        @click="removeImage"
        class="inline-flex absolute top-0 right-0 bg-red-600 text-white p-1 rounded-full shadow-lg hover:bg-red-700"
      >
      <span class="material-symbols-outlined"> close </span>
      </button>
    </div>

    <!-- Upload Button -->
    <div>
      <input
        ref="fileInput"
        type="file"
        id="image-upload"
        class="hidden"
        @change="handleChange"
        :accept="allowedTypes.join(',')"
      />
      <label
        for="image-upload"
        class="cursor-pointer text-sm py-2 px-4 bg-gray-100 text-gray-700 rounded-md shadow hover:bg-gray-200"
      >
        Choose Image
      </label>
    </div>

    <!-- Error Messages -->
    <p v-if="imageConfig.error" class="text-red-500 text-sm mt-1">
      {{ imageConfig.error }}
    </p>

    <!-- Restore Button -->
    <div>
      <button
        v-if="imageConfig.isDirty"
        @click="restoreImage"
        class="py-2 px-4 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
      >
        Restore Original
      </button>
    </div>
  </div>
</template>
