@php
    $batch = $entry;
    $regenerateUrl = url('admin/batch/' . $batch->id . '/regenerate-batches');
@endphp

<div class="mb-3">
    <button type="button"
            id="btn-regenerate-programme-batches"
            class="btn btn-warning"
            data-url="{{ $regenerateUrl }}"
            data-token="{{ csrf_token() }}">
        <i class="la la-refresh"></i> Regenerate Programme Batches
    </button>
</div>

@bassetBlock('onemillion-regenerate-batches-css')
<style>
    .swal-button--confirm {
        background-color: #ffc107 !important; /* Bootstrap Warning Orange */
        color: #212529 !important;
        box-shadow: none !important;
    }
    .swal-button--confirm:hover {
        background-color: #e0a800 !important; /* Darker Orange on hover */
    }
    .swal-button--cancel {
        background-color: #6c757d !important; /* Bootstrap Secondary Grey */
        color: #fff !important;
    }
    .swal-button--cancel:hover {
        background-color: #5a6268 !important;
    }
</style>
@endBassetBlock

@bassetBlock('onemillion-regenerate-batches-js')
<script>
    (function() {
        const btn = document.getElementById("btn-regenerate-programme-batches");
        if (!btn || btn.dataset.handled) return;
        btn.dataset.handled = "true";

        btn.addEventListener("click", function() {
            const url = this.dataset.url;
            const token = this.dataset.token;
            const title = "Regenerate Programme Batches?";
            const message = "This will update dates for existing batches, create new ones where needed, and remove orphaned batches that have no assigned students.";

            const triggerAction = () => {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = url;
                const csrfToken = document.createElement("input");
                csrfToken.type = "hidden";
                csrfToken.name = "_token";
                csrfToken.value = token;
                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            };

            if (window.swal) {
                swal({
                    title: title,
                    text: message,
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "Cancel",
                            visible: true,
                            className: "btn btn-secondary",
                            closeModal: true,
                        },
                        confirm: {
                            text: "Yes, regenerate!",
                            visible: true,
                            className: "btn btn-warning",
                            closeModal: true,
                        }
                    },
                }).then((willRegenerate) => {
                    if (willRegenerate) triggerAction();
                });
            } else if (confirm(message)) {
                triggerAction();
            }
        });
    })();
</script>
@endBassetBlock
