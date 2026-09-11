<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    if (typeof $.fn.clockpicker !== 'undefined') {
        $('.clockpicker').clockpicker({ autoclose: true });
    }

    var $grades = $('#scheduleGradeSelect');
    var $sections = $('#scheduleSectionSelect');

    function refreshSectionOptions() {
        var selectedGrades = ($grades.val() || []).map(String);

        $sections.find('option').each(function () {
            var $opt = $(this);
            var gradeId = String($opt.data('grade-id') || '');
            var matchesGrade = selectedGrades.indexOf(gradeId) !== -1;
            var keepSelected = $opt.prop('selected') && selectedGrades.length === 0;
            var visible = matchesGrade || keepSelected;

            // When grades are chosen, drop sections outside those grades.
            if (selectedGrades.length > 0 && !matchesGrade) {
                $opt.prop('selected', false);
                visible = false;
            }

            $opt.prop('disabled', !visible).toggle(visible);
        });

        if ($sections.hasClass('selectpicker') || $sections.parent().hasClass('bootstrap-select')) {
            $sections.selectpicker('refresh');
        } else if ($sections.data('select2')) {
            $sections.trigger('change.select2');
        }
    }

    $grades.on('changed.bs.select change', refreshSectionOptions);
    refreshSectionOptions();

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
