<div class="card"><div class="card-body p-4">
    <h4>Mode Maintenance</h4>
    <p class="text-secondary">Pengguna dalam kelompok yang dipilih akan melihat halaman maintenance. Kelompok lainnya tetap dapat menggunakan aplikasi.</p>
    <form action="{{ route('admin.system-settings.update') }}" method="POST">
        @csrf @method('PUT')<input type="hidden" name="section" value="maintenance">
        <div class="form-check form-switch mb-4"><input id="maintenance_enabled" type="checkbox" name="maintenance_enabled" class="form-check-input" value="1" @checked(old('maintenance_enabled', old('section') === 'maintenance' ? false : $settings['maintenance_enabled']))><label for="maintenance_enabled" class="form-check-label">Aktifkan Mode Maintenance</label></div>
        <fieldset class="border rounded p-3 mb-4"><legend class="fs-6 float-none w-auto px-2">Role / Kelompok yang terkena maintenance</legend>
            @foreach($maintenanceRoles as $role)
                <div class="form-check mb-2"><input type="checkbox" id="maintenance-role-{{ $role->id }}" name="maintenance_roles[]" value="{{ $role->id }}" class="form-check-input" @checked(in_array($role->id, old('maintenance_roles', old('section') === 'maintenance' ? [] : $settings['maintenance_roles'])))><label for="maintenance-role-{{ $role->id }}" class="form-check-label">{{ $role->name }}</label></div>
            @endforeach
            <div class="form-check mb-2"><input type="checkbox" id="maintenance_customers" name="maintenance_customers" value="1" class="form-check-input" @checked(old('maintenance_customers', old('section') === 'maintenance' ? false : $settings['maintenance_customers']))><label for="maintenance_customers" class="form-check-label">Client / User tanpa role admin</label></div>
            <div class="form-check"><input type="checkbox" id="maintenance_guests" name="maintenance_guests" value="1" class="form-check-input" @checked(old('maintenance_guests', old('section') === 'maintenance' ? false : $settings['maintenance_guests']))><label for="maintenance_guests" class="form-check-label">Pengunjung belum login</label></div>
        </fieldset>
        <label for="maintenance_reason" class="form-label">Alasan Maintenance</label>
        <textarea id="maintenance_reason" name="maintenance_reason" class="form-control" rows="5" required maxlength="3000">{{ old('maintenance_reason', $settings['maintenance_reason']) }}</textarea>
        <p class="form-text">Alasan ini ditampilkan pada halaman maintenance dan tersimpan di database.</p>
        <div class="alert alert-info mt-3">Admin dengan izin mengelola server selalu dapat masuk dan menonaktifkan maintenance. Halaman login, logout, dan pemulihan password tetap tersedia.</div>
        <button type="submit" class="btn btn-primary">Simpan Maintenance</button>
        <a class="btn btn-outline-primary" href="{{ route('admin.system-settings.maintenance-preview') }}" target="_blank">Pratinjau Halaman Maintenance</a>
    </form>
</div></div>
