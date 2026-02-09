@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('pickup.scanner_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('pickup.scanner_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-10">
                <div class="card shadow-lg border-0" style="border-radius: 20px;">
                    <div class="card-body p-4 text-center">
                        
                        {{-- Camera Viewport --}}
                        <div id="reader" class="w-100 bg-light rounded mb-3 overflow-hidden" style="min-height: 350px; border: 2px dashed #ddd;"></div>
                        
                        {{-- Status Messages --}}
                        <div id="statusMessage" class="alert d-none"></div>

                        {{-- Manual Entry Option --}}
                        <div class="input-group mt-4">
                            <input type="text" id="manualCode" class="form-control" placeholder="{{ __('pickup.enter_code') }}">
                            <button class="btn btn-primary" onclick="processCode($('#manualCode').val())">{{ __('pickup.validate_btn') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Result Modal (Simulated as card for clarity) --}}
            <div class="col-xl-4 col-lg-4 col-md-10 d-none" id="resultCard">
                <div class="card border-info border-2 shadow-lg" style="border-radius: 20px;">
                    <div class="card-header bg-info text-white text-center">
                        <h5 class="text-white mb-0"><i class="fa fa-info-circle me-2"></i> {{ __('pickup.verification_success') }}</h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <img id="studentPhoto" src="" class="rounded-circle border border-3 border-white shadow-sm mb-3" width="120" height="120" style="object-fit: cover;">
                        
                        <h3 class="fw-bold mb-1" id="studentName">Student Name</h3>
                        <p class="text-muted mb-3">{{ __('pickup.pickup_by') }}: <strong id="pickupBy" class="text-dark">Parent</strong></p>
                        
                        <div class="alert alert-warning border-0">
                            <i class="fa fa-clock-o me-2"></i> {{ __('pickup.waiting_approval') }}
                        </div>

                        <button class="btn btn-outline-primary w-100 mt-2" onclick="resetScanner()">{{ __('pickup.scan_next') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const csrfToken = "{{ csrf_token() }}";
    const scanUrl = "{{ route('pickups.process') }}";
    let html5QrcodeScanner;

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.clear();
        processCode(decodedText);
    }

    function processCode(code) {
        $('#statusMessage').removeClass('d-none alert-danger alert-success').addClass('alert-info').text('Processing...');
        
        $.ajax({
            url: scanUrl,
            type: 'POST',
            data: { _token: csrfToken, qr_code: code },
            success: function(res) {
                if(res.success) {
                    $('#studentName').text(res.student.name);
                    $('#pickupBy').text(res.student.pickup_by);
                    if(res.student.photo_url) $('#studentPhoto').attr('src', res.student.photo_url);
                    
                    $('#resultCard').removeClass('d-none');
                    $('#statusMessage').addClass('d-none');
                }
            },
            error: function(err) {
                let msg = err.responseJSON ? err.responseJSON.message : 'Failed';
                $('#statusMessage').removeClass('d-none alert-info').addClass('alert-danger').text(msg);
                setTimeout(() => { 
                    $('#statusMessage').addClass('d-none'); 
                    initScanner(); 
                }, 3000);
            }
        });
    }

    function resetScanner() {
        $('#resultCard').addClass('d-none');
        $('#manualCode').val('');
        initScanner();
    }

    function initScanner() {
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 }, false);
        html5QrcodeScanner.render(onScanSuccess, (err) => {});
    }

    $(document).ready(initScanner);
</script>
@endsection