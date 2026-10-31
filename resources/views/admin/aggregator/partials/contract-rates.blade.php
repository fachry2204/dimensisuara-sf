@php($initialUserRate = $initialUserRate ?? 70)
@php($ratePrefix = $ratePrefix ?? 'contract')
@php($userRate = old('aggregator_rate', $initialUserRate))
@php($systemRate = old('system_rate', 100 - $initialUserRate))
<fieldset class="mb-3" data-contract-rates>
    <legend class="fs-6">Pembagian Penghasilan</legend>
    <div class="row g-3">
        <div class="col-sm-6"><label for="{{ $ratePrefix }}-user-rate" class="form-label">Pembagian User (%)</label><input id="{{ $ratePrefix }}-user-rate" name="aggregator_rate" type="number" class="form-control" min="0" max="100" step="1" required value="{{ is_numeric($userRate) ? (float) $userRate : $userRate }}"></div>
        <div class="col-sm-6"><label for="{{ $ratePrefix }}-system-rate" class="form-label">Pembagian System (%)</label><input id="{{ $ratePrefix }}-system-rate" name="system_rate" type="number" class="form-control" min="0" max="100" step="1" required value="{{ is_numeric($systemRate) ? (float) $systemRate : $systemRate }}"></div>
    </div>
    <p class="form-text">Total harus 100%. Disimpan pada kontrak dan menjadi rate Aggregator user untuk laporan berikutnya.</p>
    @error('aggregator_rate')<div class="text-danger">{{ $message }}</div>@enderror
    @error('system_rate')<div class="text-danger">{{ $message }}</div>@enderror
</fieldset>
<script>
(() => {
    const group = document.currentScript.previousElementSibling;
    const user = group.querySelector('[name="aggregator_rate"]'), system = group.querySelector('[name="system_rate"]');
    [user, system].forEach(input => input.addEventListener('blur', () => {
        if (input.value !== '' && input.validity.valid) input.value = Number(input.value);
    }));
    [user, system].forEach((input, index) => input.addEventListener('input', () => {
        if (input.value !== '' && input.validity.valid) (index === 0 ? system : user).value = 100 - Number(input.value);
    }));
    const profile = group.closest('form').querySelector('select[name="profileid"]');
    if (profile) profile.addEventListener('change', () => {
        user.value = Number(profile.selectedOptions[0]?.dataset.rate || '70');
        system.value = 100 - Number(user.value);
    });
    @if(old('type') === 'aggregator' && $errors->any())
    document.addEventListener('DOMContentLoaded', () => {
        const modal = group.closest('.modal');
        if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
    });
    @endif
})();
</script>
