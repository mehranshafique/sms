@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('assignment.page_title') }}</h4>
                    <p class="mb-0">{{ __('assignment.subtitle') }}</p>
                </div>
            </div>
            @if(auth()->user()->can('create', App\Models\Assignment::class) || auth()->user()->hasRole(['Super Admin', 'Head Officer', 'Teacher']))
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('assignments.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('assignment.create_new') }}
                </a>
            </div>
            @endif
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('assignment.title') }}</th>
                                        <th>{{ __('assignment.class') }}</th>
                                        <th>{{ __('assignment.subject') }}</th>
                                        <th>{{ __('assignment.deadline') }}</th>
                                        <th>{{ __('assignment.teacher') }}</th>
                                        <th>{{ __('assignment.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <strong>{{ $assignment->title }}</strong>
                                            @if($assignment->file_path)
                                                <a href="{{ asset('storage/'.$assignment->file_path) }}" target="_blank" class="text-primary ms-2" title="{{ __('assignment.attachment_view') }}">
                                                    <i class="fa fa-paperclip"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ $assignment->classSection->gradeLevel->name ?? '' }} {{ $assignment->classSection->name }}</td>
                                        <td>{{ $assignment->subject->name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $assignment->deadline < now() ? 'danger' : 'success' }}">
                                                {{ $assignment->deadline->format('d M, Y') }}
                                            </span>
                                        </td>
                                        <td>{{ $assignment->teacher->user->name ?? 'Admin' }}</td>
                                        <td>
                                            {{-- Delete Button with SweetAlert Class --}}
                                            @if(auth()->user()->can('delete', $assignment) || auth()->user()->hasRole(['Super Admin', 'Head Officer']))
                                                <button type="button" class="btn btn-danger shadow btn-xs sharp delete-assignment-btn" 
                                                        data-id="{{ $assignment->id }}" 
                                                        data-url="{{ route('assignments.destroy', $assignment->id) }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('assignment.no_assignments') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $assignments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // SweetAlert Delete Logic
        document.querySelectorAll('.delete-assignment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                
                Swal.fire({
                    title: '{{ __("assignment.delete_confirm") }}',
                    text: "{{ __('assignment.delete_warning') }}", // Ensure this key exists or use a generic warning
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '{{ __("assignment.yes_delete") }}',
                    cancelButtonText: '{{ __("assignment.cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create a temporary form to submit the DELETE request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = '{{ csrf_token() }}';
                        form.appendChild(csrfInput);
                        
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        form.appendChild(methodInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
        
        // Show success message if redirected back
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false
            });
        @endif
    });
</script>
@endsection