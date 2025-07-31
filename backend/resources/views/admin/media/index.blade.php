@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      'Media' => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
<section class="container-fluid">
    <h2>
        <span class="text-capitalize">Media Management</span>
    </h2>
</section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="card-title">Media List</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="{{ url($crud->route.'/create') }}" class="btn btn-primary">
                            <i class="la la-plus"></i> Add New Media
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger">
                        <i class="la la-exclamation-triangle"></i> {{ $error }}
                    </div>
               @elseif(empty($mediaData))
                    <div class="alert alert-info">
                        <i class="la la-info-circle"></i> No media found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Storage</th>
                                    <th>Reusable</th>
                                    <th>Created</th>
                                    <th>File</th>
                                </tr>
                            </thead>
                            <tbody>
                              @foreach($mediaData as $media)
                                <tr>
                                    <td>{{ $media['title'] }}</td>
                                    <td>{{ Str::limit($media['description'], 50) }}</td>
                                    <td>
                                        @php
                                            $type = ucfirst($media['media_type']);
                                            if ($type === 'Document') {
                                                $badgeClass = 'bg-success';
                                            } elseif ($type === 'Image') {
                                                $badgeClass = 'bg-primary';
                                            } else {
                                                $badgeClass = 'bg-secondary';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $type }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $storage_backend = ucfirst($media['storage_backend']);
                                            if ($storage_backend === 'GCP') {
                                                $badgeClass = 'bg-warning';
                                            } elseif ($storage_backend === 'AWS') {
                                                $badgeClass = 'bg-primary';
                                            } else {
                                                $badgeClass = 'bg-secondary';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $storage_backend }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($media['is_reusable'])
                                            <span class="badge badge-success">True</span>
                                        @else
                                            <span class="badge badge-warning">False</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($media['created_on'])->format('M d, Y') }}</td>
                                    <td>
                                        @if(!empty($media['preview_url']) && $media['preview_url'] !== '#')
                                            <a href="{{ $media['preview_url'] }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="la la-external-link"></i> View
                                            </a>
                                        @else
                                            <span class="text-muted">No file</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination Section --}}
                    @if($pagination && $pagination['total'] > 0)
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="text-muted">
                                @php
                                    $start = ($pagination['current_page'] - 1) * $pagination['page_size'] + 1;
                                    $end = min($pagination['current_page'] * $pagination['page_size'], $pagination['total']);
                                @endphp
                                Showing {{ $start }} to {{ $end }} of {{ $pagination['total'] }} results
                                <small class="text-info d-block">
                                    [Page:  {{ $pagination['current_page'] }} of {{ $pagination['last_page'] }}, Per page: {{ $pagination['page_size'] }}]
                                </small>
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if($pagination['last_page'] > 1)
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-end">
                                    {{-- Previous Page Link --}}
                                    @if($pagination['current_page'] > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ url($crud->route) }}?page={{ $pagination['current_page'] - 1 }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}{{ request('page_size') ? '&page_size='.request('page_size') : '' }}">
                                                Previous
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    @endif

                                    {{-- First page if not in range --}}
                                    @if($pagination['current_page'] > 3)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ url($crud->route) }}?page=1{{ request('search') ? '&search='.urlencode(request('search')) : '' }}{{ request('page_size') ? '&page_size='.request('page_size') : '' }}">1</a>
                                        </li>
                                        @if($pagination['current_page'] > 4)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    {{-- Page Numbers --}}
                                    @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++)
                                        <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                            @if($i == $pagination['current_page'])
                                                <span class="page-link">{{ $i }}</span>
                                            @else
                                                <a class="page-link" href="{{ url($crud->route) }}?page={{ $i }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}{{ request('page_size') ? '&page_size='.request('page_size') : '' }}">
                                                    {{ $i }}
                                                </a>
                                            @endif
                                        </li>
                                    @endfor

                                    {{-- Last page if not in range --}}
                                    @if($pagination['current_page'] < $pagination['last_page'] - 2)
                                        @if($pagination['current_page'] < $pagination['last_page'] - 3)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ url($crud->route) }}?page={{ $pagination['last_page'] }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}{{ request('page_size') ? '&page_size='.request('page_size') : '' }}">{{ $pagination['last_page'] }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if($pagination['current_page'] < $pagination['last_page'])
                                        <li class="page-item">
                                            <a class="page-link" href="{{ url($crud->route) }}?page={{ $pagination['current_page'] + 1 }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}{{ request('page_size') ? '&page_size='.request('page_size') : '' }}">
                                                Next
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">Next</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                            @endif
                        </div>
                    </div>
                    @elseif($pagination && $pagination['total'] == 0)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <p class="text-muted">No results found.</p>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mediaDetailsModal" tabindex="-1" role="dialog"
     aria-labelledby="mediaDetailsModalLabel" aria-hidden="true"
     data-backdrop="true" data-keyboard="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Media Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="mediaDetailsContent">
        <!-- Populated via JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('after_scripts')
<script>
function showMediaDetails(media) {
    let content = `
        <div class="row">
            <div class="col-md-6">
                <strong>ID:</strong> ${media.id || 'N/A'}<br>
                <strong>Title:</strong> ${media.title || 'N/A'}<br>
                <strong>Type:</strong> ${media.media_type || 'N/A'}<br>
                <strong>Storage:</strong> ${media.storage_backend || 'N/A'}<br>
                <strong>Reusable:</strong> ${media.is_reusable ? 'True' : 'False'}<br>
                <strong>Created At:</strong> ${new Date(media.created_on).toLocaleDateString() || 'N/A'}
            </div>
            <div class="col-md-6">
                <strong>Description:</strong><br>
                <p>${media.meta_description || 'No description'}</p>
                <strong>File:</strong><br>
                ${media.file_url && media.file_url !== '#' ? 
                    `<a href="${media.file_url}" target="_blank" class="btn btn-primary btn-sm">
                        <i class="la la-external-link"></i> Open File
                    </a>` : 
                    '<span class="text-muted">No file available</span>'
                }
            </div>
        </div>
    `;
    
    document.getElementById('mediaDetailsContent').innerHTML = content;
    $('#mediaDetailsModal').modal('show');
}
</script>
@endpush