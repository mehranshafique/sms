<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    if (typeof $.fn.clockpicker !== 'undefined') {
        $('.clockpicker').clockpicker({ autoclose: true });
    }

    $('#attendanceScheduleForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function (response) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("attendance_schedule.success") }}',
                    text: response.message
                }).then(function () {
                    window.location.href = response.redirect;
                });
            },
            error: function (xhr) {
                var msg = '{{ __("attendance_schedule.error_occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("attendance_schedule.validation_error") }}',
                    html: msg
                });
            }
        });
    });
});
</script>
