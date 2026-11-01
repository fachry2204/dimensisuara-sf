<div class="impersonation-banner" role="status">
    <form id="stop-impersonate-form" class="d-none" action="{{ route('admin.impersonate.stop') }}" method="POST">
        @csrf
    </form>
    <div class="impersonation-banner__message">
        <span class="material-icons-outlined" aria-hidden="true">visibility</span>
        <span>You are viewing {{ app_name() }} as <strong>{{ session()->get('impersonate_name') }}</strong>.</span>
    </div>
    <button type="submit" form="stop-impersonate-form" class="btn impersonation-banner__stop">
        <span class="material-icons-outlined">undo</span> Stop impersonating
    </button>
</div>
