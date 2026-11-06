@extends('layouts.app')
@section('title', 'Edit Admin')
@section('header')
    <div><h1>Edit Admin</h1><p class="text-secondary">Perbarui data dan saluran notifikasi untuk {{ $admin->name }}.</p></div>
@endsection
@section('content')
<section class="card"><div class="card-body p-4">
    @if(!$preferencesReady)<div class="alert alert-warning">Jalankan migrasi preferensi notifikasi admin di server sebelum menyimpan perubahan.</div>@endif
    <form method="POST" action="{{ route('admin.user-admin.update', $admin->id) }}">
        @csrf @method('PUT')
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label for="admin-name" class="form-label">Nama Admin</label><input id="admin-name" class="form-control" name="name" value="{{ old('name', $admin->name) }}" maxlength="191" required></div>
            <div class="col-md-6"><label for="admin-email" class="form-label">Email Admin</label><input id="admin-email" class="form-control" name="email" type="email" value="{{ old('email', $admin->email) }}" maxlength="191" required></div>
            <div class="col-md-6"><label for="admin-phone" class="form-label">Nomor WhatsApp Notifikasi</label><input id="admin-phone" class="form-control" name="notification_phone" type="tel" value="{{ old('notification_phone', $admin->notification_phone) }}" maxlength="16" placeholder="6281234567890"><small class="form-text">Isi nomor tujuan jika admin ingin menerima notifikasi WhatsApp.</small></div>
        </div>
        <h5>Preferensi Notifikasi</h5>
        @foreach(['email'=>'Email', 'whatsapp'=>'WhatsApp'] as $channel=>$label)
            @php($field = $channel.'_notifications_enabled')
            <div class="border rounded-3 p-3 mb-3">
                <input type="hidden" name="{{ $field }}" value="0">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="admin-{{ $channel }}-enabled" name="{{ $field }}" value="1" @checked(old($field, $admin->{$field} ?? true))><label for="admin-{{ $channel }}-enabled" class="form-check-label">Aktifkan notifikasi {{ $label }}</label></div>
                <small class="text-secondary">Jika dinonaktifkan, admin ini tidak menerima notifikasi melalui {{ $label }}.</small>
            </div>
        @endforeach
        <p class="form-text">Preferensi berlaku untuk admin ini. Notifikasi di dalam aplikasi tetap tersedia.</p>
        <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit" @disabled(!$preferencesReady)>Simpan Perubahan</button><a class="btn btn-outline-secondary" href="{{ route('admin.user-roles.index') }}">Kembali</a></div>
    </form>
</div></section>
@endsection
