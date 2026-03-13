@once
    @push('crud_fields_scripts')
        <script>
            (function () {
                function toggleConstituencyField() {
                    var branchField = document.querySelector('select[name="branch_id"]');
                    var constituencyField = document.querySelector('select[name="constituency_id"]');

                    if (!branchField || !constituencyField) {
                        return;
                    }

                    var hasBranch = branchField.value !== null && branchField.value !== '';
                    var constituencyFieldJq = window.jQuery ? window.jQuery(constituencyField) : null;

                    if (hasBranch) {
                        constituencyField.disabled = false;
                        constituencyField.removeAttribute('disabled');
                        if (constituencyFieldJq) {
                            constituencyFieldJq.prop('disabled', false).trigger('change.select2');
                        }
                        return;
                    }

                    constituencyField.disabled = true;
                    constituencyField.setAttribute('disabled', 'disabled');

                    if (constituencyFieldJq) {
                        constituencyFieldJq
                            .prop('disabled', true)
                            .val(null)
                            .trigger('change')
                            .trigger('change.select2');
                    } else {
                        constituencyField.value = '';
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var branchField = document.querySelector('select[name="branch_id"]');
                    if (!branchField) {
                        return;
                    }

                    branchField.addEventListener('change', toggleConstituencyField);
                    toggleConstituencyField();
                });
            })();
        </script>
    @endpush
@endonce
