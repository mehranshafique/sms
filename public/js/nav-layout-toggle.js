"use strict";

(function ($) {
	function updateNavLayoutToggleUI(pref) {
		var $btn = $('.dlab-nav-layout-toggle');
		if (!$btn.length) {
			return;
		}

		var isHorizontal = pref === 'horizontal';
		$btn.toggleClass('is-horizontal-pref', isHorizontal);
		$btn.attr(
			'title',
			isHorizontal
				? ($btn.data('label-sidebar') || 'Sidebar menu')
				: ($btn.data('label-horizontal') || 'Top menu')
		);
		$btn.attr('aria-pressed', isHorizontal ? 'true' : 'false');
	}

	window.updateNavLayoutToggleUI = updateNavLayoutToggleUI;

	$(document).ready(function () {
		updateNavLayoutToggleUI(getNavLayoutPreference());

		$(document).on('click', '.dlab-nav-layout-toggle', function (e) {
			e.preventDefault();

			var pref = getNavLayoutPreference();
			var next = pref === 'horizontal' ? 'vertical' : 'horizontal';

			$('#main-wrapper').addClass('nav-layout-switching');

			setNavLayoutCookie(next);
			applyNavLayoutSettings();

			window.setTimeout(function () {
				$('#main-wrapper').removeClass('nav-layout-switching');
			}, 280);
		});
	});
})(jQuery);
