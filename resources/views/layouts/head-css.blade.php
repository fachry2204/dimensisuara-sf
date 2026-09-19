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
.impersonation-spacer{height:48px}.impersonation-banner{position:fixed;top:70px;left:260px;right:0;z-index:9;display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:48px;padding:8px 30px;background:#522427;border-bottom:1px solid #a74b50;color:#ffe4e4}.toggled .impersonation-banner{left:70px}.impersonation-banner__message{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600}.impersonation-banner__message .material-icons-outlined{color:#ffb0a8;font-size:19px}.impersonation-banner__message strong{color:#fff}.impersonation-banner__stop{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;padding:6px 11px;border:1px solid #e27a70;border-radius:7px;background:#7a3034;color:#fff;font-size:12px;font-weight:800}.impersonation-banner__stop:hover{background:#93383d;color:#fff}.impersonation-banner__stop .material-icons-outlined{font-size:16px}@media(max-width:700px){.impersonation-banner{left:0;padding:8px 16px}.impersonation-banner__message{font-size:12px}.impersonation-banner__message span:last-child{max-width:210px}.impersonation-banner__stop{padding:6px 8px;font-size:0}.impersonation-banner__stop .material-icons-outlined{font-size:17px}}
</style>
@stack('css')
