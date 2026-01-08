@yield('css')
@vite(['resources/scss/icons.scss','resources/scss/app.scss'])
@vite(['resources/js/config.js'])
<style>
    html[data-bs-theme="light"] {
        $body-color: #252728;
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
            color: yellow !important;
        }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate{
            color: var(--#{$prefix}body-color) !important
        }
        .bg-dark {
            --bs-bg-opacity: 1;
            background-color: rgba(var(--bs-light-rgb), var(--bs-bg-opacity)) !important;
        }
    }
</style>