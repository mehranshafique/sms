"use strict";

(function ($) {
	var state = {
		grouped: false,
		originalHtml: null,
	};

	function isHorizontalActive() {
		return (
			typeof effectiveNavLayout === "function" &&
			effectiveNavLayout() === "horizontal"
		);
	}

	function updateContentOffset() {
		var root = document.documentElement;

		if (!isHorizontalActive()) {
			root.style.removeProperty("--horizontal-nav-height");
			return;
		}

		var nav = document.querySelector(".dlabnav");
		if (!nav) {
			return;
		}

		root.style.setProperty("--horizontal-nav-height", nav.offsetHeight + "px");
	}

	function isDashboardItem($li) {
		var href = ($li.find("> a").attr("href") || "").toLowerCase();
		return href.indexOf("dashboard") !== -1 || $li.find("> a .la-home").length > 0;
	}

	function isDecorativeLabel($li) {
		return $li.hasClass("nav-label") && ($li.find("br, span").length > 0 || $li.attr("style"));
	}

	function getLabelText($li) {
		var clone = $li.clone();
		clone.find("br, span").remove();
		return $.trim(clone.text());
	}

	function pickGroupIcon($items) {
		for (var i = 0; i < $items.length; i++) {
			var iconClass = $items[i].find("> a > i").first().attr("class");
			if (iconClass) {
				return iconClass;
			}
		}
		return "la la-layer-group";
	}

	function flushGroup($menu, label, items) {
		if (!items.length) {
			return;
		}

		if (items.length === 1 && !items[0].find("> ul").length) {
			$menu.append(items[0]);
			return;
		}

		var $group = $('<li class="nav-section-group mega-menu-lg"></li>');
		var $link = $(
			'<a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"></a>'
		);
		$link.append($('<i></i>').attr("class", pickGroupIcon(items)));
		$link.append($("<span></span>").addClass("nav-text").text(label));
		var $sub = $("<ul></ul>");

		items.forEach(function ($item) {
			$sub.append($item);
		});

		if ($sub.find(".mm-active").length) {
			$group.addClass("mm-active");
		}

		$group.append($link).append($sub);
		$menu.append($group);
	}

	function decorateHorizontalDropdownLinks() {
		$("#menu").find("li > ul a, li > ul li > ul a").each(function () {
			var $a = $(this);
			$a.removeClass("nav-dd-has-icon nav-dd-has-bullet");

			if ($a.children("i").first().length) {
				$a.addClass("nav-dd-has-icon");
			} else {
				$a.addClass("nav-dd-has-bullet");
			}
		});
	}

	function groupMenuBySections() {
		var $menu = $("#menu");
		var $children = $menu.children("li").not(".nav-horizontal-more");
		var $topLevel = $(document.createDocumentFragment());
		var currentLabel = null;
		var currentItems = [];

		function flushCurrent() {
			if (!currentLabel || !currentItems.length) {
				return;
			}
			flushGroup($topLevel, currentLabel, currentItems);
			currentLabel = null;
			currentItems = [];
		}

		$children.each(function () {
			var $li = $(this);

			if ($li.hasClass("nav-label")) {
				if (isDecorativeLabel($li)) {
					return;
				}

				flushCurrent();
				currentLabel = getLabelText($li);
				return;
			}

			var $clone = $li.clone(true, true);

			if (isDashboardItem($clone)) {
				flushCurrent();
				$topLevel.append($clone);
				return;
			}

			if (currentLabel) {
				currentItems.push($clone);
				return;
			}

			$topLevel.append($clone);
		});

		flushCurrent();
		$menu.empty().append($topLevel.children());
		decorateHorizontalDropdownLinks();
	}

	function disposeMetismenu() {
		var $menu = $("#menu");
		if (!$menu.length || !$.fn.metisMenu) {
			return;
		}

		try {
			$menu.metisMenu("dispose");
		} catch (e) {
			/* not initialized */
		}
	}

	function initMetismenu() {
		var $menu = $("#menu");
		if ($menu.length && $.fn.metisMenu) {
			$menu.metisMenu();
		}
	}

	function unbindHorizontalDropdowns() {
		$(document).off(".horizontalNav");
		$(".dlabnav").off(".horizontalNav");
		$("#menu").off(".horizontalNav");
	}

	function bindHorizontalDropdowns() {
		unbindHorizontalDropdowns();

		var $menu = $("#menu");
		var $nav = $(".dlabnav");

		$menu.on("click.horizontalNav", "> li > a.has-arrow", function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $li = $(this).parent("li");
			var willOpen = !$li.hasClass("nav-dropdown-open");

			$menu.find("> li.nav-dropdown-open").removeClass("nav-dropdown-open");

			if (willOpen) {
				$li.addClass("nav-dropdown-open");
			}
		});

		$menu.on("mouseenter.horizontalNav", "> li", function () {
			var $li = $(this);
			if (!$li.children("ul").length) {
				return;
			}
			$menu.find("> li.nav-dropdown-open").removeClass("nav-dropdown-open");
			$li.addClass("nav-dropdown-open");
		});

		$nav.on("mouseleave.horizontalNav", function () {
			$menu.find("> li.nav-dropdown-open").removeClass("nav-dropdown-open");
		});

		$(document).on("click.horizontalNav", function (e) {
			if (!$(e.target).closest(".dlabnav").length) {
				$menu.find("> li.nav-dropdown-open").removeClass("nav-dropdown-open");
			}
		});

		$menu.on("mouseenter.horizontalNav", "li > ul > li", function () {
			if (!$(this).children("ul").length) {
				return;
			}
			$(this).addClass("nav-nested-open").siblings().removeClass("nav-nested-open");
		});

		$menu.on("mouseleave.horizontalNav", "li > ul > li", function () {
			$(this).removeClass("nav-nested-open");
		});

		$menu.on("click.horizontalNav", "li > ul > li > a.has-arrow", function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $li = $(this).parent("li");
			var willOpen = !$li.hasClass("nav-nested-open");

			$li.siblings(".nav-nested-open").removeClass("nav-nested-open");

			if (willOpen) {
				$li.addClass("nav-nested-open");
			} else {
				$li.removeClass("nav-nested-open");
			}
		});
	}

	function destroyHorizontalNav() {
		var $menu = $("#menu");

		unbindHorizontalDropdowns();

		if (state.grouped && state.originalHtml) {
			$menu.html(state.originalHtml);
		}

		state.grouped = false;
		state.originalHtml = null;
		$("body").removeClass("nav-horizontal-ready");
		updateContentOffset();

		initMetismenu();
	}

	function initHorizontalNav() {
		if (!isHorizontalActive()) {
			destroyHorizontalNav();
			return;
		}

		var $menu = $("#menu");
		if (!$menu.length) {
			return;
		}

		if (!state.grouped) {
			state.originalHtml = $menu.html();
			disposeMetismenu();
			groupMenuBySections();
			state.grouped = true;
		}

		bindHorizontalDropdowns();
		$("body").addClass("nav-horizontal-ready");

		window.requestAnimationFrame(function () {
			updateContentOffset();
			window.setTimeout(updateContentOffset, 100);
			window.setTimeout(updateContentOffset, 350);
		});
	}

	window.syncHorizontalNav = initHorizontalNav;
	window.destroyHorizontalNav = destroyHorizontalNav;
	window.updateHorizontalNavOffset = updateContentOffset;

	$(document).ready(function () {
		window.setTimeout(initHorizontalNav, 0);
	});

	$(window).on("resize.horizontalNavOffset", function () {
		if (isHorizontalActive()) {
			updateContentOffset();
		}
	});
})(jQuery);
