@extends(backpack_view('layouts.top_left'))


    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-actions">
                        <a class="btn btn-info mr-2" href="javascript:;" data-toggle="modal"
                            data-target="#shortlisted_students">Choose Shortlist</a>
                        <button class="btn btn-warning mr-2" data-toggle="modal"
                            data-target="#bulk-email-modal">Send Emails
                            <i class="fas fa-envelope"></i>
                        </button>
                        <button class="btn btn-success mr-2" data-toggle="modal"
                            data-target="#bulk-sms-modal">Send SMS
                            <i class="fas fa-sms"></i>
                        </button>
                        <button class="btn btn-primary mr-2" id="admit-selected">Admit Students</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="20px"><input type="checkbox" id="select-all"></th>
                                    <th>Name (Email)</th>
                                    <th>Admitted</th>
                                    <th>Shortlisted</th>
                                    <th>Course</th>
                                    <th>Session</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td><input type="checkbox" class="student-checkbox" value="{{ $student->userId }}"></td>
                                        <td>{{ $student->name }} ({{ $student->email }})</td>
                                        <td>
                                            @if($student->admitted)
                                                <span class="badge badge-primary">Admitted</span>
                                            @else
                                                <span class="badge badge-danger">Not Admitted</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-success">Shortlisted</span></td>
                                        <td>{{ $student->course_name ?? 'N/A' }}</td>
                                        <td>{{ $student->session_name ?? 'N/A' }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-info dropdown-toggle" type="button" id="actionDropdown_{{ $student->userId }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="actionDropdown_{{ $student->userId }}">
                                                    @if(!$student->admitted)
                                                        <a class="dropdown-item admit-btn" href="javascript:void(0);" data-id="{{ $student->userId }}">Admit</a>
                                                    @else
                                                        <a class="dropdown-item admit-btn" href="javascript:void(0);" data-id="{{ $student->userId }}" data-course_id="{{ $student->course_id ?? '' }}" data-session_id="{{ $student->session_id ?? '' }}">Change Admission</a>
                                                        @if($student->session_name)
                                                            <a class="dropdown-item" href="{{ url('student/select-session') }}/{{ $student->userId }}" target="_blank">Choose Session</a>
                                                        @endif
                                                        @if($student->session_name)
                                                            <a class="dropdown-item delete-admission" href="javascript:void(0);" data-userid="{{ $student->userId }}">Delete Admission</a>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.send-bulk-email')

    <x-modal id="shortlisted_students" title="Copy and Paste Shortlisted Student Emails" size="modal-lg">
        <label for="email_list">Paste Emails/Phonenumbers Here</label>
        <textarea class="form-control mb-3" name="email_list" id="email_list" rows="10" placeholder="Paste emails/numbers, one per line..."></textarea>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button id="shortlist-modal-submit" type="button" class="btn btn-primary">Submit</button>
        </x-slot>
    </x-modal>

    <div class="modal fade" id="admitModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Admit Student</h4>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/admin/admit') }}" name="admit_form" method="POST">
                        {{ csrf_field() }}
                        <input id="user_id" name="user_id" type="hidden" class="form-control" required>
                        <input id="change" name="change" value="false" type="hidden" class="form-control" required>
                        <div class="form-group">
                            <label for="course_id" class="form-label">Select Course</label>
                            <select id="course_id" name="course_id" class="form-control" required>
                                <option value="">Choose One Course</option>
                                @foreach ($courses as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="session_id" class="form-label">Choose Session</label>
                            <select id="session_id" name="session_id" class="form-control" @if(empty($sessions)) disabled @endif>
                                @if(empty($sessions))
                                    <option value="">No sessions available</option>
                                @else
                                    <option value="">Choose One Session</option>
                                    @foreach($sessions as $session)
                                        <option data-course="{{ $session->course_id }}" value="{{ $session->id }}">
                                            {{ $session->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if(empty($sessions))
                                <small class="text-muted">Sessions are not configured. Please contact support.</small>
                            @endif
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary" @if(empty($sessions)) disabled @endif>Admit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-modal id="bulk-sms-modal" title="Send Bulk SMS" size="modal-lg">
        <label for="sms_template">Select Template To Use</label>
        <select name="sms_template" id="sms_template" class="form-control">
            <option value="" selected disabled>Loading templates...</option>
        </select>
        <br>
        <label for="sms_message">Or Write Message</label>
        <textarea class="form-control mb-3" name="sms_message" id="sms_message" placeholder="Type your SMS message here..."></textarea>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button id="modal-submit" type="button" class="btn btn-primary">Submit</button>
        </x-slot>
    </x-modal>

    @push('after_scripts')
        <script type="text/javascript" src="{{ backpack_asset('js/jquery-multiselect.min.js') }}"></script>
        <script>
            // All your JS logic remains unchanged
        </script>
    @endpush
@endsection
