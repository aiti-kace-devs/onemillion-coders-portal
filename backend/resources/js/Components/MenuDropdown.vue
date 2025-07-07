<script>
export default {
    data() {
        return {
            openDropdowns: [],
        }
    },
    methods: {
        toggleDropdown(id) {
            if (this.openDropdowns.includes(id)) {
                this.closeDropdown(id);
            } else {
                this.openDropdown(id);
            }
        },
        openDropdown(id) {
            // Close all open dropdowns
            this.closeAllDropdowns();

            // Open the selected dropdown
            this.openDropdowns.push(id);

            // Add logic to display the dropdown (e.g., remove 'hidden' class)
            document.getElementById('dropdown-menu-' + id).classList.remove('hidden');
        },
        closeDropdown(id) {
            const index = this.openDropdowns.indexOf(id);
            if (index !== -1) {
                this.openDropdowns.splice(index, 1);
            }

            // Add logic to display the dropdown (e.g., add 'hidden' class)
            document.getElementById('dropdown-menu-' + id).classList.add('hidden');
        },
        closeAllDropdowns() {
            this.openDropdowns.forEach(id => {
                this.closeDropdown(id)
            });
        },
    }
}
</script>
<template />