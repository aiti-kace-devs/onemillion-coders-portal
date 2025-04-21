<template>
  <Head title="Forms" />

  <AuthenticatedLayout>
    <MenuDropdown ref="menuDropdown" />
    <template #header>
      <div class="flex items-center">Manage List</div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-end">
              <LinkButton :href="route('admin.lists.create')">
                <span class="material-symbols-outlined mr-2"> add </span>
                create form
              </LinkButton>
            </div>
            <div>
              <h1>Create List</h1>
              <form @submit.prevent="form.post(route('admin.lists.store'))">
                <div class="grid grid-cols-2 gap-4 mt-3">
                  <div>
                    <InputLabel for="view_name" value="View Name:" />
                    <TextInput
                      id="view_name"
                      type="text"
                      class="w-full"
                      v-model="form.view_name"
                      :class="{ 'border-red-600': form.errors.view_name }"
                    />
                    <InputError :message="form.errors.view_name" />
                  </div>

                  <div>
                    <InputLabel for="table_name" value="Table Name:" />
                    <SelectInput
                      id="table_name"
                      v-model="form.table_name"
                      class="w-full"
                      @change="fetchColumns"
                    >
                      <option v-for="table in tables" :key="table" :value="table">
                        {{ table }}
                      </option>
                    </SelectInput>
                    <InputError :message="form.errors.table_name" />
                  </div>
                </div>

                <div class="mt-3">
                  <InputLabel value="Columns:" />
                  <SelectInput
                    v-model="form.columns"
                    multiple
                    class="w-full"
                    :class="{ 'border-red-600': form.errors.columns }"
                  >
                    <option v-for="column in availableColumns" :key="column" :value="column">
                      {{ column }}
                    </option>
                  </SelectInput>
                  <InputError :message="form.errors.columns" />
                </div>

                <div class="mt-3">
                  <InputLabel value="Where Conditions:" />
                  <div id="where-conditions-container">
                    <div v-for="(condition, index) in form.where_conditions" :key="'where-' + index" class="mb-4">
                      <div class="grid grid-cols-3 gap-2">
                        <div>
                          <TextInput
                            type="text"
                            v-model="condition.column"
                            placeholder="Column"
                            class="w-full"
                          />
                        </div>
                        <div>
                          <SelectInput v-model="condition.operator" class="w-full">
                            <option value="==">=</option>
                            <option value="!=">!=</option>
                            <option value="<">&lt;</option>
                            <option value=">">&gt;</option>
                            <option value="<=">&lt;=</option>
                            <option value=">=">&gt;=</option>
                            <option value="LIKE">LIKE</option>
                            <option value="NOT LIKE">NOT LIKE</option>
                            <option value="IN">IN</option>
                            <option value="NOT IN">NOT IN</option>
                          </SelectInput>
                        </div>
                        <div>
                          <TextInput type="text" v-model="condition.value" placeholder="Value" class="w-full" />
                        </div>
                      </div>
                      <SecondaryButton type="button" @click="removeWhereCondition(index)" class="mt-2">Remove</SecondaryButton>
                    </div>
                    <PrimaryButton type="button" @click="addWhereCondition">Add Condition</PrimaryButton>
                  </div>
                </div>

                <div class="mt-3">
                  <InputLabel value="Joins:" />
                  <div id="joins-container">
                    <div v-for="(join, index) in form.joins" :key="'join-' + index" class="mb-4">
                      <div class="grid grid-cols-5 gap-2">
                        <div>
                          <SelectInput
                            v-model="join.table"
                            placeholder="Join Table"
                            class="w-full"
                            @change="fetchJoinColumns(index)"
                          >
                            <option v-for="table in tables" :key="table" :value="table">
                              {{ table }}
                            </option>
                          </SelectInput>
                        </div>
                        <div>
                          <SelectInput v-model="join.type" class="w-full">
                            <option value="inner">Inner Join</option>
                            <option value="left">Left Join</option>
                            <option value="right">Right Join</option>
                            <option value="cross">Cross Join</option>
                          </SelectInput>
                        </div>
                        <div>
                          <TextInput type="text" v-model="join.first_column" placeholder="First Column" class="w-full" />
                        </div>
                        <div>
                          <SelectInput v-model="join.operator" class="w-full">
                            <option value="=">=</option>
                            <option value=">">></option>
                            <option value="<">&lt;</option>
                            <option value=">=">&gt;=</option>
                            <option value="<=">&lt;=</option>
                          </SelectInput>
                        </div>
                        <div>
                          <TextInput type="text" v-model="join.second_column" placeholder="Second Column" class="w-full" />
                        </div>
                      </div>
                      <div v-if="join.availableColumnsText" class="text-sm text-gray-500">
                        Available Columns: {{ join.availableColumnsText }}
                      </div>
                      <SecondaryButton type="button" @click="removeJoin(index)" class="mt-2">Remove</SecondaryButton>
                    </div>
                    <PrimaryButton type="button" @click="addJoin">Add Join</PrimaryButton>
                  </div>
                </div>

                <div class="mt-3">
                  <InputLabel for="order_by_column" value="Order By Column:" />
                  <TextInput
                    v-model="form.order_by_column"
                    id="order_by_column"
                    type="text"
                    class="w-full"
                  />
                </div>

                <div class="mt-3">
                  <InputLabel for="order_by_direction" value="Order By Direction:" />
                  <SelectInput v-model="form.order_by_direction" id="order_by_direction" class="w-full">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                  </SelectInput>
                </div>

                <div class="mt-3">
                  <InputLabel for="limit" value="Limit:" />
                  <TextInput
                    type="number"
                    v-model="form.limit"
                    id="limit"
                    class="w-full"
                    :class="{ 'border-red-600': form.errors.limit }"
                  />
                  <InputError :message="form.errors.limit" />
                </div>

                <div class="mt-6">
                  <PrimaryButton type="submit">Create List</PrimaryButton>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';  // Import Head
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Adjust the path if needed
import MenuDropdown from '@/Components/MenuDropdown.vue'; // Adjust the path if needed
import LinkButton from '@/Components/LinkButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

export default {
  components: {
    Head,            // Add to the components
    AuthenticatedLayout,
    MenuDropdown,
    LinkButton,
    InputLabel,
    SelectInput,
    TextInput,
    InputError,
    PrimaryButton,
    SecondaryButton,
  },
  props: {
    tables: Array,
    // availableColumns: Array,
  },
  data() {
    return {
      form: useForm({
        view_name: '',
        table_name: '',
        columns: [],
        where_conditions: [],
        joins: [],
        order_by_column: '',
        order_by_direction: 'asc',
        limit: null,
      }),
      availableColumnsText: '',
      availableColumns: [],
    };
  },
  methods: {
    addWhereCondition() {
      this.form.where_conditions.push({ column: '', operator: '==', value: '' });
    },
    removeWhereCondition(index) {
      this.form.where_conditions.splice(index, 1);
    },
    addJoin() {
      this.form.joins.push({
        table: '',
        type: 'inner',
        first_column: '',
        operator: '=',
        second_column: '',
        availableColumns: [],
        availableColumnsText: '',
      });
    },
    removeJoin(index) {
      this.form.joins.splice(index, 1);
    },
    fetchColumns() {
      if (this.form.table_name) {
        axios.get(
          route('admin.lists.get-table-columns'),
          { params : {table_name: this.form.table_name } }).then(
            (response) => {
             console.log('Columns fetched:', response);
              this.availableColumns = response.data.availableColumns;
              this.form.columns = [];
            }
          ).catch((error) => {
              console.error('Error fetching columns:', error);
            });
      } else {
        this.availableColumns = [];
        this.form.columns = [];
      }
    },
    fetchJoinColumns(index) {
      const join = this.form.joins[index];
      if (join.table) {
        axios.get(
          route('admin.lists.get-table-columns'),
          {params: { table_name: join.table }})
        .then(
          (response) => {
            const columns = response.data.availableColumns;
            join.availableColumns = columns;
            join.availableColumnsText = columns.join(', ');
          }
        ).catch(
          (error) => {
            console.error('Error fetching join columns:', error);
            join.availableColumns = [];
            join.availableColumnsText = '';
          }
        );
      } else {
        join.availableColumns = [];
        join.availableColumnsText = '';
      }
    },
    submit() {
      this.form.post(route('admin.lists.store'));
    },
  },
};
</script>
