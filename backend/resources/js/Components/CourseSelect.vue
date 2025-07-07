/** * CourseSelect Component. * * This component provides a user interface for selecting a
location and then a course * based on the selected location. It uses two select elements,
one for locations and * another for courses. The course selection is dynamically updated
based on the * location selected. * * @prop {Array} locations - An array of location
objects, each with an `id` and `name` property. * @data {String} selectedLocation - The ID
of the currently selected location. * @data {String} selectedCourse - The ID of the
currently selected course. * @data {Array} filteredCourses - An array of courses filtered
based on the selected location. * @method updateCourses - Updates the `filteredCourses`
array when the `selectedLocation` changes. */

<!-- v-model="form.response_data[question.field_name]" -->
<template>
  <div>
    <InputLabel for="branch" value="Location" :required="true" class="mt-2" />
    <SelectInput
      class="mt-1 mb-1 w-full"
      v-model="selectedLocation"
      @change="clearCentre"
      :required="true"
    >
      <option value="" disabled selected>-- Select an option --</option>
      <option v-for="branch in branches" :key="branch.id" :value="branch.id">
        {{ branch.title }}
      </option>
    </SelectInput>

     <InputLabel for="centre" value="Centre (Select a location first)" :required="true" class="mt-2" />
    <SelectInput
      class="mt-1 mb-1 w-full"
      v-model="selectedCentre"
      @change="clearCourse"
      :required="true"
    >
      <option value="" disabled selected>-- Select an option --</option>
      <option v-for="centre in filteredCentres" :key="centre.id" :value="centre.id">
        {{ centre.title }}
      </option>
    </SelectInput>

    <InputLabel for="course" value="Course (Select a centre first)" :required="true" class="mt-2" />
    <SelectInput
      class="mt-1 w-full"
      v-model="form.response_data.course_id"
      name="course_id"
      :required="true"
      :class="{'border-red-600': form.errors['response_data.course_id']}"
    >
      <option value="" disabled selected>-- Select an option --</option>
      <option v-for="course in filteredCourses" :key="course.id" :value="course.id">
        {{ course.course_name }}
      </option>
    </SelectInput>
  </div>
</template>

<script>
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";

export default {
  components: {
    SelectInput,
    InputLabel,
  },
  props: {
    branches: Array,
    courses: Array,
    form: Object,
    centres: Array,
  },
  data() {
    return {
      selectedLocation: "",
      selectedCourse: "",
        selectedCentre: "",
    };
  },
  computed: {
    filteredCourses() {
      return this.courses.filter((course) => course.centre_id == this.selectedCentre);
    },
    filteredCentres() {
      return this.centres.filter((centre) => centre.branch_id == this.selectedLocation);
    },
  },
  methods: {
    clearCourse() {
        this.selectedCourse = "";
    },
    clearCentre() {
        this.selectedCentre = "";
        this.clearCourse();
    },
  },
};
</script>
