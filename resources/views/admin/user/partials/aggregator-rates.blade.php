<fieldset class="mb-3" data-aggregator-rates>
    <legend class="fs-6">Pembagian penghasilan Aggregator</legend>
    <div class="row g-3">
        <div class="col-sm-6"><label for="verify-aggregator-rate" class="form-label">Persentase user (%)</label><input id="verify-aggregator-rate" type="number" name="aggregator_rate" class="form-control" min="0" max="100" step="1" required value="{{ old('aggregator_rate', $user->profile->aggregator_rate ?? 70) }}"></div>
        <div class="col-sm-6"><label for="verify-system-rate" class="form-label">Persentase system (%)</label><input id="verify-system-rate" type="number" name="system_rate" class="form-control" min="0" max="100" step="1" required value="{{ old('system_rate', 100 - ($user->profile->aggregator_rate ?? 70)) }}"></div>
    </div>
    <p class="form-text">Total harus 100%. Persentase user dipakai untuk laporan Aggregator dan kontrak baru.</p>
</fieldset>
<script>
document.querySelectorAll('[data-aggregator-rates]').forEach(group => {
    const user = group.querySelector('[name="aggregator_rate"]'), system = group.querySelector('[name="system_rate"]');
    [user, system].forEach((input, index) => input.addEventListener('input', () => {
        if (input.value !== '' && input.validity.valid) (index === 0 ? system : user).value = 100 - Number(input.value);
    }));
});
</script>
