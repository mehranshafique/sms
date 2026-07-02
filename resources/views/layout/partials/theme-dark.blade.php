{{-- Global dark-mode overrides (body uses data-theme-version="dark" from dlabnav-init.js) --}}
<style>
    [data-theme-version="dark"] {
        --digitex-card-bg: #1e2746;
        --digitex-card-bg-2: #243054;
        --digitex-card-border: rgba(255, 255, 255, .08);
        --digitex-text: #e8ebf5;
        --digitex-muted: #9ca3af;
    }

    /* Page title bars & hardcoded white blocks */
    [data-theme-version="dark"] .bg-white,
    [data-theme-version="dark"] .row.page-titles.bg-white,
    [data-theme-version="dark"] .page-titles.bg-white,
    [data-theme-version="dark"] .card.bg-white,
    [data-theme-version="dark"] .card-header.bg-white {
        background-color: var(--digitex-card-bg) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .welcome-text h4,
    [data-theme-version="dark"] .welcome-text .text-primary,
    [data-theme-version="dark"] .card-title.text-primary {
        color: #c4b5fd !important;
    }

    [data-theme-version="dark"] .welcome-text p,
    [data-theme-version="dark"] .welcome-text .text-muted,
    [data-theme-version="dark"] .card .text-muted,
    [data-theme-version="dark"] .ap-stat .text-muted,
    [data-theme-version="dark"] .pkg-card .text-muted {
        color: var(--digitex-muted) !important;
    }

    /* Cards & panels */
    [data-theme-version="dark"] .content-body .card {
        background-color: var(--digitex-card-bg);
        border-color: var(--digitex-card-border);
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .card-header.bg-transparent {
        background-color: transparent !important;
    }

    /* Stat / custom panel blocks */
    [data-theme-version="dark"] .ap-stat,
    [data-theme-version="dark"] .pkg-card,
    [data-theme-version="dark"] .dash-stat,
    [data-theme-version="dark"] .dash-panel,
    [data-theme-version="dark"] .sp-stat,
    [data-theme-version="dark"] .sp-panel {
        background: var(--digitex-card-bg) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .ap-stat h4,
    [data-theme-version="dark"] .ap-stat .fw-bold,
    [data-theme-version="dark"] .dash-stat__value,
    [data-theme-version="dark"] .sp-stat__value {
        color: #fff !important;
    }

    /* Tables & DataTables */
    [data-theme-version="dark"] table thead.bg-light,
    [data-theme-version="dark"] .table thead.bg-light,
    [data-theme-version="dark"] table.dataTable thead th,
    [data-theme-version="dark"] .pkg-table thead th {
        background-color: var(--digitex-card-bg-2) !important;
        color: var(--digitex-text) !important;
        border-color: var(--digitex-card-border) !important;
    }

    [data-theme-version="dark"] .table,
    [data-theme-version="dark"] table.dataTable tbody td,
    [data-theme-version="dark"] table.dataTable tbody th {
        color: var(--digitex-text);
        border-color: var(--digitex-card-border);
    }

    [data-theme-version="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
        --bs-table-accent-bg: rgba(255, 255, 255, .03);
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .dataTables_wrapper .dataTables_filter input,
    [data-theme-version="dark"] .dataTables_wrapper .dataTables_length select {
        background-color: var(--digitex-card-bg-2);
        border-color: var(--digitex-card-border);
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .dt-buttons .dropdown-toggle {
        background-color: var(--digitex-card-bg-2) !important;
        color: var(--digitex-text) !important;
        border-color: var(--digitex-card-border) !important;
    }

    /* Forms */
    [data-theme-version="dark"] .form-control,
    [data-theme-version="dark"] .form-select {
        background-color: var(--digitex-card-bg-2);
        border-color: var(--digitex-card-border);
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .form-control::placeholder {
        color: #6b7280;
    }

    [data-theme-version="dark"] .form-label,
    [data-theme-version="dark"] label.form-label {
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .bootstrap-select .dropdown-toggle,
    [data-theme-version="dark"] .default-select.bg-white {
        background-color: var(--digitex-card-bg-2) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text) !important;
    }

    [data-theme-version="dark"] .bootstrap-select .dropdown-menu {
        background-color: var(--digitex-card-bg);
        border-color: var(--digitex-card-border);
    }

    [data-theme-version="dark"] .bootstrap-select .dropdown-menu .dropdown-item {
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .bootstrap-select .dropdown-menu .dropdown-item:hover,
    [data-theme-version="dark"] .bootstrap-select .dropdown-menu .dropdown-item:focus {
        background-color: var(--digitex-card-bg-2);
    }

    /* Modals */
    [data-theme-version="dark"] .modal-content {
        background-color: var(--digitex-card-bg);
        color: var(--digitex-text);
        border-color: var(--digitex-card-border);
    }

    [data-theme-version="dark"] .modal-header .btn-close {
        filter: invert(1);
    }

    /* AI surfaces */
    [data-theme-version="dark"] .ai-panel,
    [data-theme-version="dark"] .ai-sidebar,
    [data-theme-version="dark"] .ai-chat,
    [data-theme-version="dark"] .ai-tool-card {
        background: var(--digitex-card-bg) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .ai-thread {
        background: #172036 !important;
    }

    [data-theme-version="dark"] .ai-msg__bubble {
        background: var(--digitex-card-bg-2) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .ai-msg.user .ai-msg__bubble {
        background: #1e3a5f !important;
        border-color: #27496d !important;
    }

    [data-theme-version="dark"] .ai-output {
        background: #172036 !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .ai-tool-card h6,
    [data-theme-version="dark"] .ai-tool-card .text-dark {
        color: var(--digitex-text) !important;
    }

    [data-theme-version="dark"] .ai-output-view {
        background: linear-gradient(135deg, #1e2746, #172036);
        border-color: #4c1d95;
    }

    [data-theme-version="dark"] .ai-output-view__body {
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .ai-output-list li {
        background: var(--digitex-card-bg-2);
        border-color: var(--digitex-card-border);
    }

    [data-theme-version="dark"] .ai-output-list li strong {
        color: #c4b5fd;
    }

    /* Package / subscription form cards */
    [data-theme-version="dark"] .btn-light {
        background-color: var(--digitex-card-bg-2, #243054);
        border-color: var(--digitex-card-border, rgba(255,255,255,.08));
        color: var(--digitex-text, #e8ebf5);
    }

    [data-theme-version="dark"] .widget-stat.card .media-body p,
    [data-theme-version="dark"] .widget-stat.card .media-body h4 {
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .sub-stat,
    [data-theme-version="dark"] .sub-card {
        background: var(--digitex-card-bg) !important;
        border-color: var(--digitex-card-border) !important;
        color: var(--digitex-text);
    }

    [data-theme-version="dark"] .sub-table thead th {
        background: var(--digitex-card-bg-2) !important;
        color: var(--digitex-text) !important;
        border-color: var(--digitex-card-border) !important;
    }

    [data-theme-version="dark"] .ai-panel h3,
    [data-theme-version="dark"] .ai-panel h6,
    [data-theme-version="dark"] .ai-panel .fw-bold {
        color: var(--digitex-text);
    }
</style>
