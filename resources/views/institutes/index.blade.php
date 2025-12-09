@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('institutes.create') }}" class="btn btn-primary">
                        {{ __('institute.add_institute') }}
                    </a>
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
                                        <th>{{ __('institute.table_no') }}</th>
                                        <th>{{ __('institute.name') }}</th>
                                        <th>{{ __('institute.code') }}</th>
                                        <th>{{ __('institute.type') }}</th>
                                        <th>{{ __('institute.city') }}</th>
                                        <th>{{ __('institute.phone') }}</th>
                                        <th>{{ __('institute.status') }}</th>
                                        <th>{{ __('institute.action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($institutes as $key => $institute)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $institute->name }}</td>
                                            <td>{{ $institute->code }}</td>
                                            <td>{{ ucfirst($institute->type) }}</td>
                                            <td>{{ $institute->city }}</td>
                                            <td>{{ $institute->phone }}</td>
                                            <td>
                                                {{ $institute->is_active ? __('institute.active') : __('institute.inactive') }}
                                            </td>
                                            <td>
                                                <a href="{{ route('institutes.edit', $institute->id) }}" class="btn btn-xs sharp btn-primary">
                                                    <i class="fa fa-pencil"></i>
                                                </a>

                                                <form action="{{ route('institutes.destroy', $institute->id) }}" method="POST" class="d-inline deleteInstituteForm">
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

                                {{ $institutes->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.deleteBtn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    let form = this.closest('form');

                    Swal.fire({
                        title: "{{ __('institute.are_you_sure') }}",
                        text: "{{ __('institute.delete_warning') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('institute.yes_delete') }}"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

@endsection
