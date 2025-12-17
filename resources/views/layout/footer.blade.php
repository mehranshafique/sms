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
            var $lastClickedBtn = null;

            // Track clicks to identify the trigger button
            $(document).on('click', 'button[type="submit"], input[type="submit"], a.btn', function() {
                $lastClickedBtn = $(this);
            });

            // Handle AJAX Start
            $(document).ajaxSend(function(event, jqXHR, settings) {
                var $trigger = null;

                // 1. Try finding based on active element
                var $active = $(document.activeElement);
                if ($active.length && ($active.is('button') || $active.is('input[type="submit"]'))) {
                    $trigger = $active;
                }
                
                // 2. Fallback to last clicked
                if ((!$trigger || !$trigger.length) && $lastClickedBtn) {
                    $trigger = $lastClickedBtn;
                }

                // If valid trigger found and not already loading
                if ($trigger && $trigger.length && !$trigger.data('is-loading')) {
                    // Save original state
                    $trigger.data('is-loading', true);
                    $trigger.data('original-html', $trigger.html());
                    if ($trigger.is('input')) $trigger.data('original-val', $trigger.val());

                    // Set loading state
                    $trigger.addClass('disabled').attr('disabled', true);
                    
                    if ($trigger.is('input')) {
                        $trigger.val('Processing...');
                    } else {
                        $trigger.html('<i class="fas fa-circle-notch fa-spin me-2"></i> Processing...');
                    }

                    // Attach to settings so we can find it in ajaxComplete
                    settings.triggerElement = $trigger;
                }
            });

            // Handle AJAX Complete (fires on Success AND Error)
            $(document).ajaxComplete(function(event, jqXHR, settings) {
                var $trigger = settings.triggerElement;
                if ($trigger && $trigger.length) {
                    // IMMEDIATE RESET: No setTimeout needed if you want it to stop before alert
                    if ($trigger.is('input')) {
                        $trigger.val($trigger.data('original-val'));
                    } else {
                        $trigger.html($trigger.data('original-html'));
                    }
                    
                    $trigger.removeClass('disabled').attr('disabled', false);
                    $trigger.data('is-loading', false);
                    $lastClickedBtn = null;
                }
            });
        });
    </script>


    @yield('js')
    
</body>
</html>