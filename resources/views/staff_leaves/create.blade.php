@extends('layout.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff_leave.create_new') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('staff-leaves.index') }}" class="btn btn-light">
                    {{ __('staff_leave.cancel') }}
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('staff-leaves.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            {{-- Admin Selection --}}
                            @if($isAdmin)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('staff_leave.staff_member') }}</label>
                                    <select name="staff_id" class="form-control select2" required>
                                        <option value="">{{ __('staff_leave.select_staff_placeholder') }}</option>
                                        @foreach($staffMembers as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ __('staff_leave.leave_type') }}</label>
                                <select name="type" class="form-control default-select" required>
                                    <option value="sick">{{ __('staff_leave.type_sick') }}</option>
                                    <option value="casual">{{ __('staff_leave.type_casual') }}</option>
                                    <option value="maternity">{{ __('staff_leave.type_maternity') }}</option>
                                    <option value="paternity">{{ __('staff_leave.type_paternity') }}</option>
                                    <option value="unpaid">{{ __('staff_leave.type_unpaid') }}</option>
                                    <option value="other">{{ __('staff_leave.type_other') }}</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('staff_leave.start_date') }}</label>
                                    <input type="text" name="start_date" class="form-control datepicker" required placeholder="YYYY-MM-DD" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('staff_leave.end_date') }}</label>
                                    <input type="text" name="end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('staff_leave.reason') }}</label>
                                <textarea name="reason" class="form-control" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('staff_leave.attachment') }}</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('staff_leave.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        if($.fn.datepicker) {
            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }
        if($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
</script>
@endsection