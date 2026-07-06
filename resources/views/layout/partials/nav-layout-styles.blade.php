{{-- Nav layout polish (vertical + horizontal) --}}
<style>
    /* ── Shared: tighter header-to-content gap (vertical + horizontal) ── */
    [data-header-position="fixed"] #main-wrapper > .content-body {
        padding-top: var(--dz-header-height) !important;
    }

    #main-wrapper > .content-body .container {
        margin-top: 0 !important;
    }

    #main-wrapper > .content-body .container-fluid,
    #main-wrapper > .content-body .container-sm,
    #main-wrapper > .content-body .container-md,
    #main-wrapper > .content-body .container-lg,
    #main-wrapper > .content-body .container-xl,
    #main-wrapper > .content-body .container-xxl {
        padding-top: 0.75rem !important;
    }

    #main-wrapper > .content-body .page-titles {
        margin-bottom: 1rem !important;
    }

    #main-wrapper > .content-body > .dashboard-ai-slot {
        margin-top: 0;
        padding-top: 0.35rem;
    }

    .dlab-nav-layout-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .dlab-nav-layout-toggle:hover {
        background-color: rgba(0, 0, 0, 0.06);
    }

    .dlab-nav-layout-toggle.is-horizontal-pref {
        color: var(--primary);
        background-color: rgba(var(--primary-rgb, 0, 43, 128), 0.1);
    }

    .dlab-nav-layout-toggle .nav-layout-icon-top {
        display: none;
    }

    .dlab-nav-layout-toggle.is-horizontal-pref .nav-layout-icon-sidebar {
        display: none;
    }

    .dlab-nav-layout-toggle.is-horizontal-pref .nav-layout-icon-top {
        display: inline-block;
    }

    @media (max-width: 1199.98px) {
        .header-item-nav-layout {
            display: none !important;
        }
    }

    #main-wrapper.nav-layout-switching .dlabnav,
    #main-wrapper.nav-layout-switching .content-body {
        transition: opacity 0.2s ease;
        opacity: 0.92;
    }

    @media (min-width: 1200px) {
        [data-layout="horizontal"] .dlabnav,
        [data-layout="horizontal"] .dlabnav .dlabnav-scroll,
        [data-layout="horizontal"] .dlabnav .slimScrollDiv,
        [data-layout="horizontal"] .dlabnav .metismenu {
            overflow: visible !important;
        }

        [data-layout="horizontal"] .dlabnav {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            background: var(--card, #fff);
        }

        [data-layout="horizontal"] .dlabnav .metismenu {
            flex-wrap: wrap;
            gap: 0.125rem 0;
            padding: 0.35rem 1rem;
            max-width: 100%;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li {
            flex: 0 0 auto;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li > a {
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            transition: border-color 0.2s ease, color 0.2s ease;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li > a .nav-text {
            white-space: nowrap;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li.nav-dropdown-open > a,
        [data-layout="horizontal"] .dlabnav .metismenu > li.mm-active > a,
        [data-layout="horizontal"] .dlabnav .metismenu > li:hover > a {
            border-bottom-color: var(--primary);
        }

        /* Top-level dropdown */
        [data-layout="horizontal"] .dlabnav .metismenu > li.nav-dropdown-open > ul,
        [data-layout="horizontal"] .dlabnav .metismenu > li:hover > ul {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li > ul {
            max-height: min(70vh, 32rem);
            overflow-y: auto;
            border-radius: 0.5rem;
            min-width: 14rem;
            z-index: 10050 !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li.nav-section-group > ul,
        [data-layout="horizontal"] .dlabnav .metismenu > li.mega-menu-md > ul,
        [data-layout="horizontal"] .dlabnav .metismenu > li.mega-menu-lg > ul {
            min-width: 16rem;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li.nav-section-group > a {
            font-weight: 600;
        }

        /* Dropdown links: show icon when present, en-dash bullet otherwise */
        [data-layout="horizontal"] .dlabnav .metismenu li > ul a.nav-dd-has-icon,
        [data-layout="horizontal"] .dlabnav .metismenu li > ul a.nav-dd-has-bullet {
            display: flex !important;
            align-items: center;
            gap: 0.625rem;
            padding: 0.45rem 1rem !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul a.nav-dd-has-icon > i {
            display: inline-flex !important;
            width: 1.125rem;
            min-width: 1.125rem;
            justify-content: center;
            font-size: 1.05rem;
            opacity: 0.85;
            margin: 0 !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul li a.nav-dd-has-bullet::before {
            content: "–" !important;
            display: inline-flex !important;
            width: 1.125rem;
            min-width: 1.125rem;
            justify-content: center;
            color: var(--primary);
            font-weight: 700;
            opacity: 0.65;
            flex-shrink: 0;
            position: static !important;
            margin: 0 !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul a.nav-dd-has-icon .nav-text,
        [data-layout="horizontal"] .dlabnav .metismenu li > ul a.nav-dd-has-bullet .nav-text {
            flex: 1;
        }

        /* 3rd+ level: stack below parent instead of flyout to the right */
        [data-layout="horizontal"] .dlabnav .metismenu li > ul li > ul {
            position: static !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            width: 100% !important;
            min-width: 0 !important;
            max-height: none !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
            padding: 0 0 0.25rem 0 !important;
            background: transparent !important;
            display: none;
        }

        [data-layout="horizontal"] .dlabnav .metismenu > li > ul > li:hover ul.collapse,
        [data-layout="horizontal"] .dlabnav .metismenu > li > ul > li.nav-nested-open ul.collapse {
            position: static !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li.nav-nested-open > ul,
        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li:hover > ul {
            display: block !important;
            visibility: visible !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li > ul > li > a {
            padding-left: 2rem !important;
            font-size: 0.8125rem;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li > ul > li > ul > li > a {
            padding-left: 2.75rem !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li.nav-nested-open > a,
        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li:hover > a {
            background: var(--rgba-primary-1, rgba(0, 43, 128, 0.08));
            color: var(--primary);
        }

        /* Nested arrows point down/up */
        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li > a.has-arrow::after {
            right: 1rem;
            transform: rotate(45deg) translateY(-65%) !important;
        }

        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li.nav-nested-open > a.has-arrow::after,
        [data-layout="horizontal"] .dlabnav .metismenu li > ul > li:hover > a.has-arrow::after {
            transform: rotate(-135deg) translateY(-35%) !important;
        }

        [data-layout="horizontal"] #main-wrapper > .content-body {
            padding-top: calc(var(--dz-header-height) + var(--horizontal-nav-height, 3rem)) !important;
        }

        [data-layout="horizontal"] .content-body > .content-body {
            padding-top: 0 !important;
            min-height: auto !important;
        }

        [data-layout="horizontal"] .content-body .container {
            margin-top: 0 !important;
        }

        [data-layout="horizontal"] #main-wrapper > .content-body .container-fluid,
        [data-layout="horizontal"] #main-wrapper > .content-body .container-sm,
        [data-layout="horizontal"] #main-wrapper > .content-body .container-md,
        [data-layout="horizontal"] #main-wrapper > .content-body .container-lg,
        [data-layout="horizontal"] #main-wrapper > .content-body .container-xl,
        [data-layout="horizontal"] #main-wrapper > .content-body .container-xxl {
            padding-top: 0.75rem !important;
        }

        [data-layout="horizontal"] #main-wrapper > .content-body .page-titles {
            margin-bottom: 1rem !important;
        }

        [data-layout="horizontal"] .setup-alerts-inner .container-fluid {
            padding-top: 0.35rem !important;
            padding-bottom: 0 !important;
        }

        [data-layout="horizontal"] .setup-alerts-inner .alert {
            margin-bottom: 0.5rem !important;
        }

        [data-layout="horizontal"] .setup-alerts-inner + .content-body > .container-fluid,
        [data-layout="horizontal"] .setup-alerts-inner + .content-body > .container,
        [data-layout="horizontal"] .content-body > .dashboard-ai-slot + .content-body > .container-fluid {
            padding-top: 0.35rem !important;
        }

        [data-layout="horizontal"] #main-wrapper > .content-body > .dashboard-ai-slot {
            margin-top: 0;
            padding-top: 0.35rem;
        }
    }

    [data-theme-version="dark"][data-layout="horizontal"] .dlabnav {
        border-bottom-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        background: var(--card, #1e1e2d);
    }

    [data-theme-version="dark"][data-layout="horizontal"] .dlabnav .metismenu > li > ul {
        background: var(--card, #1e1e2d);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    [data-theme-version="dark"] .dlab-nav-layout-toggle:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }
</style>
