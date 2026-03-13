@once
    @push('crud_fields_scripts')
        <script>
            (function () {
                function toggleDistrictField() {
                    var branchField = document.querySelector('select[name="branch_id"]');
                    var districtField = document.querySelector('select[name="district_id"]');

                    if (!branchField || !districtField) {
                        return;
                    }

                    var hasBranch = branchField.value !== null && branchField.value !== '';
                    var districtFieldJq = window.jQuery ? window.jQuery(districtField) : null;

                    if (hasBranch) {
                        districtField.disabled = false;
                        districtField.removeAttribute('disabled');
                        if (districtFieldJq) {
                            districtFieldJq.prop('disabled', false).trigger('change.select2');
                        }
                        return;
                    }

                    districtField.disabled = true;
                    districtField.setAttribute('disabled', 'disabled');

                    if (districtFieldJq) {
                        districtFieldJq
                            .prop('disabled', true)
                            .val(null)
                            .trigger('change')
                            .trigger('change.select2');
                    } else {
                        districtField.value = '';
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var branchField = document.querySelector('select[name="branch_id"]');
                    if (!branchField) {
                        return;
                    }

                    branchField.addEventListener('change', toggleDistrictField);
                    toggleDistrictField();
                });
            })();
        </script>
    @endpush
@endonce
