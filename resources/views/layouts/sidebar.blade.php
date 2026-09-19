<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-icon">
            <a href="{{ route('home') }}">
                <img src="{{ URL::asset('build/images/logo-soundfresh-150.webp') }}" alt="SoundFresh Logo" class="py-3"
                    style="max-width: 100%;">
            </a>
        </div>
        <div class="logo-name">
            <a href="{{ route('home') }}">
                <img src="{{ URL::asset('build/images/logo-soundfresh-full-white.webp') }}" alt="SoundFresh Logo"
                    class="py-3" style="max-width: 100%;">
            </a>
        </div>
        <div class="sidebar-close">
            <span class="material-icons-outlined">close</span>
        </div>
    </div>
    <div class="sidebar-nav">
        <!--navigation-->
        <ul class="metismenu" id="sidenav">
            @can('customer')
                <li class="menu-label">Home</li>
                <li>
                    <a href="{{ route('home') }}">
                        <div class="parent-icon">
                            <span class="material-icons-outlined">home</span>
                        </div>
                        <div class="menu-title">Overview</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile') }}">
                        <div class="parent-icon">
                            <span class="material-icons-outlined">person</span>
                        </div>
                        <div class="menu-title">My Profile</div>
                    </a>
                </li>
                @can('customer.verified')
                    <li>
                        <a class="has-arrow" href="javascript:;">
                            <div class="parent-icon">
                                <span class="material-symbols-outlined">finance_mode</span>
                            </div>
                            <div class="menu-title">Analytics & Earnings</div>
                        </a>
                        <ul class="mm-collapse">
                            <li style="padding-left:30px">
                                <a href="{{ route('analytics.index') }}">
                                    <span class="material-symbols-outlined">analytics</span>
                                    <div class="menu-title">Analytics</div>
                                </a>
                            </li>
                            <li style="padding-left:30px">
                                <a href="{{ route('home.earnings') }}">
                                    <span class="material-icons-outlined">paid</span>
                                    <div class="menu-title">My Earnings</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ route('settlements.index') }}">
                            <div class="parent-icon">
                                <span class="material-icons-outlined">payments</span>
                            </div>
                            <div class="menu-title">My Transactions</div>
                        </a>
                    </li>
                    <li class="menu-label">Activity</li>
                    @if(current_user()->profile->feature_aggregator)
                        <li>
                            <a href="{{ route('releases.index') }}">
                                <div class="parent-icon">
                                    <span class="material-icons-outlined">music_note</span>
                                </div>
                                <div class="menu-title">Releases</div>
                            </a>
                        </li>
                    @endif
                    @if(current_user()->profile->feature_publishing)
                        <li>
                            <a href="{{ route('publishing.index') }}">
                                <div class="parent-icon">
                                    <span class="material-symbols-outlined">captive_portal</span>
                                </div>
                                <div class="menu-title">Publishing</div>
                            </a>
                        </li>
                    @endif
                    @if(current_user()->profile->feature_mcn)
                        <li>
                            <a href="{{ route('mcn.index') }}">
                                <div class="parent-icon">
                                    <span class="material-icons-outlined">live_tv</span>
                                </div>
                                <div class="menu-title">MCN</div>
                            </a>
                        </li>
                    @endif
                    {{-- <li class="menu-label">Support</li>
                    <li>
                        <a href="#">
                            <div class="parent-icon">
                                <span class="material-icons-outlined">local_activity</span>
                            </div>
                            <div class="menu-title">Support Ticket</div>
                        </a>
                    </li> --}}
                    @if(env('FAQS_URL'))
                        <li class="menu-label">Help & Support</li>
                        <li>
                            <a href="{{ env('FAQS_URL') }}" target="_blank">
                                <div class="parent-icon">
                                    <span class="material-symbols-outlined">help</span>
                                </div>
                                <div class="menu-title">FAQs</div>
                            </a>
                        </li>
                    @endif
                @endcan
            @endcan

            {{-- Start Admin Menu --}}
            @can('admin')
                <li class="menu-label">Administrative Tools</li>
                <li>
                    <a href="{{route('admin.home')}}">
                        <div class="parent-icon">
                            <span class="material-icons-outlined">admin_panel_settings</span>
                        </div>
                        <div class="menu-title">Admin Home</div>
                    </a>
                </li>

                {{-- Aggregator --}}
                @can('view-releases')
                    <li>
                        <a class="has-arrow" href="javascript:;">
                            <div class="parent-icon">
                                <span class="material-symbols-outlined">music_note</span>
                            </div>
                            <div class="menu-title">Aggregator</div>
                        </a>
                        <ul class="mm-collapse">
                            <li style="padding-left:30px">
                                <a href="{{route('admin.releases.index')}}">
                                    <div class="parent-icon">
                                        <span class="material-symbols-outlined">all_inclusive</span>
                                    </div>
                                    <div class="menu-title">All Releases</div>

                                </a>
                            </li>
                            @can('view-contracts')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.releases.contract.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">handshake</span>
                                        </div>
                                        <div class="menu-title">Contracts</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view-reporting')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.releases.reporting.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">receipt_long</span>
                                        </div>
                                        <div class="menu-title">Reporting</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view-transactions')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.releases.settlement.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">payments</span>
                                        </div>
                                        <div class="menu-title">Transactions</div>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                {{-- Publishing --}}
                @can('view-releases')
                    <li>
                        <a class="has-arrow" href="javascript:;">
                            <div class="parent-icon">
                                <span class="material-symbols-outlined">captive_portal</span>
                            </div>
                            <div class="menu-title">Publishing</div>
                        </a>
                        <ul class="mm-collapse">
                            <li style="padding-left:30px">
                                <a href="{{route('admin.publishing.index')}}">
                                    <div class="parent-icon">
                                        <span class="material-symbols-outlined">all_inclusive</span>
                                    </div>
                                    <div class="menu-title">All Publishing</div>
                                </a>
                            </li>
                            @can('view-contracts')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.publishing.contract.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">handshake</span>
                                        </div>
                                        <div class="menu-title">Contracts</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view-reporting')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.publishing.reporting.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">receipt_long</span>
                                        </div>
                                        <div class="menu-title">Reporting <span class="badge bg-danger">TBA</span></div>
                                    </a>
                                </li>
                            @endcan
                            @can('view-transactions')
                                <li style="padding-left:30px">
                                    <a href="{{route('admin.publishing.settlement.index')}}">
                                        <div class="parent-icon">
                                            <span class="material-icons-outlined">payments</span>
                                        </div>
                                        <div class="menu-title">Transactions <span class="badge bg-danger">TBA</span></div>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('view-users')
                    <li>
                        <a href="{{route('admin.users.index')}}">
                            <div class="parent-icon">
                                <span class="material-icons-outlined">group</span>
                            </div>
                            <div class="menu-title">User Management</div>
                        </a>
                    </li>
                @endcan

                @can('broadcast')
                    <li>
                        <a href="{{route('admin.broadcast.index')}}">
                            <div class="parent-icon">
                                <span class="material-icons-outlined">campaign</span>
                            </div>
                            <div class="menu-title">Broadcast Message</div>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.announcement.index')}}">
                            <div class="parent-icon">
                                <span class="material-symbols-outlined"> pinboard </span>
                            </div>
                            <div class="menu-title">Announcements</div>
                        </a>
                    </li>
                @endcan

                @can('view-logging')
                    <li>
                        <a href="{{route('admin.logactivity.index')}}">
                            <div class="parent-icon">
                                <span class="material-icons-outlined">history</span>
                            </div>
                            <div class="menu-title">Logging</div>
                        </a>
                    </li>
                @endcan
                @canany(['view-roles', 'manage-automation'])
                    <li class="menu-label">Server Management</li>
                    @can('manage-automation')
                        <li>
                            <a href="{{route('admin.automation.index')}}">
                                <div class="parent-icon">
                                    <span class="material-symbols-outlined">automation</span>
                                </div>
                                <div class="menu-title">Automation Status</div>
                            </a>
                        </li>
                    @endcan
                    @can('view-roles')
                        <li>
                            <a href="{{route('admin.user-roles.index')}}">
                                <div class="parent-icon">
                                    <span class="material-icons-outlined">groups</span>
                                </div>
                                <div class="menu-title">Admin Roles and Permissions</div>
                            </a>
                        </li>
                    @endcan
                @endcanany
            @endcan
        </ul>
        <!--end navigation-->
    </div>
</aside>