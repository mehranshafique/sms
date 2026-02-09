@extends('layout.layout')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white text-center d-block">
                    <h4 class="card-title text-white mb-0"><i class="la la-qrcode me-2"></i> {{ __('pickup.scanner_title') ?? 'Pickup Scanner' }}</h4>
                    <small class="text-white-50">Scan Parent's QR Code to Validate</small>
                </div>
                <div class="card-body p-4">
                    
                    {{-- Camera Viewport --}}
                    <div id="reader" class="w-100 border rounded mb-3 bg-light" style="min-height: 300px;"></div>
                    
                    {{-- Status Messages --}}
                    <div id="statusMessage" class="alert d-none text-center"></div>

                    {{-- Manual Entry Option --}}
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#manualEntry">
                            <i class="la la-keyboard"></i> Manual Entry
                        </button>
                    </div>
                    <div class="collapse mt-2" id="manualEntry">
                        <div class="input-group">
                            <input type="text" id="manualCode" class="form-control" placeholder="Enter Code (e.g. PKUP-...)">
                            <button class="btn btn-primary" onclick="processCode($('#manualCode').val())">Check</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Success Result Card (Hidden by default) --}}
        <div class="col-md-6 col-lg-5" id="resultCard" style="display: none;">
            <div class="card border-success border-2 shadow-lg">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title text-white mb-0"><i class="la la-check-circle me-2"></i> Authorized Pickup</h5>
                </div>
                <div class="card-body text-center">
                    <img id="studentPhoto" src="" alt="Student" class="rounded-circle border border-3 border-success mb-3" width="120" height="120" style="object-fit: cover;">
                    
                    <h3 class="fw-bold mb-1" id="studentName">Student Name</h3>
                    <p class="text-muted mb-3" id="admissionNo">ADM-001</p>
                    
                    <div class="row text-start bg-light p-3 rounded mx-1">
                        <div class="col-6">
                            <small class="text-muted d-block">Class</small>
                            <span class="fw-bold text-dark" id="studentClass">Grade 5-A</span>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted d-block">Time</small>
                            <span class="fw-bold text-dark" id="scanTime">10:00 AM</span>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="la la-bell me-1"></i> Class Teacher has been notified.
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button class="btn btn-success w-100" onclick="resetScanner()">Scan Next <i class="la la-arrow-right ms-1"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Audio for feedback --}}
<audio id="successSound" src="{{ asset('assets/sounds/success.mp3') }}"></audio>
<audio id="errorSound" src="{{ asset('assets/sounds/error.mp3') }}"></audio>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const csrfToken = "{{ csrf_token() }}";
    const scanUrl = "{{ route('pickups.process') }}";
    let html5QrcodeScanner;

    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning temporarily
        html5QrcodeScanner.clear();
        processCode(decodedText);
    }

    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }

    function processCode(code) {
        $('#statusMessage').removeClass('d-none alert-danger alert-success').addClass('alert-info').text('Validating...');
        
        $.ajax({
            url: scanUrl,
            type: 'POST',
            data: { _token: csrfToken, qr_code: code },
            success: function(res) {
                if(res.success) {
                    playAudio('success');
                    showResult(res.student);
                    $('#statusMessage').addClass('d-none');
                }
            },
            error: function(err) {
                playAudio('error');
                let msg = err.responseJSON ? err.responseJSON.message : 'Unknown Error';
                $('#statusMessage').removeClass('d-none alert-info').addClass('alert-danger').html('<i class="la la-times-circle me-1"></i> ' + msg);
                
                // Restart scanner after 3 seconds on error
                setTimeout(() => {
                    initScanner();
                    $('#statusMessage').addClass('d-none');
                }, 3000);
            }
        });
    }

    function showResult(student) {
        $('#studentName').text(student.name);
        $('#admissionNo').text(student.admission_no);
        $('#studentClass').text(student.class);
        $('#scanTime').text(student.pickup_time);
        $('#studentPhoto').attr('src', student.photo_url);
        
        $('#resultCard').fadeIn();
        // Scanner already cleared in onScanSuccess
    }

    function resetScanner() {
        $('#resultCard').hide();
        $('#manualCode').val('');
        initScanner();
    }

    function initScanner() {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { fps: 10, qrbox: {width: 250, height: 250} },
            /* verbose= */ false
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function playAudio(type) {
        // Optional: Ensure you have audio files or remove this
        const audio = document.getElementById(type + 'Sound');
        if(audio) audio.play().catch(e => console.log('Audio play failed', e));
    }

    // Start on load
    $(document).ready(function() {
        initScanner();
    });
</script>
@endpush
@endsection