@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('students.create') }}" class="btn btn-primary">{{ __('students.add_student') }}</a>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('students.institute') }}</th>
                                        <th>{{ __('students.registration_no') }}</th>
                                        <th>{{ __('students.name') }}</th>
                                        <th>{{ __('students.gender') }}</th>
                                        <th>{{ __('students.dob') }}</th>
                                        <th>{{ __('students.status') }}</th>
                                        <th>{{ __('students.actions') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($students as $key => $student)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $student->institute->name }}</td>
                                            <td>{{ $student->registration_no }}</td>
                                            <td>{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
                                            <td>{{ ucfirst($student->gender) }}</td>
                                            <td>{{ $student->date_of_birth }}</td>
                                            <td>{{ ucfirst($student->status) }}</td>
                                            <td>
                                                <a href="{{ route('students.edit', $student->id) }}" class="btn btn-xs sharp btn-primary">
                                                    <i class="fa fa-pencil"></i> {{ __('students.edit') }}
                                                </a>

                                                <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="d-inline deleteForm">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-xs sharp btn-danger deleteBtn">
                                                        <i class="fa fa-trash"></i> {{ __('students.delete') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                                {{--                            {{ $students->links() }}--}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.deleteBtn').forEach(button => {
                button.addEventListener('click', function() {
                    let form = this.closest('form');
                    Swal.fire({
                        title: '{{ __("students.delete_confirmation_title") }}',
                        text: '{{ __("students.delete_confirmation_text") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("students.delete_confirm_button") }}',
                        cancelButtonText: '{{ __("students.delete_cancel_button") }}'
                    }).then(result => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        });
    </script>

@endsection
