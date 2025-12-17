<div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="http://dexignlab.com/" target="_blank">DexignLab</a> 2023</p>
            </div>
        </div>
        <!--**********************************
            Footer end
        ***********************************-->

    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
	<script src="{{ asset('vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>

	<!-- Chart sparkline plugin files -->
    <script src="{{ asset('vendor/jquery-sparkline/jquery.sparkline.min.js') }}"></script>
	<script src="{{ asset('js/plugins-init/sparkline-init.js') }}"></script>

    <script src="{{ asset('vendor/select2/js/select2.full.min.js')  }}"></script>
    <script src="{{ asset('js/plugins-init/select2-init.js')  }}"></script>

	<!-- Chart Morris plugin files -->
    <script src="{{ asset('vendor/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('vendor/morris/morris.min.js') }}"></script>

    <!-- Init file -->
    <script src="{{ asset('js/plugins-init/widgets-script-init.js') }}"></script>

    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js')  }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.responsive.min.js')  }}"></script>
    <script src="{{ asset('js/plugins-init/datatables.init.js')  }}"></script>

	<!-- Svganimation scripts -->
    <script src="{{ asset('vendor/svganimation/vivus.min.js') }}"></script>
    <script src="{{ asset('vendor/svganimation/svg.animation.js') }}"></script>

	<!-- Demo scripts -->
    <script src="{{ asset('js/dashboard/dashboard.js') }}"></script>

	<script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/dlabnav-init.js') }}"></script>


    <script src="{{asset('vendor/moment/moment.min.js')}}"></script>
    <script src="{{asset('vendor/bootstrap-daterangepicker/daterangepicker.js')}}"></script>
    <script src="{{ asset('vendor/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js')  }}"></script>
    <!-- pickdate -->
    <script src="{{asset('vendor/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendor/pickadate/picker.time.js')}}"></script>
    <script src="{{asset('vendor/pickadate/picker.date.js')}}"></script>
    <script src="{{asset('js/plugins-init/bs-daterange-picker-init.js')}}"></script>
    <script src="{{asset('js/plugins-init/material-date-picker-init.js')}}"></script>
    <script src="{{asset('js/plugins-init/pickadate-init.js')}}"></script>

    <!-- Global Button Processing Script -->
    <script>
        $(document).ready(function() {
            // Keep track of the last clicked button for AJAX requests
            var $lastClickedBtn = null;
            $(document).on('click', 'button, a.btn, input[type="submit"]', function() {
                $lastClickedBtn = $(this);
            });

            // 1. Handle Global AJAX Requests (Start)
            $(document).ajaxSend(function(event, jqXHR, settings) {
                // Use activeElement or last clicked button if activeElement isn't specific
                var $trigger = $(document.activeElement);
                if (!$trigger.length || (!$trigger.is('button') && !$trigger.is('a.btn') && !$trigger.is('input[type="submit"]'))) {
                    if ($lastClickedBtn) {
                        $trigger = $lastClickedBtn;
                    }
                }

                // If it's a button/link/input and not already loading
                if (($trigger.is('button') || $trigger.is('a.btn') || $trigger.is('input[type="submit"]')) && !$trigger.data('is-loading')) {
                    
                    // Ignore buttons inside DataTables pagination/sorting as they handle themselves differently usually,
                    // but if you want them to show loading, keep this. 
                    // Ignore bulk delete if handled separately, but this makes it safer.
                    
                    // Mark as loading
                    $trigger.data('is-loading', true);
                    $trigger.data('original-html', $trigger.html()); // Store original content
                    
                    // Add disabled state
                    $trigger.addClass('disabled').attr('disabled', true);
                    
                    // Add Spinner
                    // Check if it's an input button (value attribute) or regular button (html)
                    if ($trigger.is('input')) {
                        $trigger.data('original-val', $trigger.val());
                        $trigger.val('Processing...');
                    } else {
                        $trigger.html('<i class="fas fa-circle-notch fa-spin me-2"></i> Processing...');
                    }
                    
                    // Attach the trigger to the xhr object so we can revert it later
                    settings.triggerElement = $trigger;
                }
            });

            // 2. Handle Global AJAX Requests (Complete)
            $(document).ajaxComplete(function(event, jqXHR, settings) {
                var $trigger = settings.triggerElement;
                if ($trigger && $trigger.length) {
                    setTimeout(function() { // Small delay to ensure user sees the completion if it was super fast
                        // Revert HTML/Value
                        if ($trigger.is('input')) {
                            $trigger.val($trigger.data('original-val'));
                        } else {
                            $trigger.html($trigger.data('original-html'));
                        }
                        
                        // Re-enable
                        $trigger.removeClass('disabled').attr('disabled', false);
                        $trigger.data('is-loading', false);
                        $lastClickedBtn = null; // Reset
                    }, 300);
                }
            });

            // 3. Handle Standard Form Submits (Non-AJAX)
            $('form').on('submit', function() {
                var $btn = $(this).find('button[type="submit"], input[type="submit"]');
                
                // If form is valid (if using browser validation)
                if (this.checkValidity()) {
                    if ($btn.length && !$btn.hasClass('disabled')) {
                        $btn.data('original-html', $btn.html());
                        $btn.addClass('disabled').attr('disabled', true);
                        if ($btn.is('input')) {
                            $btn.val('Processing...');
                        } else {
                            $btn.html('<i class="fas fa-circle-notch fa-spin me-2"></i> Processing...');
                        }
                    }
                }
            });
        });
    </script>

    @yield('js')
    
</body>
</html>