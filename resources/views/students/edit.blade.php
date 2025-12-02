@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">
            <h3>Edit Student</h3>
            <form action="{{ route('students.update', $student->id) }}" method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Institute <span class="text-danger">*</span></label>
                        <select name="institute_id" class="form-select" required>
                            <option value="">-- Select Institute --</option>
                            @foreach($institutes as $institute)
                                <option value="{{ $institute->id }}" {{ $student->institute_id == $institute->id ? 'selected' : '' }}>
                                    {{ $institute->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Registration No <span class="text-danger">*</span></label>
                        <input type="text" name="registration_no" class="form-control" value="{{ $student->registration_no }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="{{ $student->first_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ $student->middle_name }}">
                    </div>
                    <div class="col-md-4">
                        <label>Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="{{ $student->last_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="male" {{ $student->gender=='male'?'selected':'' }}>Male</option>
                            <option value="female" {{ $student->gender=='female'?'selected':'' }}>Female</option>
                            <option value="other" {{ $student->gender=='other'?'selected':'' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ $student->date_of_birth }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $student->status=='active'?'selected':'' }}>Active</option>
                            <option value="transferred" {{ $student->status=='transferred'?'selected':'' }}>Transferred</option>
                            <option value="withdrawn" {{ $student->status=='withdrawn'?'selected':'' }}>Withdrawn</option>
                            <option value="graduated" {{ $student->status=='graduated'?'selected':'' }}>Graduated</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
@endsection
