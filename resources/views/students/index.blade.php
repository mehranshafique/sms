@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('students.create') }}" class="btn btn-primary">Add Student</a>
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
                                        <th>Institute</th>
                                        <th>Registration No</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>DOB</th>
                                        <th>Status</th>
                                        <th>Action</th>
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
{{--                                                <a href="{{ route('students.edit', $student->id) }}" class="btn btn-xs sharp btn-primary">--}}
{{--                                                    <i class="fa fa-pencil"></i>--}}
{{--                                                </a>--}}

                                                <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="d-inline deleteForm">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-xs sharp btn-danger deleteBtn">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
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
        </div>
    </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.deleteBtn').forEach(button => {
                    button.addEventListener('click', function() {
                        let form = this.closest('form');
                        Swal.fire({
                            title: 'Are you sure?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete it!'
                        }).then(result => {
                            if (result.isConfirmed) form.submit();
                        });
                    });
                });
            });
        </script>

@endsection
