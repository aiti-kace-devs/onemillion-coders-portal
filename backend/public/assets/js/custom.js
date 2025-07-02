sAlert = (message, options = {}, callbackFn = undefined) => {
    options['toast'] = typeof options['toast'] === 'undefined' ? true : options['toast'];
                    Swal.fire({
                        title: 'Success!',
                        text: message,
                        icon: 'success',
                        backdrop: options.toast ? false : `rgba(0,0,0,0.95)`,
                        confirmButtonText: 'Okay',
                        allowOutsideClick: false,
                        timer: options.toast?  5000 : undefined,
                        toast: true,
                        position: options.toast ? 'top-end' : 'center',
                        showConfirmButton: callbackFn ? true : false,
                        preConfirm: () => {
                            if (callbackFn){
                                callbackFn();
                            }
                        },
                        ...options
                    });
}

$(document).on('submit','.database_operation',function(){
    var url=$(this).attr('action');
    var data=$(this).serialize();
    $.post(url,data,function(fb){
        var resp=$.parseJSON(fb);
        if(resp.status=='true'){
            sAlert(resp.message);
            setTimeout(() => {
                window.location.href=resp.reload;
            }, 1000);
        }
        else{
            sAlert(resp.message);
        }
    });
    return false;
});

$(document).on('click','.apply_exam',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/student/apply_exam/'+id,function(fb){
        var resp=$.parseJSON(fb);
        if(resp.status=='true'){
            sAlert(resp.message);
            setTimeout(() => {
                window.location.href=resp.reload;
            }, 1000);
        }
        else{
            sAlert(resp.message);
        }
    })
})

$(document).on('click','.category_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/category_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})



$(document).on('click','.branch_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/branch_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})



$(document).on('click','.centre_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/centre_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})




$(document).on('click','.programme_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/programme_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})




$(document).on('click','.course_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/course_status/'+id,function(fb){
      sAlert("status successsfully changed");
    })
})



$(document).on('click','.is_super_admin_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/is_super_admin_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})


$(document).on('click','.exam_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/exam_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})

$(document).on('click','.student_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/student_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})

$(document).on('click','.portal_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/portal_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})

$(document).on('click','.question_status',function(){
    var id=$(this).attr('data-id');
    $.get(BASE_URL+'/admin/question_status/'+id,function(fb){
        sAlert("status successsfully changed");
    })
})

$(document).on('submit', '#manage_form', function (e) {
    e.preventDefault();
    $.ajax({
        type: method,
        url: manageAction,
        data: $(this).serialize(),
        success: function (response) {
            // Refresh the page after a short delay (1 second)
            setTimeout(() => {
                window.location.href = response.reload;
            }, 1000);

            toastr.success(response.message);
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                toastr.error('Something went wrong. Try again.');
                let errors = xhr.responseJSON.errors;

                $.each(errors, function (field, messages) {
                    field = field.replace('.', '_')
                    $('#' + field).addClass('is-invalid');
                    $('.' + field + '_error').text(messages[0]);
                });
            } else {
                // Handle other types of errors if needed
                alert("An unexpected error occurred. Please try again.");
            }
        }
    });
});

$('#manageModal').on('hide.bs.modal', function (event) {
    $('#manage_form :input').removeClass('is-invalid');
    $('#manage_form .invalid-feedback').text("");
    $('#manage_form')[0].reset();
});