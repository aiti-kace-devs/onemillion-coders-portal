<script setup>
import Checkbox from "./Checkbox.vue";
import InputError from "./InputError.vue";
import { onMounted, reactive, watch } from "vue";

const props = defineProps({
  instructors: { type: Array, default: () => [] },
  modelValue: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:modelValue"]);

const localValue = reactive({
  instructors: Array.isArray(props.modelValue) ? [...props.modelValue] : [],
});

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(localValue, val || {});
  }
);

watch(
  localValue,
  (val) => {
    emit("update:modelValue", { ...val });
  },
  { deep: true }
);

onMounted(() => (localValue.instructors_select = true));
</script>
<template>
  <div>
    <p>Select instructors that taught you?</p>
    <div class="flex flex-col gap-2 mt-2">
      <div
        v-for="(instructor, idx) in instructors"
        :key="instructor.id"
        class="flex items-center"
      >
        <Checkbox
          :id="'instructor-opt-' + idx"
          :value="instructor.id"
          v-model:checked="localValue.instructors"
          :name="'response_data[instructors][]'"
        />
        <label :for="'instructor-opt-' + idx" class="ml-2">{{ instructor.name }} </label>
      </div>
    </div>
    <InputError :message="errors?.[`response_data.instructors`]?.[0] || ''" />
  </div>
</template>
