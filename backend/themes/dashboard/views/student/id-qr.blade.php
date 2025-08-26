@extends('layouts.student')
@section('title', 'Portal dashboard')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">My Details</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">My Details</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @if (session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                @elseif (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    function detailsUpdated($user)
                    {
                        return $user->details_updated_at != null;
                    }
                @endphp
                <!-- Small boxes (Stat box) -->
                <form action="{{ route('student.updateDetails') }}" method="POST" name="student-details">
                    @csrf
                    {{-- @method('PATCH') --}}
                    <div class="row g-3 flex mb-2 align-items-center">
                        @if($user->hasSeparateNameFields())
                            <!-- Show separate name fields if user has them -->
                            <div class="col-12 mb-2">
                                <label class="form-label col-12">First Name</label>
                                <input id="first_name" type="text" required value="{{ $user->first_name }}" name="first_name"
                                    class="form-control col-12 @error('first_name') is-invalid @enderror"
                                    @if (detailsUpdated($user)) disabled @endif>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-2">
                                <label class="form-label col-12">Middle Name</label>
                                <input id="middle_name" type="text" value="{{ $user->middle_name }}" name="middle_name"
                                    class="form-control col-12 @error('middle_name') is-invalid @enderror"
                                    @if (detailsUpdated($user)) disabled @endif>
                                @error('middle_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-2">
                                <label class="form-label col-12">Last Name</label>
                                <input id="last_name" type="text" required value="{{ $user->last_name }}" name="last_name"
                                    class="form-control col-12 @error('last_name') is-invalid @enderror"
                                    @if (detailsUpdated($user)) disabled @endif>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <!-- Show single name field for existing users -->
                            <div class="col-12 mb-2">
                                <label class="form-label col-12">Fullname (as appears on your Ghana Card/ any National ID)
                                </label>
                                <input id="name" type="text" required value=" {{ $user->student_name }}" name="name"
                                    class="form-control col-12  @error('name') is-invalid @enderror"
                                    @if (detailsUpdated($user)) disabled @endif>
                                @if ($user->previous_name)
                                    <div class="text-primary">Previous Name: {{ $user->previous_name }}</div>
                                @endif
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <!-- Option to switch to separate name fields -->
                                @if (!detailsUpdated($user))
                                    <div class="mt-2">
                                        <button type="button" onclick="switchToSeparateFields()" 
                                            class="btn btn-sm btn-outline-primary">
                                            Use separate first, middle, and last name fields
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Hidden separate name fields for switching -->
                            <div id="separate-name-fields" style="display: none;">
                                <div class="col-12 mb-2">
                                    <label class="form-label col-12">First Name</label>
                                    <input id="first_name" type="text" name="first_name"
                                        class="form-control col-12 @error('first_name') is-invalid @enderror">
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label col-12">Middle Name</label>
                                    <input id="middle_name" type="text" name="middle_name"
                                        class="form-control col-12 @error('middle_name') is-invalid @enderror">
                                    @error('middle_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label col-12">Last Name</label>
                                    <input id="last_name" type="text" name="last_name"
                                        class="form-control col-12 @error('last_name') is-invalid @enderror">
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Card Type</label>
                            <select id="card_type" name="card_type"
                                class="form-control @error('card_type') is-invalid @enderror"
                                @if (detailsUpdated($user)) disabled @endif required>
                                <option value="">Select Card Type</option>
                                <option value="ghcard"
                                    {{ old('card_type', $user->card_type) === 'ghcard' ? 'selected' : '' }}>Ghana Card
                                </option>
                                <option value="voters_id"
                                    {{ old('card_type', $user->card_type) === 'voters_id' ? 'selected' : '' }}>Voter's
                                    ID</option>
                                <option value="drivers_license"
                                    {{ old('card_type', $user->card_type) === 'drivers_license' ? 'selected' : '' }}>
                                    Driver's
                                    License</option>
                                <option value="passport"
                                    {{ old('card_type', $user->card_type) === 'passport' ? 'selected' : '' }}>Passport
                                </option>
                            </select>
                            @error('card_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group col-12 mb-2">
                            <label class="form-label col-12">Card ID</label>
                            @if ($user->card_type === 'ghcard' || $user->card_type == null)
                                <div id="ghana-card-prefix" class="input-group-prepend none">
                                    <span class="input-group-text" id="basic-addon1">GHA-</span>
                                </div>
                            @endif
                            <input id="ghcard" type="text" required value="{{ old('ghcard', $user->ghcard) }}"
                                name="ghcard" placeholder="123456789-1" @if (detailsUpdated($user)) disabled @endif
                                class="form-control  @error('ghcard') is-invalid @enderror
                                @if (!empty($user->verification_date)) is-valid @else is-invalid @endif
                                          col-12 mr-2">
                            @error('ghcard')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                {{-- Invalid Feedback --}}
                                @if (empty($user->verification_date))
                                    <div class="invalid-feedback">
                                        Card not verified (This will be done manually by an administrator)
                                    </div>
                                @endif
                                {{-- Valid Feedback --}}
                                @if (!empty($user->verification_date))
                                    <div class="valid-feedback">
                                        Card Verified Successfully
                                    </div>
                                @endif
                            @enderror

                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Gender</label>
                            <select id="gender" name="gender" class="form-control"
                                @if ($user->gender) disabled @endif required>
                                <option value="">Select Gender</option>
                                <option value="male" {{ $user->gender === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ $user->gender === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Network Type</label>
                            <select id="network_type" name="network_type"
                                class="form-control  @error('network_type') is-invalid @enderror"
                                @if ($user->network_type) disabled @endif required>
                                <option value="">Select Network</option>
                                <option value="mtn"
                                    {{ old('network_type', $user->network_type) === 'mtn' ? 'selected' : '' }}>MTN</option>
                                <option value="telecel"
                                    {{ old('network_type', $user->network_type) === 'telecel' ? 'selected' : '' }}>Telecel
                                </option>
                                <option value="airteltigo"
                                    {{ old('network_type', $user->network_type) === 'airteltigo' ? 'selected' : '' }}>
                                    AirtelTigo</option>
                            </select>
                            @error('network_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group col-12 mb-2">
                            <label class="form-label col-12">Phone Number</label>
                            {{-- <div class="input-group-prepend">
                                @if (!$user->contact)
                                    <span class="input-group-text" id="basic-addon1">+233</span>
                                @endif
                            </div> --}}
                            <input id="mobile_no" type="text" required value="{{ $user->mobile_no }}" name="mobile_no"
                                placeholder="201234567" @if ($user->mobile_no) disabled @endif
                                class="form-control @error('mobile_no') is-invalid @enderror col-12 mr-2">
                        </div>
                        @error('mobile_no')
                            <div role="alert" class="alert alert-danger">{{ $message }}</div>
                        @enderror


                        <div class="col-12">
                            {{-- <p class="alert alert-info">You'll be given the opportunity to change your details later.</p> --}}
                            @if (detailsUpdated($user))
                                <p class="text-sm text-danger">You have already updated your details</p>
                            @else
                                <button id="confirmUpdateDetails" type="button" class="btn btn-primary">Update</button>
                                <p class="text-sm text-danger">You can only update your details once, make sure you verify
                                    all
                                    details before submitting.</p>
                            @endif
                        </div>
                    </div>
                </form>
                @if ($user->isAdmitted())
                    <div class="text-md">Location : {{ $user->location }} </div>
                    <div class="text-md">Course : {{ $user->course_name }}</div>
                    <div class="text-md">Session : {{ $user->selected_session }}</div>
                    <div class="text-lg font-bold mt-2">Student ID for Attendance</div>
                    <div id="qrcode"></div>
                    <button type="button" class="btn btn-primary" id="downloadQRCode">Download</button>
                @endif
                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

@endsection


@push('scripts')
    <script src="{{ asset('assets/js/jquery.inputmask.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/easy.qrcode.min.js') }}"></script>
    <script @nonce>
        const innerWidth = Math.floor(window.innerWidth * (7 / 9));
        const width = innerWidth > 400 ? 400 : innerWidth
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: "{{ Auth::user()->userId }}",
            width: width,
            height: width,
            colorDark: "black",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H,
            quietZone: 20,
            logo: "{{ asset('assets/images/logo-bt.png') }}",
            logoWidth: 170,
            logoHeight: 80,
        });


        function downloadQRCode() {
            qrcode.download("StudentName-{{ Auth::user()->name }}")
        }

        $(document).ready(function() {
            const cardTypeSelect = $("#card_type");
            const ghcardInput = $("#ghcard");
            const downloadQRButton = $('#downloadQRCode');

            downloadQRButton.on('click', function() {
                downloadQRCode();
            })

            function toggleInputMask() {
                if (cardTypeSelect.val() === "ghcard") {
                    ghcardInput.inputmask({
                        mask: "555555555-5",
                        definitions: {
                            "5": {
                                validator: "[0-9]",
                            },
                        },
                    });
                } else {
                    ghcardInput.inputmask("remove");
                }
            }

            cardTypeSelect.on("change", toggleInputMask);

            toggleInputMask();
        });


        // $("#ghcard").inputmask({
        //     mask: "555555555-5",
        //     definitions: {
        //         '5': {
        //             validator: "[0-9]"
        //         },
        //     }
        // });

        $('#confirmUpdateDetails').on('click', function() {
            confirmUpdateDetails();
        });

        function confirmUpdateDetails() {
            Swal.fire({
                title: 'Confirm Submission',
                text: `Are you sure you want to submit this update. This cannot be undone. Make sure all details are correct`,
                icon: 'info',
                backdrop: `rgba(0,0,0,0.95)`,
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'No, Cancel',
                showCancelButton: true,
                allowOutsideClick: false,
                preConfirm: async () => {
                    $('[name="student-details"]').submit()
                }
            })
        }

        document.addEventListener("DOMContentLoaded", function() {
            const cardTypeSelect = document.getElementById("card_type");
            const ghanaCardPrefix = document.getElementById("ghana-card-prefix");

            function togglePrefix() {
                if (cardTypeSelect.value === "ghcard") {
                    ghanaCardPrefix.style.display = "flex";
                } else {
                    ghanaCardPrefix.style.display = "none";
                }
            }

            togglePrefix();

            cardTypeSelect.addEventListener("change", togglePrefix);
        });

        function switchToSeparateFields() {
            const nameInput = document.getElementById('name');
            const separateFieldsDiv = document.getElementById('separate-name-fields');
            const nameValue = nameInput.value.trim();
            
            if (nameValue) {
                // Parse the existing name into separate fields
                const nameParts = nameValue.split(' ');
                
                if (nameParts.length >= 2) {
                    document.getElementById('first_name').value = nameParts[0];
                    document.getElementById('last_name').value = nameParts[nameParts.length - 1];
                    
                    if (nameParts.length > 2) {
                        document.getElementById('middle_name').value = nameParts.slice(1, -1).join(' ');
                    }
                } else {
                    document.getElementById('first_name').value = nameValue;
                    document.getElementById('last_name').value = '';
                }
            }
            
            // Hide the single name field and show separate fields
            nameInput.parentElement.style.display = 'none';
            separateFieldsDiv.style.display = 'block';
        }
    </script>
@endpush
