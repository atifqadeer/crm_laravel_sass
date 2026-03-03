@yield('css')
@vite(['resources/scss/icons.scss','resources/scss/app.scss'])
@vite(['resources/js/config.js'])
<style>
    html[data-bs-theme="light"] {
        $body-color: #252728;
        .table a {
            color: #252728 !important;
        }
        .table a:hover {
            color: #4393e3 !important;
        }
        .table>:not(caption)>*>* {
            padding: .85rem;
            color: var(--bs-table-color-state, var(--bs-table-color-type, #252728));
            background-color: var(--bs-table-bg);
            border-bottom-width: var(--bs-border-width);
            box-shadow: inset 0 0 0 9999px var(--bs-table-bg-state, var(--bs-table-bg-type, var(--bs-table-accent-bg)));
        }
    }
    html[data-bs-theme="dark"] {
        .table-light {
            --#{$prefix}table-color: var(--#{$prefix}body-color);
            --#{$prefix}table-bg: var(--#{$prefix}light);
            --#{$prefix}table-border-color: #{$table-group-separator-color};
        }
        .table a {
            color: #aab8c5 !important;
        }
        .table a:hover {
            color: #4393e3 !important;
        }
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate{
            color: #aab8c5 !important
        }
        table.dataTable tbody tr {
            background-color: transparent !important;
        }
        .bg-dark {
            --bs-bg-opacity: 1;
            background-color: rgba(var(--bs-light-rgb), var(--bs-bg-opacity)) !important;
        }

        .bg-success {
            --bs-bg-opacity: 1;
            background-color: rgb(10 171 74) !important;
        }
    }

    html[data-bs-theme="light"] a.active_postcode {
        color: rgb(30, 30, 185) !important;
    }

    html[data-bs-theme="dark"] a.active_postcode {
        color: rgb(218, 171, 20) !important;
    }

    .badge-blink {
        animation: badgeBlink 1s infinite;
    }

    @keyframes badgeBlink {
        0%   { opacity: 1; }
        50%  { opacity: 0.4; }
        100% { opacity: 1; }
    }

     /* Premium Select2 UI Enhancements */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #e0e6ed !important;
        border-radius: 8px !important;
        transition: all 0.2s ease-in-out;
        background-color: #ffffff !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 15px !important;
        color: #495057 !important;
        font-size: 0.9rem;
    }

    /* Clear Button (Remove Keyword) Styling */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 35px !important; /* Positioned left of the arrow */
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin-right: 0 !important;
        z-index: 1;
        font-size: 1.2rem !important;
        color: #9ca3af !important;
        transition: color 0.2s;
    }

    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #ef4444 !important; /* Alert Red on hover */
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 10px !important;
    }

    /* Focus & Open State */
    .select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #6366f1 !important; /* Soft Indigo */
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    }

    /* Dropdown Menu styling */
    .select2-dropdown {
        border: 1px solid #e0e6ed !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        overflow: hidden;
        margin-top: 5px;
        animation: select2SlideDown 0.2s ease-out;
    }

    @keyframes select2SlideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Internal Search Box styling */
    .select2-search--dropdown {
        padding: 10px !important;
    }

    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e0e6ed !important;
        border-radius: 6px !important;
        padding: 8px 12px !important;
        outline: none !important;
        transition: border-color 0.2s;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #6366f1 !important;
    }

    /* Results & Hover styling */
    .select2-results__option {
        padding: 10px 15px !important;
        font-size: 0.875rem !important;
        transition: background 0.15s;
    }

    .select2-results__option--highlighted[aria-selected] {
        background-color: #6366f1 !important;
        color: #ffffff !important;
    }

    .select2-results__option[aria-selected="true"] {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: 500;
    }
</style>