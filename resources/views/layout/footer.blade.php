<div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <b>Integrale Plus</b> and <b>Digitex</b></p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
	<script src="{{ asset('vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>

    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js')  }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.responsive.min.js')  }}"></script>
    <script src="{{ asset('js/plugins-init/datatables.init.js')  }}"></script>

	<script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/dlabnav-init.js') }}"></script>

    <!-- Date & Time Pickers JS -->
    <script src="{{asset('vendor/moment/moment.min.js')}}"></script>
    <script src="{{asset('vendor/bootstrap-daterangepicker/daterangepicker.js')}}"></script>
    <script src="{{ asset('vendor/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js')  }}"></script>
    <script src="{{ asset('vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
    <script src="{{asset('vendor/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendor/pickadate/picker.time.js')}}"></script>
    <script src="{{asset('vendor/pickadate/picker.date.js')}}"></script>

    <!-- Global Init Script -->
    <script>
        $(document).ready(function() {
            // 1. Initialize Material Date Picker
            if(jQuery().bootstrapMaterialDatePicker) {
                $('.datepicker').bootstrapMaterialDatePicker({
                    weekStart: 0,
                    time: false,
                    format: 'YYYY-MM-DD'
                });
            }

            // 2. Initialize Clock Picker
            if(jQuery().clockpicker) {
                 $('.timepicker').clockpicker({
                    placement: 'bottom',
                    align: 'left',
                    donetext: 'Done',
                    autoclose: true
                });
            }

            // 3. Configure Bootstrap Select (Global Fallback)
            setTimeout(function() {
                if(jQuery().selectpicker) {
                    $('.default-select').selectpicker({
                        liveSearch: true,
                        size: 10
                    });
                    
                    // Also initialize multi-selects globally just in case
                    $('.multi-select').selectpicker({
                        liveSearch: true,
                        size: 10,
                        width: '100%'
                    });

                    $('.default-select, .multi-select').selectpicker('refresh');
                }
            }, 100);

            // 4. Global Button Loading State
            var $lastClickedBtn = null;

            $(document).on('click', 'button[type="submit"], input[type="submit"], a.btn', function() {
                var $btn = $(this);
                $lastClickedBtn = $btn;
                
                if (!$btn.data('original-html') && !$btn.data('original-val')) {
                    if ($btn.is('input')) {
                        $btn.data('original-val', $btn.val());
                    } else {
                        $btn.data('original-html', $btn.html());
                    }
                }
            });

            $(document).ajaxSend(function(event, jqXHR, settings) {
                var $trigger = null;
                var $active = $(document.activeElement);
                
                if ($active.length && ($active.is('button') || $active.is('input[type="submit"]'))) {
                    $trigger = $active;
                }
                if ((!$trigger || !$trigger.length) && $lastClickedBtn) {
                    $trigger = $lastClickedBtn;
                }

                if ($trigger && $trigger.length && !$trigger.data('is-loading')) {
                    $trigger.data('is-loading', true);
                    
                    if (!$trigger.data('original-html') && !$trigger.data('original-val')) {
                        if ($trigger.is('input')) {
                            $trigger.data('original-val', $trigger.val());
                        } else {
                            $trigger.data('original-html', $trigger.html());
                        }
                    }

                    $trigger.addClass('disabled').attr('disabled', true);
                    
                    if ($trigger.is('input')) {
                        $trigger.val('Processing...');
                    } else {
                        // Use a simple spinner to avoid layout shift
                        $trigger.html('<i class="fa fa-spinner fa-spin"></i>');
                    }
                    settings.triggerElement = $trigger;
                }
            });

            $(document).ajaxComplete(function(event, jqXHR, settings) {
                var $trigger = settings.triggerElement;
                if ($trigger && $trigger.length) {
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