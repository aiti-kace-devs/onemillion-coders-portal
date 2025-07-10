 <script @nonce>
     $(document).ready(function() {
         $('#revoke-admission-button').click(function() {
             Swal.fire({
                 title: 'Revoke Admission?',
                 html: `<ol class = "text-left" style = "font-size:1.2rem;color:red" >
                        <li>This action is irreversible !</li>
                        <li>Your slot WILL NOT be reserved !!</li>
                    </ol>`,
                 icon: 'warning',
                 showCancelButton: true,
                 confirmButtonColor: '#d33',
                 cancelButtonColor: '#6c757d',
                 confirmButtonText: 'Yes, revoke it!',
                 cancelButtonText: 'Cancel'
             }).then((result) => {
                 if (result.isConfirmed) {
                     // Proceed with deletion via AJAX
                     $.ajax({
                         url: "{{ url('student/delete-student-admission') }}/" +
                             "{{ $id }}",
                         type: 'DELETE',
                         headers: {
                             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                         },
                         success: function(response) {
                             const returnUrl = "{{ $returnUrl ?? '' }}";
                             if (returnUrl) {
                                 window.location.href = returnUrl;
                             } else {
                                 window.location.reload();
                             }
                         },
                         error: function(xhr) {
                             toastr.error(xhr.responseJSON?.message ||
                                 'Failed to delete admission.');
                         }
                     });
                 }
             });
         });
     });
 </script>
