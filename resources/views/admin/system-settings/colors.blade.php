<div class="card mb-4"><div class="card-body p-4">
    <h5 class="d-flex align-items-center gap-2"><span class="material-icons-outlined">color_lens</span> Warna Utama</h5>
    <p class="text-secondary">Atur warna tombol di seluruh aplikasi. Pilih warna solid atau gradasi untuk tema terang dan gelap.</p>
    <form action="{{ route('admin.system-settings.update') }}" method="POST" id="primary-colors-form">
        @csrf @method('PUT')<input type="hidden" name="section" value="colors">
        <fieldset class="mb-3"><legend class="form-label fs-6">Jenis Warna</legend>
            <div class="d-flex gap-4">
                @foreach(['solid' => 'Solid', 'gradient' => 'Gradasi'] as $mode => $label)
                    <div class="form-check"><input class="form-check-input" type="radio" name="primary_color_mode" id="primary-mode-{{ $mode }}" value="{{ $mode }}" @checked(old('primary_color_mode', $settings['primary_color_mode']) === $mode)><label class="form-check-label" for="primary-mode-{{ $mode }}">{{ $label }}</label></div>
                @endforeach
            </div>
        </fieldset>
        <div class="row g-3">
            <div class="col-md-4"><label for="primary_color" class="form-label">Warna Utama / Awal</label><input type="color" id="primary_color" name="primary_color" class="form-control form-control-color w-100" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', old('primary_color', $settings['primary_color'])) ? old('primary_color', $settings['primary_color']) : $settings['primary_color'] }}"><output class="form-text" data-color-value="primary_color"></output></div>
            <div class="col-md-4" data-gradient-field><label for="primary_gradient_color" class="form-label">Warna Akhir Gradasi</label><input type="color" id="primary_gradient_color" name="primary_gradient_color" class="form-control form-control-color w-100" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', old('primary_gradient_color', $settings['primary_gradient_color'])) ? old('primary_gradient_color', $settings['primary_gradient_color']) : $settings['primary_gradient_color'] }}"><output class="form-text" data-color-value="primary_gradient_color"></output></div>
            <div class="col-md-4" data-gradient-field><label for="primary_gradient_angle" class="form-label">Arah Gradasi (derajat)</label><input type="number" id="primary_gradient_angle" name="primary_gradient_angle" min="0" max="360" step="1" class="form-control" value="{{ old('primary_gradient_angle', $settings['primary_gradient_angle']) }}" required></div>
        </div>
        <div class="border rounded p-3 mt-3"><span class="d-block form-label">Pratinjau warna</span><button type="button" id="primary-colors-preview" style="padding:12px 24px;border:0;border-radius:8px;font-weight:700">Contoh Tombol</button><p class="form-text mb-0 mt-2">Warna teks disesuaikan otomatis agar terbaca. Gradasi dengan perbedaan kecerahan ekstrem akan disesuaikan untuk menjaga kontras.</p></div>
        <button class="btn btn-primary mt-3" type="submit">Simpan Warna Utama</button>
    </form>
</div></div>
<script>
(() => {
    const form = document.getElementById('primary-colors-form');
    const rgb = hex => hex.slice(1).match(/.{2}/g).map(n => parseInt(n, 16));
    const mix = (a,b,t) => a.map((v,i) => v*(1-t)+b[i]*t);
    const luminance = a => a.map(n => {n/=255;return n<=.04045?n/12.92:((n+.055)/1.055)**2.4}).reduce((sum,n,i)=>sum+n*[.2126,.7152,.0722][i],0);
    const contrast = (a,b) => {a=luminance(a);b=luminance(b);return (Math.max(a,b)+.05)/(Math.min(a,b)+.05)};
    const minimum = (a,b,text) => Math.min(...Array.from({length:21},(_,i)=>contrast(mix(a,b,i/20),text)));
    const css = a => '#'+a.map(n=>Math.round(n).toString(16).padStart(2,'0')).join('');
    const update = () => {
        const gradient = form.elements.primary_color_mode.value === 'gradient';
        form.querySelectorAll('[data-gradient-field]').forEach(field=>field.hidden=!gradient);
        form.querySelectorAll('[data-color-value]').forEach(output=>output.textContent=form.elements[output.dataset.colorValue].value.toUpperCase());
        let start=rgb(form.elements.primary_color.value),end=gradient?rgb(form.elements.primary_gradient_color.value):start;
        const white=[255,255,255],black=[0,0,0];
        let text=minimum(start,end,white)>=minimum(start,end,black)?white:black;
        if(minimum(start,end,text)<4.6){const a=start,b=end;text=white;for(let i=1;i<=100;i++){start=mix(a,black,i/100);end=mix(b,black,i/100);if(minimum(start,end,text)>=4.6)break}}
        const preview=document.getElementById('primary-colors-preview');
        preview.style.background=gradient?`linear-gradient(${Number(form.elements.primary_gradient_angle.value)}deg,${css(start)},${css(end)})`:css(start);
        preview.style.color=css(text);
    };
    form.addEventListener('input',update); form.addEventListener('change',update); update();
})();
</script>
