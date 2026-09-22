<!--start header-->
<header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4">
        <div class="btn-toggle">
            <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
        </div>
        <div class="topbar-spacer" aria-hidden="true"></div>
        <ul class="navbar-nav gap-1 nav-right-links align-items-center">
            <li class="nav-item">
                <button type="button" id="mode-switcher" class="nav-link theme-switcher" title="Gunakan tema terang" aria-label="Gunakan tema terang" aria-pressed="false">
                    <i class="material-icons-outlined" aria-hidden="true">dark_mode</i>
                </button>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" data-bs-auto-close="outside" data-bs-toggle="dropdown" href="javascript:;">
                    <i class="material-icons-outlined">notifications</i>
                    @if(current_user()->unreadNotifications->count() > 0)
                        <span class="badge-notify">{{ current_user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-notify dropdown-menu-end shadow">
                    <div class="px-3 py-1 d-flex align-items-center border-bottom">
                        <h5 class="notiy-title mb-0">Notifications</h5>
                        <button type="button" id="markall" class="ms-auto btn btn-secondary dropdown-toggle dropdown-toggle-nocaret option" type="button">
                            <span class="material-icons-outlined">done_all</span>
                        </button>
                    </div>
                    <div class="notify-list" style="height: fit-content; max-height: 390px;">
                        @if(current_user()->unreadNotifications->count() > 0)
                            @foreach(current_user()->unreadNotifications->take(10) as $notification)

                            @php
                                $link   = isset($notification->data['route']) ? route($notification->data['route']['name'], $notification->data['route']['id']) : '#';
                                if (isset($notification->data['route']) &&  isset($notification->data['route']['image'])) {
                                    $image  = isset($notification->data['route']) ? route('file.access', $notification->data['route']['image']) : '';
                                }
                                else {
                                    $image = "";
                                }
                            @endphp

                                <a class="dropdown-item border-bottom py-2" href="{{ $link }}">
                                    <div class="d-flex align-items-start gap-3">
                                        @if($image)
                                            <div class="">
                                                <img src="{{ $image }}" class="rounded-circle" style="object-fit: cover;" width="50" height="50" alt="Notification" onerror="this.onerror=null;this.src='{{ URL::asset('build/images/album-placeholder.webp') }}';">
                                            </div>
                                        @endif
                                        <div class="">
                                            <h5 class="notify-title fw-bold" style="white-space: normal;">{{ $notification->data['subject'] }}</h5>
                                            <p class="mb-0 notify-desc text-wrap">{{ $notification->data['message'] }}</p>
                                            <p class="mb-0 notify-time">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                        <div class="notify-close position-absolute end-0 me-3">
                                            <i class="material-icons-outlined fs-6" data-id="{{ $notification->id }}">close</i>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                        <div id="no-notification">
                            <a class="dropdown-item border-bottom py-2" href="javascript:;">
                                <h5 class="notify-title text-center">No new notifications</h5>
                            </a>
                        </div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="p-3 d-flex align-items-center justify-content-center">
                        <h5 class="notiy-title mb-0">All Notifications</h5>
                    </a>
                </div>
                
            </li>
            <li class="nav-item"><a class="nav-link topbar-profile" href="{{ route('profile') }}" title="Profil saya"><span class="topbar-avatar">{{ strtoupper(substr(current_user()->full_name, 0, 1)) }}</span></a></li>
            <li class="nav-item"><form action="{{ route('logout') }}" method="POST" class="topbar-logout">@csrf<button type="submit" class="nav-link" title="Logout"><i class="material-icons-outlined">logout</i><span>Logout</span></button></form></li>
        </ul>

    </nav>
    @if(session()->has('impersonate'))
        <div class="impersonation-banner" role="status">
            <form id="stop-impersonate-form" class="d-none" action="{{ route('admin.impersonate.stop') }}" method="POST">
                @csrf
            </form>
            <div class="impersonation-banner__message">
                <span class="material-icons-outlined" aria-hidden="true">visibility</span>
                <span>You are viewing Soundfresh as <strong>{{ session()->get('impersonate_name') }}</strong>.</span>
            </div>
            <button type="submit" form="stop-impersonate-form" class="btn impersonation-banner__stop">
                <span class="material-icons-outlined">undo</span> Stop impersonating
            </button>
        </div>
    @endif
</header>
<!--end top header-->
@push('css')<style>.top-header{background:#20252a!important;border-bottom:1px solid #313d4b}.top-header .navbar{display:flex!important;height:64px!important;min-height:64px;padding:0 30px;gap:18px!important}.top-header .btn-toggle{flex:0 0 auto}.topbar-workspace{display:flex!important;flex:1 1 auto;min-width:0;align-items:center;gap:10px;color:#dbe7f4}.topbar-workspace>span{display:grid;place-items:center;flex:0 0 35px;width:35px;height:35px;border-radius:9px;background:#33251f;color:#ff8050}.topbar-workspace strong{display:block;font-size:13px;line-height:16px}.topbar-workspace small{display:block;color:#8fa6c1;font-size:11px;line-height:14px}.nav-right-links{display:flex!important;flex:0 0 auto;align-items:center!important;gap:10px!important;margin:0!important;white-space:nowrap}.topbar-profile{display:flex!important;align-items:center;gap:0;padding:4px!important;text-decoration:none}.topbar-avatar{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:#ff7043;color:#fff;font-weight:800;font-size:13px}.topbar-profile-name{display:none!important}.topbar-logout button{display:flex!important;align-items:center;gap:6px;border:1px solid #45556b!important;border-radius:8px;color:#c9d7e8!important;padding:7px 10px!important;background:transparent;line-height:1}.topbar-logout button:hover{color:#fff!important;border-color:#ff7043!important;background:#3a2927}.topbar-logout span{font-size:12px;font-weight:700}.impersonation-spacer{height:48px}.impersonation-banner{position:fixed;top:64px;left:260px;right:0;z-index:9;display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:48px;padding:8px 30px;background:#522427;border-bottom:1px solid #a74b50;color:#ffe4e4}.toggled .impersonation-banner{left:70px}.impersonation-banner__message{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600}.impersonation-banner__message .material-icons-outlined{color:#ffb0a8;font-size:19px}.impersonation-banner__message strong{color:#fff}.impersonation-banner__stop{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;padding:6px 11px;border:1px solid #e27a70;border-radius:7px;background:#7a3034;color:#fff;font-size:12px;font-weight:800}.impersonation-banner__stop:hover{background:#93383d;color:#fff}.impersonation-banner__stop .material-icons-outlined{font-size:16px}@media(max-width:700px){.topbar-workspace{display:none!important}.top-header .navbar{padding:0 16px}.topbar-logout span{display:none}.impersonation-banner{left:0;padding:8px 16px}.impersonation-banner__message{font-size:12px}.impersonation-banner__message span:last-child{max-width:210px}.impersonation-banner__stop{padding:6px 8px;font-size:0}.impersonation-banner__stop .material-icons-outlined{font-size:17px}}</style>@endpush

@push('script')
    <script>
        var notifications = {{ current_user()->unreadNotifications->count() }};
        $(document).ready(function() {
            notifyListCheck();
            $('.notify-close').click(async function(event) {
                var id = $(event.target).data('id');
                var url = "{{ route('notifications.mark_read', ':id') }}";
                url = url.replace(':id', id);
                fetch(url).then(res => res.json());

                event.preventDefault();
                notifications--;
                $(this).parents('.dropdown-item').remove();
                notifyListCheck();
            });

            $('#markall').click(async function(event) {
                var url = "{{ route('notifications.mark_read', 'all') }}";
                fetch(url).then(res => res.json());

                $('.notify-list > a').remove();
                notifications = 0;
                notifyListCheck();
            });
        });

        function notifyListCheck() {
            if(notifications > 0) $('#no-notification').hide();
            else {
                $('#no-notification').show();
                $('.badge-notify').hide();
                $('.badge-notify').html(notifications);
            };
        }
        
    </script>
@endpush
