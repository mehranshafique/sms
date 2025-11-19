@extends('layout.layout')

@section('content')

    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="row">
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-primary">
                        <div class="card-body">
                            <div class="media">
									<span class="me-3">
										<i class="la la-users"></i>
									</span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Students</p>
                                    <h3 class="text-white">3280</h3>
                                    <div class="progress mb-2 bg-white">
                                        <div class="progress-bar progress-animated bg-white" style="width: 80%"></div>
                                    </div>
                                    <small>80% Increase in 20 Days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-warning">
                        <div class="card-body">
                            <div class="media">
									<span class="me-3">
										<i class="la la-user"></i>
									</span>
                                <div class="media-body text-white">
                                    <p class="mb-1">New Students</p>
                                    <h3 class="text-white">245</h3>
                                    <div class="progress mb-2 bg-white">
                                        <div class="progress-bar progress-animated bg-white" style="width: 50%"></div>
                                    </div>
                                    <small>50% Increase in 25 Days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-secondary">
                        <div class="card-body">
                            <div class="media">
									<span class="me-3">
										<i class="la la-graduation-cap"></i>
									</span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Course</p>
                                    <h3 class="text-white">28</h3>
                                    <div class="progress mb-2 bg-white">
                                        <div class="progress-bar progress-animated bg-white" style="width: 76%"></div>
                                    </div>
                                    <small>76% Increase in 20 Days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-danger">
                        <div class="card-body">
                            <div class="media">
									<span class="me-3">
										<i class="la la-dollar"></i>
									</span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Fees Collection</p>
                                    <h3 class="text-white">25160$</h3>
                                    <div class="progress mb-2 bg-white">
                                        <div class="progress-bar progress-animated bg-white" style="width: 30%"></div>
                                    </div>
                                    <small>30% Increase in 30 Days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
