@props(['history'])
<span class="fw-normal">{{ $history->admin_user_id ? 'Admin' : 'User' }}: {{ filled($history->user?->name) ? $history->user->name : 'Nama akun tidak tersedia' }}</span>
