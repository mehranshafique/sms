@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('pickup.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('pickup.parent_subtitle') }}</p>
                </div>
            </div>
        </div>

        @php
            $isAdmin = auth()->user()->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
        @endphp

        {{-- ADMIN SEARCH SECTION --}}
        @if($isAdmin)
        <div class="row mb-5">
            <div class="col-xl-12">
                <div class="card shadow-sm border-primary" style="border-width: 1px;">
                    <div class="card-header bg-soft-primary">
                        <h5 class="card-title text-primary"><i class="fa fa-search me-2"></i> Find Any Student</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-3 mb-md-0">
                                <label class="form-label fw-bold">Search Student (Name or ID)</label>
                                {{-- Added class 'admin-search-select' for targeting --}}
                                <select id="adminStudentSearch" class="admin-search-select" style="width: 100%;">
                                    <option value="">Type to search...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary w-100" id="adminGenerateBtn" disabled>
                                    <i class="fa fa-qrcode me-2"></i> {{ __('pickup.generate_qr') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Hidden container for dynamically generated card --}}
                    <div id="adminQrResult" class="card-footer bg-white d-none text-center p-4">
                        <h4 id="adminStudentName" class="text-dark mb-1"></h4>
                        <p id="adminStudentAdm" class="text-muted mb-3"></p>
                        
                        <div class="qr-box bg-light rounded p-3 mb-3 d-inline-block border">
                            {{-- Added crossOrigin attribute for image copy/download --}}
                            <img id="adminQrImg" src="" class="img-fluid" style="max-width: 250px;" crossOrigin="Anonymous">
                            <div class="mt-2 small text-danger fw-bold">
                                {{ __('pickup.expires_at') }}: <span id="adminExpiry"></span>
                            </div>
                            {{-- Download/Copy Buttons --}}
                            <div class="mt-3 d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadQr('adminQrImg')" title="Download">
                                    <i class="fa fa-download"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyQr('adminQrImg')" title="Copy to Clipboard">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- PARENT LIST --}}
        @if(!$isAdmin || ($isAdmin && count($students) > 0))
            <h5 class="text-muted text-uppercase fs-12 font-w600 mb-3 ps-3">My Students</h5>
            <div class="row justify-content-center">
                @forelse($students as $student)
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                    <div class="card overflow-hidden shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            
                            <div class="mb-3">
                                @if($student->student_photo)
                                    <img src="{{ asset('storage/'.$student->student_photo) }}" class="rounded-circle border border-3 border-white shadow-sm" width="100" height="100" style="object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width:100px; height:100px; font-size: 30px; font-weight:bold; color: #aaa;">
                                        {{ substr($student->first_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            
                            <h4 class="mt-2 mb-1 text-dark">{{ $student->full_name }}</h4>
                            <p class="text-muted mb-3">{{ $student->admission_number }}</p>
                            
                            <div id="qr-container-{{ $student->id }}" class="qr-box bg-light rounded p-3 mb-3 d-none">
                                <img id="qr-img-{{ $student->id }}" src="" class="img-fluid" style="max-width: 200px;" crossOrigin="Anonymous">
                                <div class="mt-2 small text-danger fw-bold">
                                    {{ __('pickup.expires_at') }}: <span id="expiry-{{ $student->id }}"></span>
                                </div>
                                {{-- Download/Copy Buttons --}}
                                <div class="mt-2 d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadQr('qr-img-{{ $student->id }}')" title="Download">
                                        <i class="fa fa-download"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyQr('qr-img-{{ $student->id }}')" title="Copy to Clipboard">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary w-100 generate-btn" data-id="{{ $student->id }}">
                                <i class="fa fa-qrcode me-2"></i> {{ __('pickup.generate_qr') }}
                            </button>

                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle me-2"></i> {{ __('pickup.no_students_linked') }}
                    </div>
                </div>
                @endforelse
            </div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- Select2 for Admin Search --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Fix Select2 height to match Bootstrap 5 inputs */
    .select2-container .select2-selection--single {
        height: 45px !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 45px !important;
    }
    /* Hide native select to prevent double inputs if theme applies default styling */
    select.admin-search-select {
        display: none !important;
    }
</style>

<script>
    // --- QR Helper Functions (Global Scope) ---
    async function downloadQr(imgId) {
        try {
            const img = document.getElementById(imgId);
            const src = img.src;
            // Fetch as blob to handle cross-origin download
            const blob = await (await fetch(src)).blob();
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = "pickup-qr.png";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch(e) {
            console.error(e);
            Swal.fire({ toast: true, icon: 'error', title: 'Download failed', position: 'top-end', showConfirmButton: false, timer: 1500 });
        }
    }

    async function copyQr(imgId) {
        try {
            const img = document.getElementById(imgId);
            const src = img.src;
            const blob = await (await fetch(src)).blob();
            await navigator.clipboard.write([
                new ClipboardItem({
                    [blob.type]: blob
                })
            ]);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'QR Copied!', showConfirmButton: false, timer: 1500 });
        } catch (err) {
            console.error(err);
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Failed to copy', showConfirmButton: false, timer: 1500 });
        }
    }

    $(document).ready(function() {
        
        // --- 1. Parent/Default Generation Logic ---
        $('.generate-btn').click(function() {
            let btn = $(this);
            let studentId = btn.data('id');
            let originalText = btn.html();

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('pickup.generating') }}...');

            generateQrAjax(studentId, 
                function(res) {
                    $('#qr-img-' + studentId).attr('src', res.qr_url);
                    $('#expiry-' + studentId).text(res.expires_at);
                    $('#qr-container-' + studentId).removeClass('d-none');
                    btn.hide(); 
                    
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('pickup.qr_generated_title') }}",
                        text: "{{ __('pickup.qr_generated_text') }}",
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                function() {
                    btn.prop('disabled', false).html(originalText);
                }
            );
        });

        // --- 2. Admin Search Logic ---
        @if($isAdmin)
            var $adminSelect = $('#adminStudentSearch');
            
            // Forcefully destroy other plugins that might attach to this select
            if($adminSelect.data('selectpicker')) $adminSelect.selectpicker('destroy');
            if($.fn.niceSelect) $adminSelect.niceSelect('destroy');

            // Init Select2 with AJAX
            $adminSelect.select2({
                placeholder: "Search student by name or ID...",
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: "{{ route('students.index') }}", // Uses existing DataTable endpoint
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: { value: params.term }, // DataTables search param
                            start: 0,
                            length: 10
                        };
                    },
                    processResults: function (data) {
                        // Map DataTables array to Select2 format
                        return {
                            results: $.map(data.data, function (item) {
                                // Extract raw text from HTML columns if needed
                                let tempDiv = document.createElement("div");
                                tempDiv.innerHTML = item.details;
                                let name = tempDiv.querySelector('a') ? tempDiv.querySelector('a').innerText : 'Unknown';
                                
                                let adm = item.admission_number || ''; 

                                return {
                                    text: name + ' (' + adm + ')',
                                    id: item.id,
                                    student_name: name,
                                    student_adm: adm
                                }
                            })
                        };
                    },
                    cache: true
                }
            });

            // Enable Generate Button on Selection
            $adminSelect.on('select2:select', function (e) {
                $('#adminGenerateBtn').prop('disabled', false);
                $('#adminQrResult').addClass('d-none'); // Hide previous result
            });
            
            // Disable on clear
            $adminSelect.on('select2:clear', function (e) {
                $('#adminGenerateBtn').prop('disabled', true);
                $('#adminQrResult').addClass('d-none');
            });

            // Handle Admin Generate Click
            $('#adminGenerateBtn').click(function() {
                let data = $adminSelect.select2('data')[0];
                if(!data) return;

                let btn = $(this);
                let originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

                generateQrAjax(data.id, 
                    function(res) {
                        // Populate Admin Result Card
                        $('#adminStudentName').text(data.student_name);
                        $('#adminStudentAdm').text(data.student_adm);
                        $('#adminQrImg').attr('src', res.qr_url);
                        $('#adminExpiry').text(res.expires_at);
                        
                        $('#adminQrResult').removeClass('d-none');
                        
                        btn.prop('disabled', false).html(originalText);
                    },
                    function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                );
            });
        @endif

        // --- Helper Function ---
        function generateQrAjax(studentId, onSuccess, onError) {
            $.ajax({
                url: "{{ route('pickups.generate_parent') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    student_id: studentId
                },
                success: function(res) {
                    if(res.success) {
                        onSuccess(res);
                    }
                },
                error: function(err) {
                    if(onError) onError();
                    Swal.fire("{{ __('pickup.error') }}", "{{ __('pickup.qr_generation_failed') }}", 'error');
                }
            });
        }
    });
</script>
@endsection