<template>

    <Head title="Create List" />

    <AuthenticatedLayout>
        <MenuDropdown ref="menuDropdown" />
        <template #header>
            <div class="flex items-center">Create New List</div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold text-gray-800">Create New List</h1>
                            <LinkButton :href="route('admin.lists.index')" class="bg-gray-100 hover:bg-gray-200">
                                Back to Lists
                            </LinkButton>
                        </div>

                        <form @submit.prevent="form.post(route('admin.lists.store'))" class="space-y-6">
                            <!-- Basic Information Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="view_name" value="View Name *" />
                                        <TextInput id="view_name" type="text" class="w-full" v-model="form.view_name"
                                            :class="{ 'border-red-600': form.errors.view_name }" required />
                                        <InputError :message="form.errors.view_name" />
                                    </div>

                                    <div>
                                        <InputLabel for="table_name" value="Base Table *" />
                                        <SelectInput id="table_name" v-model="form.table_name" class="w-full"
                                            @change="fetchColumns" required
                                            :class="{ 'border-red-600': form.errors.table_name }">
                                            <option value="">Select a table</option>
                                            <!-- <option v-for="table in tables" :key="table" :value="table">
                                                {{ table }}
                                            </option> -->

                                            <optgroup label="Tables">
                                                <option v-for="table in tables.tables" :key="'table-' + table"
                                                    :value="table">
                                                    {{ table }}
                                                </option>
                                            </optgroup>

                                            <!-- Views Group -->
                                            <optgroup label="Views">
                                                <option v-for="view in tables.views" :key="'view-' + view"
                                                    :value="view">
                                                    {{ view }}
                                                </option>
                                            </optgroup>
                                        </SelectInput>
                                        <InputError :message="form.errors.table_name" />
                                    </div>
                                </div>
                            </div>

                            <!-- Columns Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Columns Configuration</h2>

                                <!-- Selected Columns Display -->
                                <div class="mb-6">
                                    <div class="flex justify-between items-center">
                                        <InputLabel value="Selected Columns" />
                                        <SecondaryButton type="button" @click="selectAllColumns"
                                            :disabled="availableColumns.length === 0 || allColumnsSelected"
                                            class="text-sm">
                                            <span class="material-symbols-outlined mr-1 text-sm">playlist_add</span>
                                            Select All Columns
                                        </SecondaryButton>
                                    </div>
                                    <div v-if="form.columns.length > 0" class="mt-2 flex flex-wrap gap-2">
                                        <div v-for="(column, index) in form.columns" :key="index"
                                            class="inline-flex items-center bg-blue-50 text-blue-800 rounded-full px-3 py-1 text-sm font-medium">
                                            <span class="truncate max-w-[200px]">
                                                {{ column.name.split('.')[1] || column.name }}
                                                <span v-if="column.alias" class="text-blue-600"> (as {{ column.alias
                                                }})</span>
                                            </span>
                                            <button type="button" @click="removeColumn(index)"
                                                class="ml-1.5 text-blue-500 hover:text-blue-700 focus:outline-none">
                                                <span class="material-symbols-outlined text-sm">close</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="mt-2 text-gray-500 italic">
                                        No columns selected (all columns will be included)
                                    </div>
                                </div>

                                <!-- Add Column Form -->
                                <div class="border p-4 rounded-lg bg-gray-50">
                                    <InputLabel value="Add New Column" />
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <SelectInput v-model="newColumn.name" class="w-full"
                                                :disabled="!form.table_name"
                                                :class="{ 'border-red-600': form.errors['newColumn.name'] }">
                                                <option value="">Select Column</option>
                                                <optgroup v-for="(group, groupName) in groupedAvailableColumns"
                                                    :key="groupName" :label="groupName">
                                                    <option v-for="col in group" :key="col.name" :value="col.name"
                                                        :disabled="isColumnSelected(col.name)">
                                                        {{ col.name }} ({{ col.type }})
                                                    </option>
                                                </optgroup>
                                            </SelectInput>
                                            <InputError :message="form.errors['newColumn.name']" />
                                        </div>
                                        <div>
                                            <TextInput type="text" v-model="newColumn.alias"
                                                placeholder="Alias (optional)" class="w-full"
                                                :class="{ 'border-red-600': form.errors['newColumn.alias'] }" />
                                            <InputError :message="form.errors['newColumn.alias']" />
                                        </div>
                                        <div class="flex items-end">
                                            <PrimaryButton type="button" @click="addColumnToSelected"
                                                :disabled="!newColumn.name" class="w-full">
                                                Add Column
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Joins Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-medium text-gray-900">Table Joins</h2>
                                    <SecondaryButton type="button" @click="addJoin">
                                        Add Join
                                    </SecondaryButton>
                                </div>

                                <div v-if="form.joins.length > 0" class="space-y-4">
                                    <div v-for="(join, index) in form.joins" :key="'join-' + index"
                                        class="border p-4 rounded-lg bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                                            <div>
                                                <SelectInput v-model="join.table" placeholder="Join Table"
                                                    class="w-full" @change="fetchJoinColumns(index)">
                                                    <option value="">Select Table</option>
                                                    <optgroup label="Tables">
                                                        <option v-for="table in tables.tables" :key="'table-' + table"
                                                            :value="table" :disabled="table === form.table_name">
                                                            {{ table }}
                                                        </option>
                                                    </optgroup>

                                                    <!-- Views Group -->
                                                    <optgroup label="Views">
                                                        <option v-for="view in tables.views" :key="'view-' + view"
                                                            :value="view" :disabled="table === form.table_name">
                                                            {{ view }}
                                                        </option>
                                                    </optgroup>

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
                                                <SelectInput v-model="join.first_column" class="w-full">
                                                    <option value="">Select Column</option>
                                                    <option v-for="col in join.availableColumns" :key="col"
                                                        :value="join.table + '.' + col">
                                                        {{ join.table }}.{{ col }}
                                                    </option>
                                                </SelectInput>
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
                                                <SelectInput v-model="join.second_column" class="w-full">
                                                    <option value="">Select Column</option>
                                                    <option
                                                        v-for="col in availableColumns.filter(c => !join.availableColumns.includes(c.split('.')[1]))"
                                                        :key="col" :value="col">
                                                        {{ col }}
                                                    </option>
                                                </SelectInput>
                                            </div>
                                        </div>
                                        <div v-if="join.availableColumnsText" class="mt-2 text-sm text-gray-500">
                                            Available Columns: {{ join.availableColumnsText }}
                                        </div>
                                        <div class="flex justify-end mt-2">
                                            <button type="button" @click="removeJoin(index)"
                                                class="text-sm text-red-500 hover:text-red-700">
                                                Remove Join
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 italic">
                                    No joins added yet
                                </div>
                            </div>

                            <!-- Filter Conditions Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-medium text-gray-900">Filter Conditions</h2>
                                    <SecondaryButton type="button" @click="addWhereCondition">
                                        Add Condition
                                    </SecondaryButton>
                                </div>

                                <div v-if="form.where_conditions.length > 0" class="space-y-4">
                                    <div v-for="(condition, index) in form.where_conditions" :key="'where-' + index"
                                        class="border p-4 rounded-lg bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                            <div>
                                                <SelectInput v-model="condition.column" class="w-full"
                                                    :class="{ 'border-red-600': conditionErrors[index]?.column }">
                                                    <option value="">Select Column</option>
                                                    <option v-for="col in allAvailableColumns" :key="col" :value="col">
                                                        {{ col.split('.').pop() }} <!-- Show just the column name -->
                                                        <template v-if="col.includes('.')">
                                                            (from {{ col.split('.')[0] }})
                                                        </template>
                                                    </option>
                                                </SelectInput>
                                                <InputError :message="where_conditions?.[index]?.column" />
                                            </div>
                                            <div>
                                                <SelectInput v-model="condition.operator" class="w-full"
                                                    :class="{ 'border-red-600': conditionErrors[index]?.operator }">
                                                    <option value="=">=</option>
                                                    <option value="!=">!=</option>
                                                    <option value="<">&lt;</option>
                                                    <option value=">">&gt;</option>
                                                    <option value="<=">&lt;=</option>
                                                    <option value=">=">&gt;=</option>
                                                    <option value="LIKE">LIKE</option>
                                                    <option value="NOT LIKE">NOT LIKE</option>
                                                    <option value="IN">IN</option>
                                                    <option value="NOT IN">NOT IN</option>
                                                    <option value="IS NULL">IS NULL</option>
                                                    <option value="IS NOT NULL">IS NOT NULL</option>
                                                    <option value="BETWEEN">BETWEEN</option>
                                                </SelectInput>
                                                <InputError :message="where_conditions?.[index]?.operator" />
                                            </div>
                                            <div class="md:col-span-2">
                                                <template
                                                    v-if="['IN', 'NOT IN', 'BETWEEN'].includes(condition.operator)">
                                                    <TextInput type="text" v-model="condition.value"
                                                        :placeholder="condition.operator === 'BETWEEN' ? 'value1, value2' : 'value1, value2, value3...'"
                                                        class="w-full" />
                                                </template>
                                                <template
                                                    v-else-if="['IS NULL', 'IS NOT NULL'].includes(condition.operator)">
                                                    <TextInput type="text" v-model="condition.value"
                                                        placeholder="No value needed" class="w-full" disabled />
                                                </template>

                                                <template v-else>
                                                    <div>
                                                        <template v-if="getColumnType(condition.column) === 'datetime'">
                                                            <input type="datetime-local" v-model="condition.value"
                                                                class="w-full border rounded px-2 py-1">
                                                        </template>
                                                        <template
                                                            v-else-if="getColumnType(condition.column) === 'integer'">
                                                            <input type="number" v-model="condition.value"
                                                                class="w-full border rounded px-2 py-1" step="1">
                                                        </template>
                                                        <template
                                                            v-else-if="getColumnType(condition.column) === 'float'">
                                                            <input type="number" v-model="condition.value"
                                                                class="w-full border rounded px-2 py-1" step="0.01">
                                                        </template>
                                                        <template v-else>
                                                            <TextInput type="text" v-model="condition.value"
                                                                placeholder="Value" class="w-full" />
                                                        </template>
                                                    </div>
                                                </template>
                                                <InputError :message="where_conditions?.[index]?.value" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-2">
                                            <button type="button" @click="removeWhereCondition(index)"
                                                class="text-sm text-red-500 hover:text-red-700">
                                                Remove Condition
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 italic">
                                    No conditions added yet
                                </div>
                            </div>

                            <!-- Sorting and Limits Section -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                <div>
                                    <InputLabel for="order_by_column" value="Order By Column" />
                                    <SelectInput v-model="form.order_by_column" id="order_by_column" class="w-full"
                                        :class="{ 'border-red-600': form.errors.order_by_column }">
                                        <option value="">Select Column</option>
                                        <option v-for="column in selectedColumnsForOrderBy" :key="column.name"
                                            :value="column.name">
                                            {{ column.alias || column.name.split('.').pop() }}
                                        </option>
                                    </SelectInput>
                                    <InputError :message="form.errors.order_by_column" />
                                </div>

                                <div>
                                    <InputLabel for="order_by_direction" value="Order Direction" />
                                    <SelectInput v-model="form.order_by_direction" id="order_by_direction"
                                        class="w-full">
                                        <option value="asc">Ascending</option>
                                        <option value="desc">Descending</option>
                                    </SelectInput>
                                </div>

                                <div>
                                    <InputLabel for="limit" value="Row Limit" />
                                    <TextInput type="number" v-model="form.limit" id="limit" class="w-full" min="1"
                                        placeholder="Leave empty for no limit" />
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-4 pt-6">
                                <div class="mt-6 flex items-center space-x-4">
                                    <SecondaryButton type="button" @click="resetForm" :disabled="form.processing">
                                        Reset
                                    </SecondaryButton>
                                    <SecondaryButton type="button" @click="submitForm(true)"
                                        :disabled="form.processing || !form.table_name" class="bg-green-300">
                                        <span v-if="form.processing && isTesting">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Testing...
                                        </span>
                                        <span v-else>Test Query</span>
                                    </SecondaryButton>

                                    <PrimaryButton type="button" @click="submitForm(false)" :disabled="form.processing">
                                        <span v-if="form.processing && !isTesting">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Saving...
                                        </span>
                                        <span v-else>Save List</span>
                                    </PrimaryButton>

                                    <div v-if="testResult" class="text-sm">
                                        <span class="font-medium">Test Results:</span>
                                        {{ testResult.count }} rows
                                        <span v-if="testResult.time" class="text-gray-500">({{ testResult.time
                                            }}ms)</span>
                                    </div>
                                </div>

                                <!-- Query Details Accordion (same as before) -->
                                <!-- Query Details Accordion -->

                            </div>
                            <div v-if="testResult" class="bg-gray-50 p-4 rounded-lg">
                                <button type="button" @click="showQueryDetails = !showQueryDetails"
                                    class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                                    <span class="material-symbols-outlined mr-1 text-base">
                                        {{ showQueryDetails ? 'expand_less' : 'expand_more' }}
                                    </span>
                                    {{ showQueryDetails ? 'Hide' : 'Show' }} Query Details
                                </button>


                            </div>
                            <div v-if="showQueryDetails" class="mt-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">SQL Query</h4>
                                        <pre class="bg-white p-3 rounded text-xs font-mono overflow-x-auto">{{
                            testResult.sql }}</pre>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">Bindings</h4>
                                        <pre class="bg-white p-3 rounded text-xs font-mono overflow-x-auto">{{
                            testResult.bindings }}</pre>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MenuDropdown from '@/Components/MenuDropdown.vue';
import LinkButton from '@/Components/LinkButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

export default {
    components: {
        Head,
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
            availableColumns: [],
            newColumn: { name: '', alias: '' },
            isLoadingColumns: false,
            conditionErrors: [],
            testingQuery: false,
            testResult: null,
            showQueryDetails: false,
        };
    },
    computed: {
        allAvailableColumns() {
            // Combine base table columns and all join table columns
            const baseColumns = this.availableColumns;
            const joinColumns = this.form.joins.flatMap(join =>
                join.availableColumns.map(col => ({ name: `${join.table}.${col.name}`, type: col.type }))
            );
            return [...new Set([...baseColumns, ...joinColumns])];
        },
        selectedColumnsForOrderBy() {
            // Return only columns that have been selected in the form
            return this.form.columns.map(col => ({
                name: col.name,
                alias: col.alias || null
            }));
        },
        allColumnsSelected() {
            return this.form.columns.length === this.availableColumns.length;
        },

        columnDisplayName() {
            return (column) => {
                const name = column.name.split('.').pop();
                return column.alias ? `${name} (as ${column.alias})` : name;
            };
        }
    },
    methods: {
        isColumnSelected(columnName) {
            return this.form.columns.some(col => col.name.startsWith(columnName));
        },
        getColumnType(columnName) {
            // Handle table.column format
            const simpleName = columnName.includes('.')
                ? columnName.split('.')[1]
                : columnName;

            const column = this.availableColumns.find(c => c.name === simpleName);
            return column ? column.type.toLowerCase() : 'string';
        },
        selectAllColumns() {
            // Clear existing selections
            this.form.columns = [];

            // Add all available columns
            this.availableColumns.forEach(col => {
                this.form.columns.push({
                    name: col,
                    alias: '' // No alias by default
                });
            });
            // If you want to automatically add join table columns too:
            this.form.joins.forEach(join => {
                join.availableColumns.forEach(col => {
                    const fullName = `${join.table}.${col}`;
                    if (!this.form.columns.some(c => c.name === fullName)) {
                        this.form.columns.push({
                            name: fullName,
                            alias: ''
                        });
                    }
                });
            });
        },

        addColumnToSelected() {
            if (!this.newColumn.name) return;

            const columnName = this.newColumn.name;
            const columnAlias = this.newColumn.alias;

            // Check if column already exists
            if (this.form.columns.some(col => col.name === columnName)) {
                this.form.setError('newColumn.name', 'This column is already selected');
                return;
            }

            // Add to columns array
            this.form.columns.push({
                name: columnName,
                alias: columnAlias
            });

            // Reset the new column form
            this.newColumn = { name: '', alias: '' };
        },

        removeColumn(index) {
            this.form.columns.splice(index, 1);
        },

        addWhereCondition() {
            this.form.where_conditions.push({
                column: '',
                operator: '==',
                value: ''
            });
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
                _prevTable: '' // Track previous table name
            });
        },

        removeJoin(index) {
            const join = this.form.joins[index];
            const table = join.table;
            if (table &&
                this.form.columns.some(c => c.name.startsWith(`${table}.`))) {
                if (!confirm('Removing this join table will remove any columns selected from it. Continue?')) {
                    return;
                }
            }
            const removedJoin = this.form.joins.splice(index, 1)[0];
            if (removedJoin?.table) {
                // Remove any columns from this join table in availableColumns
                this.availableColumns = this.availableColumns.filter(
                    col => !col.startsWith(`${removedJoin.table}.`)
                );

                // Also clean up any selected columns that might reference this table
                this.form.columns = this.form.columns.filter(
                    col => !col.name.startsWith(`${removedJoin.table}.`)
                );
            }
        },

        async fetchColumns() {
            if (!this.form.table_name) {
                this.availableColumns = [];
                return;
            }

            this.isLoadingColumns = true;
            try {
                const response = await axios.get(route('admin.lists.get-table-columns'), {
                    params: { table_name: this.form.table_name },
                });

                this.availableColumns = response.data.availableColumns.map(
                    col => `${this.form.table_name}.${col}`
                );

                // Reset columns when table changes
                this.form.columns = [];
            } catch (error) {
                console.error('Error fetching columns:', error);
                this.availableColumns = [];
            } finally {
                this.isLoadingColumns = false;
            }
        },

        async fetchJoinColumns(index) {
            const join = this.form.joins[index];
            const oldTable = join._prevTable; // Store the previous table name

            // Check if there are selected columns from this table
            if (oldTable && oldTable !== join.table &&
                this.form.columns.some(c => c.name.startsWith(`${oldTable}.`))) {
                if (!confirm('Changing this join table will remove any columns selected from it. Continue?')) {
                    join.table = oldTable; // Revert the change
                    return;
                }
            }

            join._prevTable = join.table; // Update the previous table reference

            // Clean up columns from the old table if it changed
            if (oldTable && oldTable !== join.table) {
                this.availableColumns = this.availableColumns.filter(
                    col => !col.startsWith(`${oldTable}.`)
                );
            }

            if (!join.table) {
                join.availableColumns = [];
                join.availableColumnsText = '';
                return;
            }

            try {
                const response = await axios.get(route('admin.lists.get-table-columns'), {
                    params: { table_name: join.table },
                });

                const columns = response.data.availableColumns;
                join.availableColumns = columns;
                join.availableColumnsText = columns.join(', ');

                // Add these columns to the main available columns list if not already present
                const newColumns = columns.map(col => `${join.table}.${col}`);
                this.availableColumns = [...new Set([...this.availableColumns, ...newColumns])];
            } catch (error) {
                console.error('Error fetching join columns:', error);
                join.availableColumns = [];
                join.availableColumnsText = '';
            }
        },

        resetForm() {
            if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
                this.form.reset();
                this.availableColumns = [];
                this.newColumn = { name: '', alias: '' };
            }
        },
        addWhereCondition() {
            this.form.where_conditions.push({
                column: '',
                operator: '=',
                value: ''
            });
            this.conditionErrors.push({});
        },
        removeWhereCondition(index) {
            this.form.where_conditions.splice(index, 1);
            this.conditionErrors.splice(index, 1);
        },
        validateConditions() {
            let isValid = true;
            this.conditionErrors = [];

            this.form.where_conditions.forEach((condition, index) => {
                this.conditionErrors[index] = {};

                if (!condition.column) {
                    this.conditionErrors[index].column = 'Column is required';
                    isValid = false;
                }

                if (!condition.operator) {
                    this.conditionErrors[index].operator = 'Operator is required';
                    isValid = false;
                }

                if (condition.value === undefined || condition.value === '') {
                    this.conditionErrors[index].value = 'Value is required';
                    isValid = false;
                }
            });

            return isValid;
        },
        submitForm(isTest) {
            this.isTesting = isTest;
            this.form.transform(data => ({
                ...data,
                is_test: isTest, // Add test flag to form data
            })).post(route('admin.lists.store'), {
                onSuccess: (response) => {
                    if (isTest) {
                        this.testResult = response.props.testResult;
                    } else {
                        // Handle actual save success
                        this.$toast.success('List saved successfully');
                    }
                },
                onError: (errors) => {
                    if (isTest && errors.response?.data?.testResult) {
                        this.testResult = errors.response.data.testResult;
                    }
                },
                onFinish: () => {
                    this.isTesting = false;
                }
            });
        },
    },
};
</script>
<style scoped>
/* In your CSS file */
select optgroup {
    font-weight: bold;
    font-style: normal;
    color: #4b5563;
    /* gray-600 */
    background-color: #f3f4f6;
    /* gray-100 */
}

.disabled-select-all {
    opacity: 0.5;
    cursor: not-allowed;
}

select option {
    padding-left: 1rem;
}

/* Style for the Autocomplete input to match TextInput */
.p-autocomplete-input-wrapper {
    @apply rounded-md shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 w-full bg-gray-100 cursor-default;
    /* Added background and cursor for readonly */
}

.p-autocomplete-input {
    @apply block w-full py-2 px-3 border-none focus:outline-none focus:ring-0 text-gray-700 bg-transparent cursor-default;
    /* Added background and cursor for readonly */
}

.p-autocomplete-multiple-container {
    @apply rounded-md shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 w-full flex items-center flex-wrap p-1 bg-gray-100 cursor-default;
    /* Added cursor for readonly */
}

.p-autocomplete-token {
    @apply inline-flex items-center bg-gray-200 text-gray-700 rounded-full px-2 py-1 mr-2;
}

.p-autocomplete-token-label {
    @apply mr-1;
}

.p-autocomplete-panel {
    @apply list-none m-0 p-0 shadow-md rounded-md border border-gray-200 bg-white absolute z-10;
}

.p-autocomplete-item {
    @apply p-2 cursor-pointer hover:bg-gray-100;
}

.p-autocomplete-empty-message {
    @apply p-2 text-gray-500;
}
</style>
