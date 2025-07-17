<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import LinkButton from "@/Components/LinkButton.vue";
import MenuDropdown from "@/Components/MenuDropdown.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default {
  components: {
    AuthenticatedLayout,
    Head,
    LinkButton,
    MenuDropdown,
    DangerButton,
    Modal,
    PrimaryButton
  },
  props: {
    form: Object,
  },
  data() {
    return {
      destroyModal: false,
      selectedRow: null,
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
        // Close all dropdowns when clicking outside
        this.$refs.menuDropdown.closeAllDropdowns();
      }
    });

    $(document).on("click", ".edit", (evt) => {
      const data = $(evt.currentTarget).data("id");

      router.get(route("admin.form_responses.edit", data));
    });

    $(document).on("click", ".view", (evt) => {
      const data = $(evt.currentTarget).data("id");

      router.get(route("admin.form_responses.show", data));
    });

    $(document).on("click", ".responses", (evt) => {
      const data = $(evt.currentTarget).data("id");

      router.get(route("admin.form.show", data));
    });

    $(document).on("click", ".delete", (evt) => {
      const data = $(evt.currentTarget).data("id");

      this.showDestroyModal(data);
    });
  },
  methods: {
    fetch() {
      $("#data_table").DataTable({
        destroy: true,
        stateSave: false,
        processing: false,
        serverSide: true,
        ajax: {
          url: route("admin.form_responses.fetch"),
          data: {
            uuid: this.form.uuid,
          },
        },
        columns: [
          { data: "title", name: "title" },
          { data: "date", name: "date" },
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
        columnDefs: [{ width: "5%", targets: -1 }],
        createdRow: function (row, data, dataIndex) {
          // Find the dropdown element in the row and set its width
          var dropdownMenu = $(row).find(".dropdown-menu");
          if (dropdownMenu.length > 0) {
            dropdownMenu.width(160); // Set your desired width here
          }
        },
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
    destroy() {
      axios
        .post(route("admin.form_responses.destroy", { response: this.selectedRow }))
        .then((response) => [
          this.hideDestroyModal(),
          toastr.success("Response successfully deleted"),
          this.fetch(),
        ])
        .catch((error) => console.log(error));
    },
    exportData(){
      window.open(route("admin.form.export", { form: this.form.uuid }));
    }
  },
};
</script>
<template>
  <Head title="Forms | Form Responses" />

  <AuthenticatedLayout>
    <MenuDropdown ref="menuDropdown" />
    <template #header>
      <div class="flex items-center">
        Forms
        <span class="material-symbols-outlined text-gray-400">
          keyboard_arrow_right
        </span>
        Form Responses
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
          <div class="flex justify-end">
          <PrimaryButton @click="exportData">
            <span class="material-symbols-outlined mr-2"> file_export </span>
            export data
          </PrimaryButton>
          </div>
            <div class="flex flex-col mt-4">
              <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 inline-w-full sm:px-6 lg:px-8">
                  <div class="overflow-x-auto">
                    <table id="data_table" class="w-full text-sm table-striped">
                      <thead class="capitalize border-b bg-gray-100 font-medium">
                        <tr>
                          <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                            Title
                          </th>
                          <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                            submitted at
                          </th>
                          <th scope="col" class="text-gray-900 px-6 py-4 text-left">
                            action
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

    <!-- destroy modal  -->
    <Modal
      :show="destroyModal"
      :closeable="true"
      :modalTitle="'delete form response'"
      @close="hideDestroyModal"
      :maxWidth="'md'"
    >
      <div class="flex justify-center mt-4">
        <p class="text-lg">Are you sure you want to delete this response?</p>
      </div>

      <div class="flex justify-center mt-6 gap-4">
        <DangerButton @click="destroy" type="submit"> Yes </DangerButton>

        <button
          @click="hideDestroyModal"
          type="button"
          class="block items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:cursor-not-allowed"
        >
          Cancel
        </button>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
