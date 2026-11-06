<nav class="nav nav-pills flex-wrap gap-2 mb-4" aria-label="Navigasi Automation">
    @foreach(['jobs' => 'Job Monitor', 'email' => 'Riwayat Notifikasi Email', 'whatsapp' => 'Riwayat Notifikasi WhatsApp'] as $tab => $label)
        <a class="nav-link {{ $automationTab === $tab ? 'active' : '' }}" href="{{ route('admin.automation.index', ['tab' => $tab]) }}" @if($automationTab === $tab) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
