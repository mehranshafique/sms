<div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <b>Integrale Plus</b> and <b>Digitex</b></p>
                <p class="small mb-0">
                    <a href="{{ route('help.index') }}" target="_blank" rel="noopener">{{ __('help.nav_help') }}</a>
                    &nbsp;·&nbsp;
                    <a href="{{ route('community.index') }}" target="_blank" rel="noopener">{{ __('help.nav_community') }}</a>
                    &nbsp;·&nbsp;
                    <a href="{{ route('pay.lookup') }}" target="_blank" rel="noopener">{{ __('help.nav_pay') }}</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
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

    <style>
        .bootstrap-select .dropdown-menu { z-index: 1065 !important; }
        .content-body .card,
        .content-body .card-body,
        .content-body .table-responsive { overflow: visible; }
    </style>
    <!-- Global Init Script -->
    <script>
        $(document).ready(function() {
            // 1. Initialize Material Date Picker
            if(jQuery().bootstrapMaterialDatePicker) {
                jQuery('.datepicker, .datepicker-default').bootstrapMaterialDatePicker({
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
            window.digitexReinitSelectPickers = function() {
                if (typeof jQuery.fn.selectpicker === 'undefined') {
                    return;
                }
                jQuery('.default-select, .multi-select').each(function() {
                    var $el = jQuery(this);
                    if ($el.data('selectpicker')) {
                        $el.selectpicker('destroy');
                    }
                });
                jQuery('.default-select').selectpicker({
                    liveSearch: true,
                    size: 10,
                    container: 'body',
                    dropupAuto: false,
                });
                jQuery('.multi-select').selectpicker({
                    liveSearch: true,
                    size: 10,
                    width: '100%',
                    container: 'body',
                    dropupAuto: false,
                });
                jQuery('.default-select, .multi-select').each(function() {
                    if (!jQuery(this).attr('title')) {
                        jQuery(this).attr('title', '');
                    }
                }).selectpicker('refresh');
            };

            if (typeof jQuery.fn.selectpicker !== 'undefined' && jQuery.fn.selectpicker.defaults) {
                jQuery.fn.selectpicker.defaults.noneSelectedText = '';
                jQuery.fn.selectpicker.defaults.noneResultsText = 'No results found';
            }

            setTimeout(function() {
                if (typeof window.digitexReinitSelectPickers === 'function') {
                    window.digitexReinitSelectPickers();
                }
            }, 100);

            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    timeOut: 4000,
                };
            }

            // SweetAlert: close bootstrap-select dropdowns so "Nothing selected" does not appear in modals
            if (typeof Swal !== 'undefined' && Swal.fire) {
                var digitexSwalFire = Swal.fire.bind(Swal);
                Swal.fire = function() {
                    jQuery('.bootstrap-select.open, .bootstrap-select.show').removeClass('open show');
                    jQuery('.bootstrap-select .dropdown-menu').removeClass('show');
                    return digitexSwalFire.apply(Swal, arguments);
                };
            }

            window.digitexNotifySuccess = function(message, title) {
                if (typeof window.toastr !== 'undefined') {
                    window.toastr.success(message);
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: title || @json(__('attendance.success')), text: message, timer: 2200, showConfirmButton: false });
                }
            };

            window.digitexNotifyError = function(message, title) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: title || @json(__('attendance.error_occurred')), text: message });
                } else {
                    alert(message);
                }
            };

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
                if (!settings.skipDigitexLoader) {
                    $('#digitex-ajax-loader').addClass('is-visible');
                }
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
                $('#digitex-ajax-loader').removeClass('is-visible');
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

            $(document).ajaxError(function() {
                $('#digitex-ajax-loader').removeClass('is-visible');
            });

            // Dismiss setup configuration alerts
            $(document).on('click', '.setup-alert-dismiss', function() {
                var $alert = $(this).closest('.setup-config-alert');
                var key = $(this).data('alert-key');
                $alert.fadeOut(200, function() {
                    $(this).remove();
                    if ($('#setup-alerts-wrap .setup-config-alert').length === 0) {
                        $('#setup-alerts-wrap').fadeOut(150, function() { $(this).remove(); });
                    }
                });
                $.post('{{ route('configuration.setup_alert.dismiss') }}', {
                    _token: '{{ csrf_token() }}',
                    key: key
                }).fail(function() { /* silent */ });
            });
        });
    </script>

    {{-- Global flash → toastr / SweetAlert --}}
    @if(session('success') || session('error') || session('warning') || session('info'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            @if(session('success'))
                if (typeof toastr !== 'undefined') {
                    toastr.success(@json(session('success')));
                } else {
                    Swal.fire({ icon: 'success', title: @json(__('attendance.success')), text: @json(session('success')), timer: 2800, showConfirmButton: false });
                }
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: @json(__('attendance.error_occurred')), text: @json(session('error')) });
            @endif
            @if(session('warning'))
                if (typeof toastr !== 'undefined') { toastr.warning(@json(session('warning'))); }
            @endif
            @if(session('info'))
                if (typeof toastr !== 'undefined') { toastr.info(@json(session('info'))); }
            @endif
        });
    </script>
    @endif

    @auth
        @include('layout.partials.in-app-notification-scripts')
    @endauth

    @yield('js')

    @if(has_ai_access())
        @include('ai.partials.floating-widget')
        @include('ai.partials.embed-scripts')
    @endif
    
</body>
</html>