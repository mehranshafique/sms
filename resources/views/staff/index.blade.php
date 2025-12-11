@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('staff.create') }}" class="btn btn-primary">
                        {{ __('staff.index.add') }}
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
                                        <th>{{ __('staff.index.table.serial') }}</th>
                                        <th>{{ __('staff.index.table.employee_no') }}</th>
                                        <th>{{ __('staff.index.table.user') }}</th>
                                        <th>{{ __('staff.index.table.campus') }}</th>
                                        <th>{{ __('staff.index.table.designation') }}</th>
                                        <th>{{ __('staff.index.table.department') }}</th>
                                        <th>{{ __('staff.index.table.hire_date') }}</th>
                                        <th>{{ __('staff.index.table.status') }}</th>
                                        <th>{{ __('staff.index.table.action') }}</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($staff as $key => $item)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $item->employee_no }}</td>
                                            <td>{{ $item->user->name }}</td>
                                            <td>{{ $item->institute->name }}</td>
                                            <td>{{ $item->designation }}</td>
                                            <td>{{ $item->department }}</td>
                                            <td>{{ $item->hire_date }}</td>
                                            <td>{{ ucfirst($item->status) }}</td>

                                            <td>
                                                <a href="{{ route('staff.edit', $item->id) }}"
                                                   class="btn btn-xs sharp btn-primary">
                                                    <i class="fa fa-pencil"></i>
                                                </a>

                                                <form action="{{ route('staff.destroy', $item->id) }}"
                                                      method="POST"
                                                      class="d-inline deleteForm">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                            class="btn btn-xs sharp btn-danger deleteBtn">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>

                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.deleteBtn').forEach(button => {
                button.addEventListener('click', function () {
                    let form = this.closest('form');

                    Swal.fire({
                        title: '{{ __("staff.index.confirm_delete_title") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("staff.index.confirm_delete_button") }}'
                    }).then(result => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });

        });
    </script>
@endsection
