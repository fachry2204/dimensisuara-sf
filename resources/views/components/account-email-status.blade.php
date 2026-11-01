@props(['account', 'action', 'canManage' => false])
<div class="d-flex flex-wrap align-items-center gap-2">
    @if($account->hasVerifiedEmail())
        <span class="badge bg-success">Email: Sudah Verifikasi</span>
        <small class="text-body-secondary">{{ $account->email_verified_at->format('d M Y H:i') }}</small>
    @else
        <span class="badge bg-warning text-dark">Email: Belum Verifikasi</span>
        @if($canManage)
            <form method="POST" action="{{ $action }}" class="m-0">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-sm btn-outline-primary">Verifikasi Email</button>
            </form>
        @endif
    @endif
</div>
