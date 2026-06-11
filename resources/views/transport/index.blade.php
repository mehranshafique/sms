@extends('layout.layout')



@section('styles')

<style>

    .transport-page .content-body,

    .content-body.transport-page-body {

        padding-bottom: 6rem !important;

    }

    .transport-tabs .nav-link {

        font-weight: 600;

    }

    .transport-tabs .nav-link.active {

        color: var(--primary);

        border-bottom: 2px solid var(--primary);

    }

</style>

@endsection



@section('content')

<div class="content-body transport-page-body">

    <div class="container-fluid transport-page">

        <div class="row page-titles mx-0 mb-3">

            <div class="col-sm-8">

                <h4 class="mb-1">{{ __('transport.page_title') }}</h4>

                <p class="text-muted mb-0">{{ __('transport.subtitle') }}</p>

            </div>

        </div>



        <ul class="nav nav-tabs transport-tabs mb-4" role="tablist">

            <li class="nav-item">

                <a class="nav-link active" data-bs-toggle="tab" href="#tab-vehicles" role="tab">

                    <i class="la la-bus me-1"></i> {{ __('transport.vehicles') }}

                    <span class="badge badge-primary ms-1">{{ $vehicles->count() }}</span>

                </a>

            </li>

            <li class="nav-item">

                <a class="nav-link" data-bs-toggle="tab" href="#tab-routes" role="tab">

                    <i class="la la-road me-1"></i> {{ __('transport.routes') }}

                    <span class="badge badge-primary ms-1">{{ $routes->count() }}</span>

                </a>

            </li>

            <li class="nav-item">

                <a class="nav-link" data-bs-toggle="tab" href="#tab-assign" role="tab">

                    <i class="la la-user-plus me-1"></i> {{ __('transport.assign_student') }}

                </a>

            </li>

        </ul>



        <div class="tab-content">

            {{-- Vehicles --}}

            <div class="tab-pane fade show active" id="tab-vehicles" role="tabpanel">

                <div class="row">

                    <div class="col-lg-4 mb-4">

                        <div class="card shadow-sm h-100">

                            <div class="card-header border-0 pb-0">

                                <h5 class="card-title mb-0"><i class="la la-bus me-1 text-primary"></i> {{ __('transport.add_vehicle') }}</h5>

                            </div>

                            <div class="card-body">

                                <form method="POST" action="{{ route('transport.vehicles.store') }}" class="transport-form">

                                    @csrf

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.plate_number') }} <span class="text-danger">*</span></label>

                                        <input class="form-control" name="plate_number" placeholder="ABC-1234" required>

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.capacity') }} <span class="text-danger">*</span></label>

                                        <input class="form-control" type="number" name="capacity" min="1" placeholder="30" required>

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.driver_name') }}</label>

                                        <input class="form-control" name="driver_name">

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.driver_phone') }}</label>

                                        <input class="form-control" name="driver_phone">

                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">{{ __('transport.add_vehicle') }}</button>

                                </form>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-8 mb-4">

                        <div class="card shadow-sm h-100">

                            <div class="card-header border-0">

                                <h5 class="card-title mb-0">{{ __('transport.vehicles') }} ({{ $vehicles->count() }})</h5>

                            </div>

                            <div class="card-body p-0">

                                <div class="table-responsive">

                                    <table class="table table-hover mb-0">

                                        <thead class="bg-light">

                                            <tr>

                                                <th>{{ __('transport.plate_number') }}</th>

                                                <th>{{ __('transport.capacity') }}</th>

                                                <th>{{ __('transport.driver_name') }}</th>

                                                <th>{{ __('transport.driver_phone') }}</th>

                                                <th>{{ __('transport.routes') }}</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            @forelse($vehicles as $vehicle)

                                                <tr>

                                                    <td class="fw-bold">{{ $vehicle->plate_number }}</td>

                                                    <td>{{ $vehicle->capacity }}</td>

                                                    <td>{{ $vehicle->driver_name ?: '—' }}</td>

                                                    <td>{{ $vehicle->driver_phone ?: '—' }}</td>

                                                    <td><span class="badge badge-primary">{{ $vehicle->routes->count() }}</span></td>

                                                </tr>

                                            @empty

                                                <tr>

                                                    <td colspan="5" class="text-center text-muted py-4">{{ __('transport.no_vehicles') }}</td>

                                                </tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- Routes --}}

            <div class="tab-pane fade" id="tab-routes" role="tabpanel">

                <div class="row">

                    <div class="col-lg-4 mb-4">

                        <div class="card shadow-sm h-100">

                            <div class="card-header border-0 pb-0">

                                <h5 class="card-title mb-0"><i class="la la-road me-1 text-primary"></i> {{ __('transport.add_route') }}</h5>

                            </div>

                            <div class="card-body">

                                <form method="POST" action="{{ route('transport.routes.store') }}" class="transport-form">

                                    @csrf

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.route_name') }} <span class="text-danger">*</span></label>

                                        <input class="form-control" name="name" required>

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.vehicles') }}</label>

                                        <select class="form-control default-select" name="transport_vehicle_id">

                                            <option value="">— {{ __('transport.select_vehicle') }} —</option>

                                            @foreach($vehicles as $v)

                                                <option value="{{ $v->id }}">{{ $v->plate_number }} ({{ $v->capacity }})</option>

                                            @endforeach

                                        </select>

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.departure_time') }}</label>

                                        <input class="form-control" type="time" name="departure_time">

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label">{{ __('transport.zones') }}</label>

                                        <input class="form-control" name="zones" placeholder="{{ __('transport.zones_hint') }}">

                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">{{ __('transport.add_route') }}</button>

                                </form>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-8 mb-4">

                        <div class="card shadow-sm h-100">

                            <div class="card-header border-0">

                                <h5 class="card-title mb-0">{{ __('transport.routes') }} ({{ $routes->count() }})</h5>

                            </div>

                            <div class="card-body p-0">

                                <div class="table-responsive">

                                    <table class="table table-hover mb-0">

                                        <thead class="bg-light">

                                            <tr>

                                                <th>{{ __('transport.route_name') }}</th>

                                                <th>{{ __('transport.vehicles') }}</th>

                                                <th>{{ __('transport.zones') }}</th>

                                                <th>{{ __('transport.assignments') }}</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            @forelse($routes as $route)

                                                <tr>

                                                    <td class="fw-bold">{{ $route->name }}</td>

                                                    <td>{{ $route->vehicle?->plate_number ?? '—' }}</td>

                                                    <td>{{ $route->zones ?: '—' }}</td>

                                                    <td><span class="badge badge-info">{{ $route->assignments->count() }}</span></td>

                                                </tr>

                                            @empty

                                                <tr>

                                                    <td colspan="4" class="text-center text-muted py-4">{{ __('transport.no_routes') }}</td>

                                                </tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- Assign --}}

            <div class="tab-pane fade" id="tab-assign" role="tabpanel">

                <div class="card shadow-sm mb-4">

                    <div class="card-header border-0">

                        <h5 class="card-title mb-0">{{ __('transport.assign_student') }}</h5>

                    </div>

                    <div class="card-body">

                        @if($routes->isEmpty() || $students->isEmpty())

                            <div class="alert alert-warning mb-0">{{ __('transport.assign_requires_data') }}</div>

                        @else

                            <form method="POST" action="{{ route('transport.assignments.store') }}" class="transport-form row g-3">

                                @csrf

                                <div class="col-md-4">

                                    <label class="form-label">{{ __('transport.routes') }}</label>

                                    <select class="form-control default-select" name="transport_route_id" required>

                                        @foreach($routes as $r)

                                            <option value="{{ $r->id }}">{{ $r->name }}</option>

                                        @endforeach

                                    </select>

                                </div>

                                <div class="col-md-4">

                                    <label class="form-label">{{ __('transport.student') }}</label>

                                    <select class="form-control default-select" name="student_id" required>

                                        @foreach($students as $s)

                                            <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>

                                        @endforeach

                                    </select>

                                </div>

                                <div class="col-md-4">

                                    <label class="form-label">{{ __('transport.pickup_point') }}</label>

                                    <input class="form-control" name="pickup_point">

                                </div>

                                <div class="col-12">

                                    <button type="submit" class="btn btn-success"><i class="la la-user-plus me-1"></i> {{ __('transport.assign_student') }}</button>

                                </div>

                            </form>

                        @endif

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

$(function() {

    @if(session('success'))

        if (typeof toastr !== 'undefined') {

            toastr.success(@json(session('success')));

        } else {

            Swal.fire({ icon: 'success', title: @json(__('transport.success')), text: @json(session('success')), timer: 2500, showConfirmButton: false });

        }

    @endif

    @if(session('error'))

        Swal.fire({ icon: 'error', title: @json(__('transport.error')), text: @json(session('error')) });

    @endif



    @if(session('transport_tab'))

        $('a[href="#{{ session('transport_tab') }}"]').tab('show');

    @endif

});

</script>

@endsection

