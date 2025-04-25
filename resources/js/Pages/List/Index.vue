<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import LinkButton from "@/Components/LinkButton.vue";
import MenuDropdown from "@/Components/MenuDropdown.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import SimpleMDE from '@/Components/SimpleMDE.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        LinkButton,
        MenuDropdown,
        DangerButton,
        Modal,
        SecondaryButton,
        PrimaryButton,
        TextInput,
        InputLabel,
        TextAreaInput,
        SimpleMDE,
    },
    data() {
        return {
            destroyModal: false,
            smsModal: false,
            emailModal: false,
            selectedRow: null,
            smsMessage: '',
            emailSubject: '',
            emailMessage: '',
            viewModal: false,
            listData: [],
            listColumns: [],
            currentPage: 1,
            lastPage: 1,
            perPage: 10,
            totalRecords: 0,
            loading: false,
            templateVariables: [],
            loadingVariables: false,
            mdeOptions: {
                toolbar: [
                    'bold', 'italic', 'heading-smaller', 'heading-bigger', '|',
                    'quote', 'unordered-list', 'ordered-list', '|',
                    'link', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ],
                minHeight: '200px',
                spellChecker: false,
                status: false
            }
        };
    },
    mounted() {
        this.fetch();

        $(document).on("click", ".dropdown-toggle", (evt) => {
            const data = $(evt.target).attr("dropdown-log");

            if (this.$refs.menuDropdown && evt.target.classList.contains("dropdown-span")) {
                this.$refs.menuDropdown.toggleDropdown(data);
            }
        });

        $(document).on("click", "body", (evt) => {
            if (this.$refs.menuDropdown && !evt.target.classList.contains("dropdown-span")) {
                this.$refs.menuDropdown.closeAllDropdowns();
            }
        });

        $(document).on("click", ".view", (evt) => {
            const data = $(evt.currentTarget).data("id");
            this.showViewModal(data);
            // router.get(route("admin.lists.show", data));
        });

        $(document).on("click", ".delete", (evt) => {
            const data = $(evt.currentTarget).data("id");
            this.showDestroyModal(data);
        });

        $(document).on("click", ".sms", (evt) => {
            const data = $(evt.currentTarget).data("id");
            this.showSmsModal(data);
        });

        $(document).on("click", ".email", (evt) => {
            const data = $(evt.currentTarget).data("id");
            this.showEmailModal(data);
        });
    },
    methods: {
        async fetchTemplateVariables(tableName) {
            this.loadingVariables = true;
            try {
                const response = await axios.get(route('admin.lists.get-table-columns'), {
                    params: { table_name: tableName }
                });

                this.templateVariables = response.data.availableColumns.map(c => `{${c.name}}`);
                console.log('Template Variables:', this.templateVariables);

            } catch (error) {
                console.error('Error loading template variables:', error);
                this.templateVariables = [];
            } finally {
                this.loadingVariables = false;
            }
        },
        async loadPage(page) {
            this.currentPage = page;
            await this.showViewModal(this.selectedRow);
        },
        fetch() {
            $("#data_table").DataTable({
                destroy: true,
                stateSave: false,
                processing: false,
                serverSide: true,
                ajax: {
                    url: route("admin.lists.fetch"),
                },
                columns: [
                    { data: "name", name: "View Name" },
                    {
                        data: "count",
                        name: "Record Count",
                        render: function (data) {
                            return data.toLocaleString();
                        }
                    },
                    {
                        data: "action",
                        name: "action",
                        orderable: false,
                        searchable: false,
                    },
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    ["10", "25", "50", "100", "All"],
                ],
                order: [[1, "desc"]],
                columnDefs: [{ width: "15%", targets: -1 }],
                createdRow: function (row, data, dataIndex) {
                    $(row).attr("data-id", data.uuid);
                    var dropdownMenu = $(row).find(".dropdown-menu");
                    if (dropdownMenu.length > 0) {
                        dropdownMenu.width(200);
                    }
                },
            });

            $("#data_table").on("click", "tbody tr > td:not(:last-child)", function (evt) {
                const data = $(evt.currentTarget).parent().data("id");
                router.get(route("admin.lists.show", data));
            });
        },
        showDestroyModal(data) {
            this.selectedRow = data;
            this.destroyModal = true;
        },
        hideDestroyModal() {
            this.destroyModal = false;
            this.selectedRow = null;
        },
        showSmsModal(data) {
            this.selectedRow = data;
            this.smsMessage = '';
            this.smsModal = true;
            this.fetchTemplateVariables(data);
        },
        hideSmsModal() {
            this.smsModal = false;
        },
        showEmailModal(data) {
            this.selectedRow = data;
            this.emailSubject = '';
            this.emailMessage = '';
            this.emailModal = true;
            this.fetchTemplateVariables(data);

        },
        hideEmailModal() {
            this.emailModal = false;
        },
        destroy() {
            axios
                .delete(route("admin.lists.destroy", { list: this.selectedRow }))
                .then((response) => {
                    this.hideDestroyModal();
                    toastr.success("List successfully deleted");
                    this.fetch();
                })
                .catch((error) => console.log(error));
        },
        sendSms() {
            axios
                .post(route("admin.send_bulk_sms"), {
                    message: this.smsMessage, list: this.selectedRow
                })
                .then((response) => {
                    this.hideSmsModal();
                    toastr.success("SMS queued for sending");
                })
                .catch((error) => console.log(error));
        },
        sendEmail() {
            axios
                .post(route("admin.send_bulk_email"), {
                    message: this.emailMessage, list: this.selectedRow, subject: this.emailSubject
                })
                .then((response) => {
                    this.hideEmailModal();
                    toastr.success("Email queued for sending");
                })
                .catch((error) => console.log(error));
        },
        async showViewModal(listId) {
            this.selectedRow = listId;
            this.loading = true;
            this.viewModal = true;

            try {
                const response = await axios.get(route('admin.lists.view-data', {
                    list: listId,
                    page: this.currentPage,
                    per_page: this.perPage
                }));

                this.listData = response.data.data;
                this.listColumns = response.data.columns;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                this.totalRecords = response.data.total;
            } catch (error) {
                console.error('Error loading list data:', error);
            } finally {
                this.loading = false;
            }
        },
        hideViewModal() {
            this.viewModal = false;
            this.selectedRow = null;
            this.listData = [];
            this.listColumns = [];
            this.currentPage = 1;
            this.lastPage = 1;
            this.totalRecords = 0;
        },
        insertVariable(variable, field) {
            if (field === 'emailMessage') {
                // Get the SimpleMDE instance and insert at cursor
                const mdeElement = document.querySelector('.CodeMirror');
                if (mdeElement && mdeElement.CodeMirror) {
                    const cm = mdeElement.CodeMirror;
                    const doc = cm.getDoc();
                    const cursor = doc.getCursor();
                    doc.replaceRange(variable, cursor);
                    cm.focus();
                }
            } else {
                // For other fields (like SMS)
                this[field] += variable;
            }
        },
        hideEmailModal() {
            this.emailModal = false;
            // Reset the editor content when modal closes
            this.emailMessage = '';
        }
    },
};
</script>

<template>

    <Head title="Lists" />

    <AuthenticatedLayout>
        <MenuDropdown ref="menuDropdown" />
        <template #header>
            <div class="flex items-center">Manage Lists</div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end">
                            <LinkButton :href="route('admin.lists.create')">
                                <span class="material-symbols-outlined mr-2"> add </span>
                                create list
                            </LinkButton>
                        </div>

                        <div class="flex flex-col mt-4">
                            <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 inline-w-full sm:px-6 lg:px-8">
                                    <div class="overflow-x-auto">
                                        <table id="data_table" class="w-full text-sm table-striped">
                                            <thead class="capitalize border-b bg-gray-100 font-medium">
                                                <tr>
                                                    <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                                                        View Name
                                                    </th>
                                                    <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                                                        Record Count
                                                    </th>

                                                    <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                                                        Action
                                                    </th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Destroy Modal -->
        <Modal :show="destroyModal" :closeable="true" :modalTitle="'Delete List'" @close="hideDestroyModal"
            :maxWidth="'md'">
            <div class="flex justify-center mt-4">
                <p class="text-lg">Are you sure you want to delete this list?</p>
            </div>

            <div class="flex justify-center mt-6 gap-4">
                <DangerButton @click="destroy" type="submit"> Yes </DangerButton>

                <button @click="hideDestroyModal" type="button"
                    class="block items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:cursor-not-allowed">
                    Cancel
                </button>
            </div>
        </Modal>

        <!-- SMS Modal -->
        <Modal :show="smsModal" :closeable="true" :modalTitle="'Send SMS to List'" @close="hideSmsModal"
            :maxWidth="'md'">
            <div class="mt-4">
                <p class="text-sm text-gray-600 mb-4">You are about to send an SMS to this list.</p>
                <div class="mb-3 p-3 bg-gray-50 rounded">
                    <p class="text-sm font-medium text-gray-700 mb-1">Available variables:</p>
                    <div v-if="loadingVariables" class="text-sm text-gray-500">
                        Loading variables...
                    </div>
                    <div v-else class="flex flex-wrap gap-1">
                        <span v-for="(item, index) in templateVariables" :key="index"
                            class="px-2 py-1 bg-white border border-gray-200 rounded text-xs cursor-pointer hover:bg-gray-100"
                            @click="insertVariable(item, 'smsMessage')" :title="'Insert ' + item">
                            {{ item }}
                        </span>
                        <span v-if="templateVariables.length === 0" class="text-sm text-gray-500">
                            No variables available
                        </span>
                    </div>
                </div>
                <div class="mb-4">
                    <InputLabel for="smsMessage" value="Message" />
                    <TextAreaInput id="smsMessage" v-model="smsMessage" rows="4" class="mt-1 block w-full" />
                    <div class="text-xs text-gray-500 mt-1">{{ smsMessage.length }}/160 characters</div>
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="hideSmsModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="sendSms" :disabled="smsMessage.length === 0">
                        Send SMS
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Email Modal -->
        <Modal :show="emailModal" :closeable="true" :modalTitle="'Send Email to List'" @close="hideEmailModal"
            :maxWidth="'md'">
            <div class="mt-4">
                <p class="text-sm text-gray-600 mb-4">You are about to send an email to this list.</p>

                <div class="mb-4">
                    <InputLabel for="emailSubject" value="Subject" />
                    <TextInput id="emailSubject" v-model="emailSubject" class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <InputLabel for="emailMessage" value="Message" />
                    <SimpleMDE id="emailMessage" v-model="emailMessage" class="mt-1 block w-full"
                        :options="mdeOptions" />
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="hideEmailModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="sendEmail"
                        :disabled="emailSubject.length === 0 || emailMessage.length === 0">
                        Send Email
                    </PrimaryButton>
                </div>
                <div class="mb-3 p-3 bg-gray-50 rounded">
                    <p class="text-sm font-medium text-gray-700 mb-1">Available variables:</p>
                    <div v-if="loadingVariables" class="text-sm text-gray-500">
                        Loading variables...
                    </div>
                    <div v-else class="flex flex-wrap gap-1">
                        <span v-for="(item, index) in templateVariables" :key="index"
                            class="px-2 py-1 bg-white border border-gray-200 rounded text-xs cursor-pointer hover:bg-gray-100"
                            @click="insertVariable(item, 'emailMessage')" :title="'Insert ' + item">
                            {{ item }}
                        </span>
                        <span v-if="templateVariables.length === 0" class="text-sm text-gray-500">
                            No variables available
                        </span>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- View Data Modal  -->
        <Modal :show="viewModal" :closeable="true" :modalTitle="'List Data'" @close="hideViewModal" :maxWidth="'6xl'">
            <div class="mt-4">
                <div v-if="loading" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>

                <div v-else>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th v-for="column in listColumns" :key="column.name" scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ column.alias || column.name.split('.').pop() }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in listData" :key="index">
                                    <td v-for="column in listColumns" :key="column.name"
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ row[column.name] }}
                                    </td>
                                </tr>
                                <tr v-if="listData?.length === 0">
                                    <td :colspan="listColumns.length"
                                        class="px-6 py-4 text-center text-sm text-gray-500">
                                        No records found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="flex items-center justify-between mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span>
                                    to
                                    <span class="font-medium">{{ Math.min(currentPage * perPage, totalRecords) }}</span>
                                    of
                                    <span class="font-medium">{{ totalRecords }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                    aria-label="Pagination">
                                    <button @click="loadPage(currentPage - 1)" :disabled="currentPage === 1"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="sr-only">Previous</span>
                                        <span class="material-symbols-outlined">chevron_left</span>
                                    </button>

                                    <template v-for="page in lastPage" :key="page">
                                        <button
                                            v-if="Math.abs(page - currentPage) <= 2 || page === 1 || page === lastPage"
                                            @click="loadPage(page)" :class="{
                                                'bg-blue-50 border-blue-500 text-blue-600': page === currentPage,
                                                'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !== currentPage
                                            }"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            {{ page }}
                                        </button>
                                        <span v-else-if="Math.abs(page - currentPage) === 3"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                    </template>

                                    <button @click="loadPage(currentPage + 1)" :disabled="currentPage === lastPage"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="sr-only">Next</span>
                                        <span class="material-symbols-outlined">chevron_right</span>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
        <!-- End View Data Modal -->

    </AuthenticatedLayout>
</template>

<style>
#data_table tbody tr>td:not(:last-child) {
    cursor: pointer;
}

.dropdown-menu {
    min-width: 200px;
}

.dropdown-item {
    padding: 0.25rem 1rem;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: #f3f4f6;
}
</style>
