<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Title -->
	<title>@yield('title', 'Authentication | E-Digitex')</title>

	<!-- Meta -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">

	<!-- STYLESHEETS -->
	<link href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link class="main-css" rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    @yield('styles')
</head>
<body>
    <div class="fix-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-6">
                    <div class="card mb-0 h-auto">
                        <div class="card-body">
                            <div class="text-center mb-2">
                                <a href="{{ url('/') }}">
                                    <img src="https://e-digitex.com/public/images/smsslogonew.png" alt="Logo" style="width: 150px !important;">
                                </a>
                            </div>
                            
                            {{-- INLINE ALERT MESSAGES --}}
                            @if (session('status') || session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('status') ?? session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            
                            @yield('content')

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
	<script src="{{ asset('vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- 1. Session-Based Alerts (For Standard Form Submissions) ---
            
            // Success Message (session('status') or session('success'))
            @if (session('status') || session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "{{ session('status') ?? session('success') }}",
                    confirmButtonColor: '#3085d6',
                });
            @endif

            // Error Message (session('error'))
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#d33',
                });
            @endif

            // Validation Errors (session('errors'))
            @if ($errors->any())
                let errorHtml = '<ul style="text-align: left; margin-left: 1rem;">';
                @foreach ($errors->all() as $error)
                    errorHtml += '<li>{{ $error }}</li>';
                @endforeach
                errorHtml += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: errorHtml,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
            @endif


            // --- 2. AJAX Form Submission Handler (Only for forms with class 'ajax-form') ---
            const ajaxForms = document.querySelectorAll('.ajax-form');
            ajaxForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                    const formData = new FormData(form);
                    const url = form.getAttribute('action');

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;

                        if (status === 200 || status === 201 || status === 204) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: body.message || 'Operation successful!',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                if (body.redirect) window.location.href = body.redirect;
                                else if (body.two_factor) window.location.href = "{{ route('login') }}"; 
                                else window.location.href = "{{ url('/dashboard') }}"; 
                            });
                        } else if (status === 422) {
                            let errorMessages = '';
                            for (const [field, messages] of Object.entries(body.errors)) {
                                const input = form.querySelector(`[name="${field}"]`);
                                if (input) input.classList.add('is-invalid');
                                errorMessages += `<li style="text-align:left">${messages[0]}</li>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: `<ul style="list-style:none; padding:0; margin:0;">${errorMessages}</ul>`,
                                confirmButtonColor: '#d33'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: body.message || 'An unexpected error occurred.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        // If JSON parse failed, likely HTML returned (redirect)
                        Swal.fire({
                            icon: 'error',
                            title: 'System Error',
                            text: 'Unexpected response from server. Please reload and try again.',
                            confirmButtonColor: '#d33'
                        });
                    });
                });
            });

            // --- 3. Password Toggle ---
            const toggles = document.querySelectorAll('.show-pass');
            toggles.forEach(function(toggle) {
                const container = toggle.closest('.position-relative');
                if(!container) return;
                const input = container.querySelector('input');
                if (!input) return;

                function updateIcons(isShown) {
                    const eyeOn = toggle.querySelector('.fa-eye');
                    const eyeOff = toggle.querySelector('.fa-eye-slash');
                    if(eyeOn && eyeOff) {
                        eyeOn.style.display = isShown ? 'inline' : 'none';
                        eyeOff.style.display = isShown ? 'none' : 'inline';
                    }
                }
                updateIcons(false);

                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isText = input.type === 'text';
                    input.type = isText ? 'password' : 'text';
                    updateIcons(!isText);
                });
            });
        });

        // --- 4. FIX: Bootstrap Select "Nothing selected" ---
        window.addEventListener('load', function() {
            if (typeof jQuery !== 'undefined' && jQuery.fn.selectpicker) {
                jQuery.fn.selectpicker.defaults.noneSelectedText = 'Select Option';
                jQuery('.default-select, .selectpicker').each(function() {
                    if (!jQuery(this).attr('title')) {
                        jQuery(this).attr('title', 'Select Option');
                    }
                    jQuery(this).selectpicker('refresh');
                });
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>