@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('lmd.units_page_title') }}</h4>
                    <p class="mb-0">Manage LMD Academic Units (UE) and Subject Assignments</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#unitModal">
                    <i class="fa fa-plus me-2"></i> {{ __('lmd.create_unit') }}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="unitsTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('lmd.code') }}</th>
                                        <th>{{ __('lmd.unit_name') }}</th>
                                        <th>{{ __('lmd.type') }}</th>
                                        <th>{{ __('lmd.program_name') }} / Grade</th>
                                        <th>{{ __('lmd.semester') }}</th>
                                        <th>{{ __('lmd.credits') }}</th>
                                        <th>Subjects</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- CREATE/EDIT MODAL --}}
<div class="modal fade" id="unitModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="unitForm" action="{{ route('units.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="unitId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">{{ __('lmd.create_unit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('lmd.unit_name') }} *</label>
                        <input type="text" name="name" id="unitName" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.code') }}</label>
                            <input type="text" name="code" id="unitCode" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.type') }} *</label>
                            <select name="type" id="unitType" class="form-control default-select">
                                <option value="fundamental">{{ __('lmd.fundamental') }}</option>
                                <option value="transversal">{{ __('lmd.transversal') }}</option>
                                <option value="optional">{{ __('lmd.optional') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Link Mode Toggle --}}
                    <div class="mb-3">
                        <label class="form-label d-block fw-bold">Link Unit To:</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="link_mode" id="modeProgram" value="program" checked>
                            <label class="form-check-label" for="modeProgram">{{ __('lmd.program_name') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="link_mode" id="modeGrade" value="grade">
                            <label class="form-check-label" for="modeGrade">{{ __('sidebar.grade_levels.title') }}</label>
                        </div>
                    </div>

                    {{-- Program Selector (Default) --}}
                    <div class="row" id="programGroup">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('lmd.program_name') }} *</label>
                            <select name="program_id" id="programSelect" class="form-control default-select">
                                <option value="">-- Select Program --</option>
                                @if(isset($programs))
                                    @foreach($programs as $prog)
                                        <option value="{{ $prog->id }}" data-semesters="{{ $prog->total_semesters }}">
                                            {{ $prog->name }} ({{ $prog->code }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    {{-- Grade Selector (Hidden) --}}
                    <div class="row d-none" id="gradeGroup">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('sidebar.grade_levels.title') }} *</label>
                            <select name="grade_level_id" id="gradeSelect" class="form-control default-select">
                                <option value="">-- Select Grade --</option>
                                @if(isset($grades))
                                    @foreach($grades as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.semester') }} *</label>
                            <select name="semester" id="semSelect" class="form-control default-select" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Initialize DataTable
        var table = $('#unitsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('units.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'code' },
                { data: 'name' },
                { data: 'type' },
                { data: 'link', name: 'program.name' }, // This column comes from Controller logic
                { data: 'semester' },
                { data: 'total_credits' },
                { data: 'subjects_count', searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // 2. Toggle Program vs Grade Logic
        $('input[name="link_mode"]').change(function() {
            if (this.value === 'program') {
                $('#programGroup').removeClass('d-none');
                $('#gradeGroup').addClass('d-none');
                
                $('#programSelect').prop('required', true);
                $('#gradeSelect').prop('required', false).val('');
                
                // Refresh selector UI
                if($.fn.selectpicker) {
                    $('#programSelect').selectpicker('refresh');
                    $('#gradeSelect').selectpicker('refresh');
                }
            } else {
                $('#programGroup').addClass('d-none');
                $('#gradeGroup').removeClass('d-none');
                
                $('#programSelect').prop('required', false).val('');
                $('#gradeSelect').prop('required', true);

                // Reset Semester to basic 1/2
                let semSelect = $('#semSelect');
                semSelect.empty().append('<option value="1">1</option><option value="2">2</option>');
                if($.fn.selectpicker) {
                    $('#programSelect').selectpicker('refresh');
                    $('#gradeSelect').selectpicker('refresh');
                    semSelect.selectpicker('refresh');
                }
            }
        });

        // 3. Dynamic Semesters based on Program
        $('#programSelect').change(function() {
            let semesters = $(this).find(':selected').data('semesters') || 8; 
            let semSelect = $('#semSelect');
            let currentVal = semSelect.val();
            
            semSelect.empty();
            for(let i=1; i<=semesters; i++) {
                let selected = (i == currentVal) ? 'selected' : '';
                semSelect.append(`<option value="${i}" ${selected}>Semester ${i}</option>`);
            }
            
            if($.fn.selectpicker) semSelect.selectpicker('refresh');
        });

        // 4. Edit Handler
        $(document).on('click', '.edit-unit', function() {
            var data = $(this).data('json');
            
            // Reset
            $('#unitForm')[0].reset();
            $('#unitId').val(data.id);
            $('#unitName').val(data.name);
            $('#unitCode').val(data.code);
            $('#unitType').val(data.type).change();

            // Handle Logic Mode
            if (data.program_id) {
                $('#modeProgram').prop('checked', true).trigger('change');
                $('#programSelect').val(data.program_id).trigger('change');
            } else {
                $('#modeGrade').prop('checked', true).trigger('change');
                $('#gradeSelect').val(data.grade_level_id);
            }

            // Set Semester (delayed slightly to ensure options populated)
            setTimeout(() => {
                $('#semSelect').val(data.semester);
                if($.fn.selectpicker) {
                    $('.default-select').selectpicker('refresh');
                }
            }, 100);

            $('#modalTitle').text('Edit Unit');
            $('#unitModal').modal('show');
        });

        // 5. Submit Handler
        $('#unitForm').submit(function(e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');
            var originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#unitModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(res.message);
                    btn.prop('disabled', false).html(originalText);
                },
                error: function(err) {
                    btn.prop('disabled', false).html(originalText);
                    let msg = err.responseJSON ? err.responseJSON.message : 'Error saving unit.';
                    alert(msg);
                }
            });
        });
        
        // 6. Delete Handler
        $(document).on('click', '.delete-unit', function() {
            if(!confirm('Delete this unit? Subjects will be detached.')) return;
            var id = $(this).data('id');
            $.ajax({
                url: '/academics/units/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    table.ajax.reload();
                    toastr.success(res.message);
                }
            });
        });

        // Reset Modal
        $('#unitModal').on('hidden.bs.modal', function () {
            $('#unitForm')[0].reset();
            $('#unitId').val('');
            $('#modalTitle').text("{{ __('lmd.create_unit') }}");
            $('#modeProgram').prop('checked', true).trigger('change');
            if($.fn.selectpicker) $('.default-select').selectpicker('refresh');
        });
    });
</script>
@endsection