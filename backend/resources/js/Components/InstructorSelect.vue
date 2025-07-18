<script setup>
import Checkbox from './Checkbox.vue';
import InputLabel from './InputLabel.vue';
import InputError from './InputError.vue';

const props = defineProps({
  instructors: { type: Array, default: () => [] },
  modelValue: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

function onCheckboxChange(id, checked) {
  let arr = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  if (checked) {
    if (!arr.includes(id)) arr.push(id);
  } else {
    arr = arr.filter(val => val !== id);
  }
  emit('update:modelValue', arr);
}
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
          :checked="modelValue.includes(instructor.id)"
          @update:checked="checked => onCheckboxChange(instructor.id, checked)"
          :name="'response_data[instructors][]'"
        />
        <InputLabel :for="'instructor-opt-' + idx" class="ml-2" :value="instructor.name" />
      </div>
    </div>
    <InputError :message="errors && errors.instructors ? errors.instructors[0] : ''" />
    <input type="hidden" name="response_data[instructors_select]" value="true" />
  </div>
</template>