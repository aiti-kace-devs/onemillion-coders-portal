<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Global Actions</h4>
                    <p class="text-muted mb-0">Process all pending enrollments across all platforms.</p>
                </div>
                <div>
                    <form action="{{ url(config('backpack.base.route_prefix') . '/partner-admissions/enrol-all') }}" method="POST" id="globalEnrolForm">
                        @csrf
                        @php $awaiting = $widget['data']['totalAwaiting'] ?? 0; @endphp
                        <button type="button" class="btn btn-primary btn-lg" onclick="confirmGlobalEnrol()">
                            <i class="la la-rocket"></i> Enrol ALL Awaiting Students ({{ $awaiting }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmGlobalEnrol() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will attempt to enrol ALL awaiting students across all partner programmes.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, enrol all!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('globalEnrolForm').submit();
            }
        })
    }
</script>
@endpush
