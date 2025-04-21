<!-- <script>
     import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
    import {
        Head,
        useForm
    } from "@inertiajs/vue3";

     export default {
         components: {

         },

     };
</script> -->

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import LinkButton from "@/Components/LinkButton.vue";
import MenuDropdown from "@/Components/MenuDropdown.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import { Link } from '@inertiajs/vue3';


export default {
  components: {
    AuthenticatedLayout,
    Head,
    LinkButton,
    MenuDropdown,
    DangerButton,
    Modal,
    Link,
  },
   props: {
             views: Array,
         },
         methods: {
              destroyView(viewName) {
                 if (confirm('Are you sure you want to delete this view?')) {
                     useForm({ viewName }).delete(route('lists.destroy', { viewName }));
                 }
             },
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

      router.get(route("admin.form.edit", data));
    });

    $(document).on("click", ".preview", (evt) => {
      const data = $(evt.currentTarget).data("id");

      router.get(route("admin.form.preview", data));
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
          url: route("admin.form.fetch"),
        },
        columns: [
          { data: "title", name: "title" },
          { data: "active", name: "active" },
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
          $(row).attr("data-id", data.uuid);
          var dropdownMenu = $(row).find(".dropdown-menu");
          if (dropdownMenu.length > 0) {
            dropdownMenu.width(160); // Set your desired width here
          }
        },
      });

      $('#data_table').on('click', 'tbody tr > td:not(:last-child)', function (evt) {
        const data = $(evt.currentTarget).parent().data("id");
        console.log(data);


        router.get(route("admin.form.edit", data));
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
        .post(route("admin.form.destroy", { form: this.selectedRow }))
        .then((response) => [
          this.hideDestroyModal(),
          toastr.success("Form successfully deleted"),
          this.fetch(),
        ])
        .catch((error) => console.log(error));
    },
  },
};
</script>
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

            <div class="flex flex-col mt-4">
              <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 inline-w-full sm:px-6 lg:px-8">
                   <div>
        <h1>Available Lists (Views)</h1>
        <ul>
            <li v-for="view in views" :key="view.Name">
                <Link :href="route('admin.lists.show', { viewName: view.Name })">{{ view.Name }}</Link>
                <form @submit.prevent="destroyView(view.Name)" style="display: inline-block;">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                        Delete
                    </button>
                </form>
            </li>
        </ul>

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
      :modalTitle="'delete form'"
      @close="hideDestroyModal"
      :maxWidth="'md'"
    >
      <div class="flex justify-center mt-4">
        <p class="text-lg">Are you sure you want to delete this form?</p>
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

<style>
#data_table tbody tr > td:not(:last-child) {
    cursor: pointer;
}
</style>
