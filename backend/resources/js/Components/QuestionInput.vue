<script setup>
import InputLabel from "./InputLabel.vue";
import TextInput from "./TextInput.vue";
import Checkbox from "./Checkbox.vue";
import RadioInput from "./RadioInput.vue";

import { watch, reactive } from "vue";
import InputError from "./InputError.vue";

const props = defineProps({
  sectionQuestions: {
    type: Array,
    default: [],
  },
  hideLabel: {
    type: Boolean,
    default: false,
  },
  sectionIndex: {
    type: [Number, String],
    default: 0,
  },
  sectionTitle: { 
    type: String, 
    default: "" 
  },
  responses: { 
    type: Object,
     default: {}
     },
  instructors: { 
    type: Array,
     default: [] 
    },
  errors:  Object,
  modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["update:modelValue"]);

const localValue = reactive({ ...props.modelValue });

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

function fieldName(question) {
  return `response_data[${question.field_name}]`;
}
function fieldId(index) {
  return `field-${props.sectionIndex}-${index}`;
}
function options(question) {
  return question.options ? question.options.split(",").map((o) => o.trim()) : [];
}
function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}
function radioLabel(optionValue) {
  switch (optionValue) {
    case "1":
      return "Very Bad";
    case "2":
      return "Bad";
    case "3":
      return "Good";
    case "4":
      return "Very Good";
    case "5":
      return "Excellent";
    default:
      return capitalize(optionValue);
  }
}
function isChecked(question, option) {
  const val = localValue[question.field_name];
  if (Array.isArray(val)) return val.includes(option);
  return false;
}
function onCheckboxComponentChange(question, option, checked) {
  let arr = localValue[question.field_name] || [];
  if (!Array.isArray(arr)) arr = [];
  if (checked) {
    if (!arr.includes(option)) arr.push(option);
  } else {
    arr = arr.filter((o) => o !== option);
  }
  localValue[question.field_name] = arr;
}
function onRadioComponentChange(question, option, checked) {
  if (checked) {
    localValue[question.field_name] = option;
  }
}
</script>

<template>
  <div>
    <div v-for="(question, index) in sectionQuestions" :key="index" class="mb-6">
      <div v-if="!hideLabel" class="mb-2">
        <p>
          {{ question.title
          }}<span class="text-red-600" v-if="question.validators?.required">*</span>
        </p>
      </div>

      <!-- Input types -->
      <template v-if="['text', 'email', 'number', 'password'].includes(question.type)">
        <TextInput
          :type="question.type"
          class="mt-1 block w-full"
          :id="fieldId(index)"
          :name="fieldName(question)"
          :placeholder="question.title"
          v-model="localValue[question.field_name]"
          :required="question.validators?.required"
        />
      </template>

      <template v-else-if="question.type === 'checkbox'">
        <div class="flex flex-col gap-2">
          <div
            v-for="(option, idx) in options(question)"
            :key="idx"
            class="flex items-center"
          >
            <Checkbox
              :id="fieldId(index) + '-opt-' + idx"
              :name="fieldName(question) + '[]'"
              :value="option"
              :checked="isChecked(question, option)"
              @update:checked="(val) => onCheckboxComponentChange(question, option, val)"
            />
            <label :for="fieldId(index) + '-opt-' + idx" class="ml-2 text-base">
              {{ capitalize(option) }}
            </label>
          </div>
        </div>
      </template>

      <template v-else-if="question.type === 'radio'">
        {{ localValue }}
        <div class="flex flex-wrap gap-4">
          <div
            v-for="(option, idx) in options(question)"
            :key="idx"
            class="flex items-center"
          >
            <RadioInput
              :id="fieldId(index) + '-opt-' + idx"
              :name="fieldName(question)"
              :value="option"
              v-model:checked="localValue[fieldName(question)]"
            />
            <label
              :for="fieldId(index) + '-opt-' + idx"
              class="ml-2 text-sm text-capitalize"
            >
              {{ radioLabel(option) }}
            </label>
          </div>
        </div>
      </template>

      <div v-if="question.description" class="text-blue-500 text-xs mt-1">
        {{ question.description }}
      </div>

      <InputError :message="errors?.[question.field_name]?.[0] || ''" />
    </div>
  </div>
</template>
