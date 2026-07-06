"use strict";
var dlabSettingsOptions = {};

function getUrlParams(dParam) {
	var dPageURL = window.location.search.substring(1),
		dURLVariables = dPageURL.split('&'),
		dParameterName,
		i;

	for (i = 0; i < dURLVariables.length; i++) {
		dParameterName = dURLVariables[i].split('=');

		if (dParameterName[0] === dParam) {
			return dParameterName[1] === undefined ? true : decodeURIComponent(dParameterName[1]);
		}
	}
}

function getNavLayoutPreference() {
	var pref = getCookie('nav_layout');
	return pref === 'horizontal' ? 'horizontal' : 'vertical';
}

function effectiveNavLayout() {
	var pref = getNavLayoutPreference();
	return (pref === 'horizontal' && window.innerWidth >= 1200) ? 'horizontal' : 'vertical';
}

function applyNavLayoutSettings() {
	var pref = getNavLayoutPreference();
	dlabSettingsOptions.layout = effectiveNavLayout();

	if (typeof jQuery !== 'undefined') {
		jQuery('body').attr('data-user-nav-layout', pref);
	}

	if (typeof dlabSettings === 'function') {
		new dlabSettings(dlabSettingsOptions);
	}

	if (typeof window.updateNavLayoutToggleUI === 'function') {
		window.updateNavLayoutToggleUI(pref);
	}

	if (typeof window.syncHorizontalNav === 'function') {
		window.syncHorizontalNav();
	}
}

(function($) {

	var direction = getUrlParams('dir');

	var version = getCookie('version') || "light";

	dlabSettingsOptions = {
		typography: "poppins",
		version: version,
		layout: effectiveNavLayout(),
		primary: "color_1",
		headerBg: "color_1",
		navheaderBg: "color_3",
		sidebarBg: "color_1",
		sidebarStyle: "full",
		sidebarPosition: "fixed",
		headerPosition: "fixed",
		containerLayout: "full",
		direction: 'ltr',
	};

	applyNavLayoutSettings();

	jQuery(window).on('resize', function () {
		dlabSettingsOptions.containerLayout = $('#container_layout').val();
		applyNavLayoutSettings();
	});

	if (direction == 'rtl' || body.attr('direction') == 'rtl') {
		direction = 'rtl';
		jQuery('.main-css').attr('href', 'css/style-rtl.css');
	} else {
		direction = 'ltr';
		jQuery('.main-css').attr('href', 'css/style.css');
	}

	if (jQuery(".dlab-theme-mode").length > 0) {
		jQuery('.dlab-theme-mode').on('click', function () {
			jQuery(this).toggleClass('active');

			if (jQuery(this).hasClass('active')) {
				jQuery('body').attr('data-theme-version', 'dark');
				setCookie('version', 'dark');
				jQuery('#theme_version').val('dark');
			} else {
				jQuery('body').attr('data-theme-version', 'light');
				setCookie('version', 'light');
				jQuery('#theme_version').val('light');
			}
			$('.default-select').selectpicker('refresh');
		});

		jQuery('body').attr('data-theme-version', version);
		jQuery('.dlab-theme-mode').removeClass('active');

		setTimeout(function () {
			if (jQuery('body').attr('data-theme-version') === "dark") {
				jQuery('.dlab-theme-mode').addClass('active');
			}
		}, 1500);
	}

})(jQuery);

function setCookie(cname, cvalue, exhours) {
	var d = new Date();
	d.setTime(d.getTime() + (30 * 60 * 1000));
	var expires = "expires=" + d.toString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function setNavLayoutCookie(cvalue) {
	var d = new Date();
	d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
	document.cookie = 'nav_layout=' + cvalue + ';expires=' + d.toUTCString() + ';path=/';
}

function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function deleteCookie(cname) {
	document.cookie = cname + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT" + ";path=/";
}

function deleteAllCookie(reload = true) {
	jQuery.each(themeOptionArr, function (optionKey, optionValue) {
		deleteCookie(optionKey);
	});
	if (reload) {
		location.reload();
	}
}
