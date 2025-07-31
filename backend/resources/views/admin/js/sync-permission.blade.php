@push('crud_fields_styles')
    @bassetBlock('custom/sync-permission.js')
        <script>
            function initRolePermissionSync() {
                console.log("Initializing role-permission sync...");

                // Try multiple possible selectors for Backpack Select2 fields
                const possibleRoleSelectors = [
                    "select[name=\'roles[]\']",
                    "[data-field-name=\'roles\'] select",
                    "#roles",
                    ".roles select",
                    "select.select2-hidden-accessible[name*=\'roles\']"
                ];

                const possiblePermissionSelectors = [
                    "select[name=\'permissions[]\']",
                    "[data-field-name=\'permissions\'] select",
                    "#permissions",
                    ".permissions select",
                    "select.select2-hidden-accessible[name*=\'permissions\']"
                ];

                let roleField = null;
                let permissionField = null;

                // Find the role field
                for (let selector of possibleRoleSelectors) {
                    roleField = document.querySelector(selector);
                    if (roleField) {
                        // console.log("Found role field with selector:", selector);
                        break;
                    }
                }

                // Find the permission field
                for (let selector of possiblePermissionSelectors) {
                    permissionField = document.querySelector(selector);
                    if (permissionField) {
                        // console.log("Found permission field with selector:", selector);
                        break;
                    }
                }

                if (!roleField) {
                    console.error("Role field not found. Available selects:", document.querySelectorAll("select"));
                    return;
                }

                if (!permissionField) {
                    console.error("Permission field not found. Available selects:", document.querySelectorAll("select"));
                    return;
                }

                // console.log("Both fields found successfully");

                // Function to handle role changes
                function handleRoleChange() {
                    // console.log("Role change detected");

                    let selectedRoleIds = [];

                    // Get selected values (works for both regular select and Select2)
                    if ($(roleField).hasClass("select2-hidden-accessible")) {
                        selectedRoleIds = $(roleField).val() || [];
                    } else {
                        selectedRoleIds = Array.from(roleField.selectedOptions).map(opt => opt.value);
                    }

                    // console.log("Selected role IDs:", selectedRoleIds);

                    if (selectedRoleIds.length === 0) {
                        // console.log("No roles selected, clearing permissions");
                        $(permissionField).val(null).trigger("change");
                        return;
                    }

                    // Build URL
                    const queryParams = selectedRoleIds.map(id => "role_ids[]=" + encodeURIComponent(id)).join("&");
                    const url = "{{ url('admin/roles/permissions') }}" + "?" + queryParams;


                    // console.log("Fetching permissions from:", url);

                    fetch(url)
                        .then(response => {
                            // console.log("Response status:", response.status);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // console.log("Received permission data:", data);

                            // Set the permissions using Select2 method
                            $(permissionField).val(data).trigger("change");

                            // console.log("Permissions updated successfully");
                        })
                        .catch(error => {
                            console.error("Error fetching permissions:", error);
                        });
                }

                // Attach event listeners for both regular change and Select2 change
                $(roleField).on("change", handleRoleChange);
                $(roleField).on("select2:select select2:unselect", handleRoleChange);

                // console.log("Event listeners attached");
            }

            // Try to initialize immediately
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initRolePermissionSync);
            } else {
                initRolePermissionSync();
            }

            // Also try after a short delay to account for Backpack/Select2 initialization
            setTimeout(initRolePermissionSync, 1000);
            setTimeout(initRolePermissionSync, 3000);
        </script>
    @endBassetBlock
@endpush
