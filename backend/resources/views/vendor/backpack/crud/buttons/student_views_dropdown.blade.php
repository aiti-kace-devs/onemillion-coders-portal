<div class="dropdown d-inline-block me-2">
    <button class="btn btn-primary dropdown-toggle" type="button" id="studentViewsDropdown" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="la la-eye"></i> Student Views
    </button>
    <ul class="dropdown-menu" aria-labelledby="studentViewsDropdown">
        <li>
            <a class="dropdown-item" href="{{ backpack_url('manage-student') }}">
                <i class="la la-users text-primary"></i> All Students
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ backpack_url('students-with-admission') }}">
                <i class="la la-user-check text-success"></i> Students with Admission
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ backpack_url('students-without-exam-results') }}">
                <i class="la la-file-alt text-warning"></i> Students without Exam Results
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ backpack_url('students-yet-to-accept-admission') }}">
                <i class="la la-clock text-info"></i> Students Yet to Accept Admission
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ backpack_url('students-with-exam-results') }}">
                <i class="la la-check-circle text-primary"></i> Students with Exam Results
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ backpack_url('shortlisted-students') }}">
                <i class="la la-star text-warning"></i> Shortlisted Students
            </a>
        </li>
    </ul>
</div>
