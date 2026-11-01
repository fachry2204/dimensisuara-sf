@include('layouts.theme-init')
@unless (Route::is('login') || Route::is('register'))
<!-- loader-->
<link href="{{ URL::asset('build/css/pace.min.css') }}" rel="stylesheet">
<script src="{{ URL::asset('build/js/pace.min.js') }}"></script>
@endunless

<!--plugins-->
<link href="{{ URL::asset('build/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/metismenu/metisMenu.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/metismenu/mm-vertical.css') }}">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/simplebar/css/simplebar.css') }}">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<!--bootstrap css-->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<!--main css-->
<link href="{{ URL::asset('build/css/bootstrap-extended.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/main.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/dark-theme.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/semi-dark.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/responsive.css') }}" rel="stylesheet">

<style>
.top-header{background:#20252a!important;border-bottom:1px solid #313d4b!important}.top-header>.navbar{display:grid!important;grid-template-columns:42px minmax(0,1fr) auto!important;align-items:center!important;min-height:64px!important;padding:0 30px!important;gap:18px!important}.top-header .btn-toggle{grid-column:1!important;display:grid!important;place-items:center!important}.top-header .btn-toggle a{display:grid;place-items:center;width:36px;height:36px;color:#dbe7f4}.topbar-spacer{grid-column:2!important;min-width:0!important}.top-header .nav-right-links{grid-column:3!important;display:flex!important;align-items:center!important;gap:12px!important;margin:0!important;padding:0!important;white-space:nowrap!important}.top-header .nav-right-links .nav-item{display:flex!important;align-items:center!important}.top-header .nav-right-links .nav-item:first-child{padding-right:12px;border-right:1px solid #4a5667}.topbar-profile{display:grid!important;place-items:center!important;width:42px!important;height:42px!important;padding:2px!important;text-decoration:none!important}.topbar-avatar{display:grid!important;place-items:center!important;width:36px!important;height:36px!important;border-radius:50%!important;background:#ff7043!important;color:#fff!important;font-size:13px!important;font-weight:800!important}.topbar-profile-name{display:none!important}.topbar-logout{margin:0!important}.topbar-logout button{display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;min-width:98px!important;height:40px!important;border:1px solid #536178!important;border-radius:8px!important;color:#edf4fb!important;padding:0 12px!important;background:transparent!important;line-height:1!important}.topbar-logout span{font-size:12px!important;font-weight:700!important}@media(max-width:700px){.top-header>.navbar{grid-template-columns:42px 1fr auto!important;padding:0 16px!important}.topbar-logout button{min-width:40px!important;width:40px!important;padding:0!important}.topbar-logout span{display:none!important}.top-header .nav-right-links{gap:6px!important}.top-header .nav-right-links .nav-item:first-child{padding-right:8px}}
.sf-app{background-color:#0b1220!important;background-image:url("data:image/svg+xml,%3Csvg width='440' height='440' viewBox='0 0 440 440' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23314c6f' stroke-width='2' opacity='.28'%3E%3Cpath d='M50 76c60-34 121-34 181 0s121 34 181 0'/%3E%3Cpath d='M28 324c68 38 137 38 205 0s137-38 205 0'/%3E%3C/g%3E%3Cg fill='%23314c6f' opacity='.22'%3E%3Ctext x='70' y='185' font-size='72'%3E%26%239833%3B%3C/text%3E%3Ctext x='300' y='270' font-size='58'%3E%26%239832%3B%3C/text%3E%3Ctext x='180' y='405' font-size='48'%3E%26%239835%3B%3C/text%3E%3C/g%3E%3C/svg%3E")!important;background-size:440px 440px!important;background-attachment:fixed!important}.sf-app .main-wrapper,.sf-app .main-content{background-color:transparent!important}
.theme-switcher{display:grid!important;place-items:center;width:40px;height:40px;padding:0!important;border:1px solid #45556b!important;border-radius:8px!important;background:transparent!important;color:#f7c55d!important}.theme-switcher:hover{border-color:#ff7043!important;background:#3a2927!important}.theme-switcher .material-icons-outlined{font-size:20px}.impersonation-spacer{height:48px}.impersonation-banner{position:fixed;top:70px;left:260px;right:0;z-index:9;display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:48px;padding:8px 30px;background:#522427;border-bottom:1px solid #a74b50;color:#ffe4e4}.toggled .impersonation-banner{left:70px}.impersonation-banner__message{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600}.impersonation-banner__message .material-icons-outlined{color:#ffb0a8;font-size:19px}.impersonation-banner__message strong{color:#fff}.impersonation-banner__stop{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;padding:6px 11px;border:1px solid #e27a70;border-radius:7px;background:#7a3034;color:#fff;font-size:12px;font-weight:800}.impersonation-banner__stop:hover{background:#93383d;color:#fff}.impersonation-banner__stop .material-icons-outlined{font-size:16px}html[data-bs-theme=light]{color-scheme:light}html[data-bs-theme=light] .sf-app{background-color:#f4f7fb!important;background-image:none!important;color:#1f2937}html[data-bs-theme=light] .top-header{background:#fff!important;border-bottom-color:#dbe3ee!important}html[data-bs-theme=light] .top-header .btn-toggle a,html[data-bs-theme=light] .top-header .nav-link{color:#334155!important}html[data-bs-theme=light] .theme-switcher{color:#475569!important;border-color:#cbd5e1!important;background:#fff!important}html[data-bs-theme=light] .theme-switcher:hover{background:#fff7ed!important;border-color:#ff7043!important}html[data-bs-theme=light] .topbar-logout button{color:#334155!important;border-color:#cbd5e1!important}html[data-bs-theme=light] .main-wrapper,html[data-bs-theme=light] .main-content{background:transparent!important}@media(max-width:700px){.impersonation-banner{left:0;padding:8px 16px}.impersonation-banner__message{font-size:12px}.impersonation-banner__message span:last-child{max-width:210px}.impersonation-banner__stop{padding:6px 8px;font-size:0}.impersonation-banner__stop .material-icons-outlined{font-size:17px}}
</style>
@stack('css')
<style>
.sidebar-theme-logo{display:block;max-width:100%;height:auto}.sidebar-theme-logo-light{display:none}html[data-bs-theme=light] .sidebar-theme-logo-dark{display:none}html[data-bs-theme=light] .sidebar-theme-logo-light{display:block}
/* Loaded after page styles so light mode changes the full workspace. */
html[data-bs-theme=light] body.sf-app,
html[data-bs-theme=light] body.sf-app:has(.dashboard-welcome),
html[data-bs-theme=light] .sf-app .main-wrapper,
html[data-bs-theme=light] .sf-app .main-content,
html[data-bs-theme=light] .sf-app:has(.dashboard-welcome) .main-wrapper,
html[data-bs-theme=light] .sf-app:has(.dashboard-welcome) .main-content {
    background-color:#f4f7fb!important;
    background-image:none!important;
    color:#334155!important;
}
html[data-bs-theme=light] .sf-app .card,
html[data-bs-theme=light] .sf-app .dashboard-kpi-card,
html[data-bs-theme=light] .sf-app .dashboard-panel,
html[data-bs-theme=light] .sf-app .admin-panel,
html[data-bs-theme=light] .sf-app .admin-metric-card {
    background:#fff!important;
    border-color:#dbe3ee!important;
    color:#334155!important;
    box-shadow:0 8px 24px rgba(15,23,42,.06)!important;
}
html[data-bs-theme=light] .sf-app .dashboard-header h4,
html[data-bs-theme=light] .sf-app .dashboard-kpi-card h3,
html[data-bs-theme=light] .sf-app .dashboard-panel h5,
html[data-bs-theme=light] .sf-app .dashboard-song-table tbody td,
html[data-bs-theme=light] .sf-app .dashboard-song-table tbody strong,
html[data-bs-theme=light] .sf-app .release-health-copy strong,
html[data-bs-theme=light] .sf-app .admin-panel h2,
html[data-bs-theme=light] .sf-app .admin-metric-card strong {
    color:#0f172a!important;
}
html[data-bs-theme=light] .sf-app .dashboard-kpi-card p,
html[data-bs-theme=light] .sf-app .dashboard-kpi-card small,
html[data-bs-theme=light] .sf-app .dashboard-panel-heading p,
html[data-bs-theme=light] .sf-app .dashboard-legend,
html[data-bs-theme=light] .sf-app .dashboard-song-table thead th,
html[data-bs-theme=light] .sf-app .dashboard-song-table tbody td.text-muted,
html[data-bs-theme=light] .sf-app .release-health-copy small,
html[data-bs-theme=light] .sf-app .admin-panel p {
    color:#64748b!important;
}
html[data-bs-theme=light] .sf-app .dashboard-panel .table {
    --bs-table-color:#334155!important;
    --bs-table-bg:transparent!important;
    --bs-table-border-color:#e2e8f0!important;
}
html[data-bs-theme=light] .sf-app .dashboard-period,
html[data-bs-theme=light] .sf-app .dashboard-secondary-btn,
html[data-bs-theme=light] .sf-app .dashboard-action {
    background:#fff!important;
    border-color:#dbe3ee!important;
    color:#334155!important;
}
/* Page CSS uses dark colors with !important. These shared selectors must load last. */

html[data-bs-theme=light] .sf-app table,
html[data-bs-theme=light] .sf-app .table {
    --bs-table-color:#334155!important;
    --bs-table-bg:#fff!important;
    --bs-table-striped-bg:#f8fafc!important;
    --bs-table-hover-bg:#f1f5f9!important;
    --bs-table-border-color:#e2e8f0!important;
    color:#334155!important;
    background-color:#fff!important;
}
html[data-bs-theme=light] .sf-app .table > :not(caption) > * > *,
html[data-bs-theme=light] .sf-app table td,
html[data-bs-theme=light] .sf-app table th {
    color:#334155!important;
    background-color:transparent!important;
    border-color:#e2e8f0!important;
}
html[data-bs-theme=light] .sf-app table thead th,
html[data-bs-theme=light] .sf-app [class*="table"] thead,
html[data-bs-theme=light] .sf-app [class*="table-head"] {
    color:#475569!important;
    background-color:#f8fafc!important;
    border-color:#e2e8f0!important;
}
html[data-bs-theme=light] .sf-app input:not(:where([type="radio"], [type="checkbox"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"])),
html[data-bs-theme=light] .sf-app select,
html[data-bs-theme=light] .sf-app textarea,
html[data-bs-theme=light] .sf-app .form-control,
html[data-bs-theme=light] .sf-app .form-select {
    background-color:#fff!important;
    color:#334155!important;
    border-color:#cbd5e1!important;
}
html[data-bs-theme=light] .sf-app h1,
html[data-bs-theme=light] .sf-app h2,
html[data-bs-theme=light] .sf-app h3,
html[data-bs-theme=light] .sf-app h4,
html[data-bs-theme=light] .sf-app h5,
html[data-bs-theme=light] .sf-app h6,
html[data-bs-theme=light] .sf-app [class*="header"] strong,
html[data-bs-theme=light] .sf-app [class*="title"] {
    color:#0f172a!important;
}
html[data-bs-theme=light] .sf-app [class*="header"] p,
html[data-bs-theme=light] .sf-app .text-muted,
html[data-bs-theme=light] .sf-app small {
    color:#64748b!important;
}
/* Sidebar & nav */
html[data-bs-theme=light] .sf-app .sidebar-wrapper,
html[data-bs-theme=light] .sf-app .sidebar-header,
html[data-bs-theme=light] .sf-app .sidebar-nav,
html[data-bs-theme=light] .sf-app .sidebar-bottom {
    background-color:#fff!important;
    border-color:#e2e8f0!important;
}
html[data-bs-theme=light] .sf-app .sidebar-nav .metismenu a,
html[data-bs-theme=light] .sf-app .sidebar-nav .menu-title,
html[data-bs-theme=light] .sf-app .menu-label {
    color:#475569!important;
}
html[data-bs-theme=light] .sf-app .sidebar-nav .metismenu a:hover,
html[data-bs-theme=light] .sf-app .sidebar-nav .metismenu a:focus,
html[data-bs-theme=light] .sf-app .sidebar-nav .mm-active > a {
    color:#ff6a00!important;
    background-color:#fff7ed!important;
}
html[data-bs-theme=light] .sf-app .sidebar-nav .metismenu ul {
    background-color:#f8fafc!important;
}
/* Main wrapper for every page sets its own dark bg — override all */
html[data-bs-theme=light] .sf-app .main-wrapper .main-content,
html[data-bs-theme=light] body.sf-app .main-content {
    background-color:#f4f7fb!important;
    background-image:none!important;
}
/* DataTables search/length inputs */
html[data-bs-theme=light] .sf-app .dataTables_filter input,
html[data-bs-theme=light] .sf-app .dataTables_length select,
html[data-bs-theme=light] .sf-app .dataTables_wrapper .dataTables_filter input,
html[data-bs-theme=light] .sf-app .dataTables_wrapper .dataTables_length select {
    background-color:#fff!important;
    color:#334155!important;
    border-color:#cbd5e1!important;
}
/* Badges & pills that have dark inline bg */
html[data-bs-theme=light] .sf-app [class*="badge-"]:not(.badge-notify),
html[data-bs-theme=light] .sf-app [class*="status-badge"],
html[data-bs-theme=light] .sf-app [class*="release-state"] {
    filter:none;
}
/* Sidebar footer bottom */
html[data-bs-theme=light] .sf-app .sidebar-bottom .dropdown-menu {
    background-color:#fff!important;
    border-color:#e2e8f0!important;
}
html[data-bs-theme=light] .sf-app .sidebar-bottom .dropdown-menu .dropdown-item {
    color:#334155!important;
}
html[data-bs-theme=light] .sf-app .sidebar-bottom .dropdown-menu .dropdown-item:hover {
    background-color:#f1f5f9!important;
}
/* Catch page-specific dashboard/list components not using Bootstrap card classes. */

html[data-bs-theme=light] .sf-app .dataTables_wrapper,
html[data-bs-theme=light] .sf-app .dataTables_scroll,
html[data-bs-theme=light] .sf-app .dataTables_scrollHead,
html[data-bs-theme=light] .sf-app .dataTables_scrollBody,
html[data-bs-theme=light] .sf-app .table-responsive {
    background-color:#fff!important;
    color:#334155!important;
}
html[data-bs-theme=light] .sf-app .nav-tabs .nav-link,
html[data-bs-theme=light] .sf-app .nav-pills .nav-link,
html[data-bs-theme=light] .sf-app .page-link,
html[data-bs-theme=light] .sf-app .dataTables_paginate .paginate_button {
    background:#fff!important;
    color:#475569!important;
    border-color:#dbe3ee!important;
}
html[data-bs-theme=light] .sf-app .nav-tabs .nav-link.active,
html[data-bs-theme=light] .sf-app .nav-pills .nav-link.active,
html[data-bs-theme=light] .sf-app .page-item.active .page-link,
html[data-bs-theme=light] .sf-app .dataTables_paginate .paginate_button.current {
    background:#fff7ed!important;
    color:#c2410c!important;
    border-color:#fdba74!important;
}
</style>
<style>
/* Keep fixed top navigation opaque and above page content. */
.sf-app .top-header,
.sf-app .top-header > .navbar {
    position: fixed !important;
    top: 0 !important;
    z-index: 1050 !important;
    opacity: 1 !important;
    isolation: isolate !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
.sf-app .top-header > .navbar {
    background-color: #20252a !important;
    background-image: none !important;
}
html[data-bs-theme="light"] .sf-app .top-header,
html[data-bs-theme="light"] .sf-app .top-header > .navbar {
    background-color: #ffffff !important;
    background-image: none !important;
    border-bottom: 1px solid #dbe3ee !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .08) !important;
}
html[data-bs-theme="light"] .sf-app .main-wrapper {
    position: relative !important;
    z-index: 0 !important;
}
</style>
<link href="{{ asset('build/css/light-theme-overrides.css') }}?v={{ file_exists(public_path('build/css/light-theme-overrides.css')) ? substr(hash_file('sha256', public_path('build/css/light-theme-overrides.css')), 0, 12) : '1' }}" rel="stylesheet">
<link href="{{ asset('build/css/theme-controls.css') }}?v={{ file_exists(public_path('build/css/theme-controls.css')) ? substr(hash_file('sha256', public_path('build/css/theme-controls.css')), 0, 12) : '1' }}" rel="stylesheet">
@include('layouts.primary-colors')

<link href="{{ asset('build/css/workspace-layout.css') }}?v={{ file_exists(public_path('build/css/workspace-layout.css')) ? substr(hash_file('sha256', public_path('build/css/workspace-layout.css')), 0, 12) : '1' }}" rel="stylesheet">
