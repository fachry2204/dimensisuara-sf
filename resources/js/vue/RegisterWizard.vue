<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    action: String, csrf: String, loginUrl: String, pendingUrl: String,
    termsUrl: String, logoUrl: String, appName: String, studioUrl: String, countries: Object,
});

const steps = ['Jenis akun', 'Kontak', 'Alamat', 'Dokumen', 'Rekening', 'Keamanan', 'Tinjau'];
const descriptions = [
    'Pilih jenis akun yang paling sesuai dengan kebutuhanmu.',
    'Isi data penanggung jawab akun.',
    'Lengkapi alamat sesuai identitas.',
    'Unggah dokumen untuk verifikasi akun.',
    'Rekening ini digunakan untuk penarikan penghasilan.',
    'Buat kata sandi untuk mengamankan akunmu.',
    'Periksa kembali persetujuan dan bubuhkan tanda tangan.',
];
const step = ref(0);
const busy = ref(false);
const showPassword = ref(false);
const errors = ref({});
const generalError = ref('');
const signatureCanvas = ref(null);
const signed = ref(false);
let drawing = false;
let signatureData = null;

const form = reactive({
    registrationtype: 'personal', companyname: '', nibcompany_num: '', npwpcompany_num: '', position: '',
    firstname: '', lastname: '', email: '', phone: '', ktp_num: '', npwppersonal_num: '',
    country: 'ID', provinceid: '', cityid: '', districtid: '', villageid: '', zip: '', state: '', city: '', address1: '', address2: '',
    bankname: '', bankbranch: '', bankaccountname: '', bankaccountnumber: '',
    password: '', password_confirmation: '', contract_agree: false, stampfee_agree: false,
});
const files = reactive({ ktp: null, npwppersonal: null, nibcompany: null, npwpcompany: null, deed: null, establishment: null });
const regions = reactive({ provinces: [], cities: [], districts: [], villages: [], postcodes: [] });
const isCompany = computed(() => form.registrationtype === 'company');
const sortedCountries = computed(() => Object.entries(props.countries || {}).sort((a, b) => a[1].localeCompare(b[1])));

function setFile(name, event) { files[name] = event.target.files?.[0] || null; }
function errorFor(key) { return errors.value[key]?.[0] || ''; }
function clearErrors() { errors.value = {}; generalError.value = ''; }
async function getRegions(type, url) {
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Data wilayah tidak tersedia.');
        regions[type] = await response.json();
    } catch { generalError.value = 'Data wilayah gagal dimuat. Periksa koneksi lalu coba lagi.'; }
}
function resetRegion(level) {
    if (level <= 1) { form.cityid = ''; regions.cities = []; }
    if (level <= 2) { form.districtid = ''; regions.districts = []; }
    if (level <= 3) { form.villageid = ''; regions.villages = []; }
    form.zip = ''; regions.postcodes = [];
}
function regionChange(level) {
    resetRegion(level);
    if (level === 1 && form.provinceid) getRegions('cities', `/regions/children?parent_code=${encodeURIComponent(form.provinceid)}`);
    if (level === 2 && form.cityid) getRegions('districts', `/regions/children?parent_code=${encodeURIComponent(form.cityid)}`);
    if (level === 3 && form.districtid) getRegions('villages', `/regions/children?parent_code=${encodeURIComponent(form.districtid)}`);
    if (level === 4 && form.villageid) getRegions('postcodes', `/regions/postcode?parent_code=${encodeURIComponent(form.villageid)}`);
}
onMounted(() => getRegions('provinces', '/regions/provinces'));

function validateCurrentStep() {
    clearErrors();
    const missing = [];
    const required = (key, label) => { if (!String(form[key] ?? '').trim()) missing.push(label); };
    if (step.value === 0 && isCompany.value) {
        [['companyname', 'Nama perusahaan'], ['nibcompany_num', 'NIB'], ['npwpcompany_num', 'NPWP perusahaan'], ['position', 'Jabatan']].forEach(([key, label]) => required(key, label));
    }
    if (step.value === 1) {
        [['firstname', 'Nama depan'], ['email', 'Email'], ['phone', 'Nomor telepon'], ['ktp_num', 'Nomor KTP']].forEach(([key, label]) => required(key, label));
        if (!isCompany.value) required('npwppersonal_num', 'NPWP pribadi');
        if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) missing.push('Format email yang valid');
    }
    if (step.value === 2) {
        required('country', 'Negara'); required('address1', 'Alamat'); required('zip', 'Kode pos');
        if (form.country === 'ID') [['provinceid', 'Provinsi'], ['cityid', 'Kabupaten/Kota'], ['districtid', 'Kecamatan'], ['villageid', 'Kelurahan']].forEach(([key, label]) => required(key, label));
        else { required('state', 'Provinsi/negara bagian'); required('city', 'Kota'); }
    }
    if (step.value === 3) {
        if (!files.ktp) missing.push('Foto KTP');
        if (isCompany.value) [['nibcompany', 'Dokumen NIB'], ['npwpcompany', 'Dokumen NPWP'], ['deed', 'Akta pendirian'], ['establishment', 'SK pendirian']].forEach(([key, label]) => { if (!files[key]) missing.push(label); });
        else if (!files.npwppersonal) missing.push('Dokumen NPWP pribadi');
        for (const file of Object.values(files)) if (file && file.size > 5 * 1024 * 1024) missing.push(`${file.name} melebihi 5 MB`);
    }
    if (step.value === 4) [['bankname', 'Nama bank'], ['bankbranch', 'Cabang bank'], ['bankaccountname', 'Nama pemilik rekening'], ['bankaccountnumber', 'Nomor rekening']].forEach(([key, label]) => required(key, label));
    if (step.value === 5) {
        if (form.password.length < 8) missing.push('Kata sandi minimal 8 karakter');
        if (form.password !== form.password_confirmation) missing.push('Konfirmasi kata sandi harus sama');
    }
    if (step.value === 6) {
        if (!signed.value) missing.push('Tanda tangan');
        if (!form.contract_agree) missing.push('Persetujuan syarat dan ketentuan');
        if (!form.stampfee_agree) missing.push('Persetujuan biaya eMeterai');
    }
    if (missing.length) { generalError.value = `Lengkapi: ${missing.join(', ')}.`; return false; }
    return true;
}
function goTo(index) {
    if (index < 0 || index >= steps.length || busy.value) return;
    if (index > step.value && !validateCurrentStep()) return;
    if (step.value === 6 && signed.value && signatureCanvas.value) signatureData = signatureCanvas.value.toDataURL('image/png');
    step.value = index;
    clearErrors();
    if (index === 6) nextTick(prepareCanvas);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function prepareCanvas() {
    const canvas = signatureCanvas.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#17202b'; ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    signed.value = false;
    if (signatureData) {
        const image = new Image();
        image.onload = () => { ctx.drawImage(image, 0, 0, canvas.width, canvas.height); signed.value = true; };
        image.src = signatureData;
    }
}
function pointerPosition(event) {
    const rect = signatureCanvas.value.getBoundingClientRect();
    return { x: (event.clientX - rect.left) * signatureCanvas.value.width / rect.width, y: (event.clientY - rect.top) * signatureCanvas.value.height / rect.height };
}
function startDraw(event) {
    drawing = true; signatureCanvas.value.setPointerCapture(event.pointerId);
    const point = pointerPosition(event); const ctx = signatureCanvas.value.getContext('2d');
    ctx.beginPath(); ctx.moveTo(point.x, point.y);
}
function draw(event) {
    if (!drawing) return;
    const point = pointerPosition(event); const ctx = signatureCanvas.value.getContext('2d');
    ctx.lineTo(point.x, point.y); ctx.stroke(); signed.value = true;
}
function endDraw() { drawing = false; if (signed.value) signatureData = signatureCanvas.value.toDataURL('image/png'); }
function clearSignature() { signatureData = null; prepareCanvas(); }
function uploadSignature(event) {
    const file = event.target.files?.[0]; if (!file) return;
    if (!file.type.startsWith('image/')) { generalError.value = 'Tanda tangan unggahan harus berupa gambar JPG atau PNG.'; return; }
    const img = new Image(); const url = URL.createObjectURL(file);
    img.onload = () => {
        signatureData = null;
        prepareCanvas(); const canvas = signatureCanvas.value; const ctx = canvas.getContext('2d');
        const ratio = Math.min(canvas.width / img.width, canvas.height / img.height);
        ctx.drawImage(img, (canvas.width - img.width * ratio) / 2, (canvas.height - img.height * ratio) / 2, img.width * ratio, img.height * ratio);
        signed.value = true; signatureData = canvas.toDataURL('image/png'); URL.revokeObjectURL(url);
    };
    img.onerror = () => { generalError.value = 'Gambar tanda tangan tidak dapat dibaca.'; URL.revokeObjectURL(url); };
    img.src = url;
}
async function submit() {
    if (!validateCurrentStep() || busy.value) return;
    busy.value = true;
    try {
        const body = new FormData();
        for (const [key, value] of Object.entries(form)) if (!['contract_agree', 'stampfee_agree'].includes(key)) body.append(key, value ?? '');
        for (const [key, value] of Object.entries(files)) if (value && (key === 'ktp' || (isCompany.value ? key !== 'npwppersonal' : key === 'npwppersonal'))) body.append(key, value);
        body.append('signature', signatureCanvas.value.toDataURL('image/png'));
        const response = await fetch(props.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' }, body, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) {
            if (result.errors && !Array.isArray(result.errors) && typeof result.errors === 'object') errors.value = result.errors;
            generalError.value = Array.isArray(result.errors) ? result.errors.join(' ') : typeof result.errors === 'string' ? result.errors : Object.keys(errors.value).length ? '' : 'Periksa data yang ditandai, lalu coba lagi.';
            return;
        }
        window.location.assign(props.pendingUrl);
    } catch { generalError.value = 'Pendaftaran belum terkirim. Periksa koneksi lalu coba lagi.'; }
    finally { busy.value = false; }
}
</script>

<template>
    <main class="sf-register" :class="{ 'is-compact-step': step === 0 }" :style="{ '--studio-image': `url('${studioUrl}')` }">
        <aside class="sf-register__rail">
            <a :href="loginUrl" class="sf-register__logo"><img :src="logoUrl" :alt="appName"></a>
            <nav aria-label="Tahap pendaftaran"><ol><li v-for="(label, index) in steps" :key="label" :class="{ current: index === step, done: index < step }"><span>{{ index + 1 }}</span>{{ label }}</li></ol></nav>
            <p class="sf-register__rail-footer"><span class="material-icons-outlined">schedule</span>Pendaftaran hanya butuh<br>beberapa menit.</p>
        </aside>
        <section class="sf-register__main">
            <div class="sf-register__top"><span>Langkah {{ step + 1 }} dari 7</span><a :href="loginUrl">Sudah punya akun? <strong>Masuk</strong></a></div>
            <div class="sf-register__progress" role="progressbar" :aria-valuenow="step + 1" aria-valuemin="1" aria-valuemax="7" aria-label="Kemajuan pendaftaran"><span v-for="index in 7" :key="index" :class="{ active: index <= step + 1 }"></span></div>
            <div class="sf-register__body">
                <h1>{{ step === 0 ? 'Buat akun ' + appName : steps[step] }}</h1>
                <p class="sf-register__intro">{{ descriptions[step] }}</p>
                <div v-if="generalError" class="sf-register__error" role="alert">{{ generalError }}</div>
                <div v-if="Object.keys(errors).length" class="sf-register__error" role="alert"><strong>Periksa kembali:</strong><ul><li v-for="(messages, key) in errors" :key="key">{{ Array.isArray(messages) ? messages[0] : messages }}</li></ul></div>

                <div v-if="step === 0">
                    <div class="sf-register__choices">
                        <label :class="{ selected: !isCompany }"><input v-model="form.registrationtype" name="registrationtype" type="radio" value="personal"><span class="material-icons-outlined">person_outline</span><span><strong>Individu</strong><small>Untuk musisi, solois, atau kreator independen.</small></span></label>
                        <label :class="{ selected: isCompany }"><input v-model="form.registrationtype" name="registrationtype" type="radio" value="company"><span class="material-icons-outlined">business</span><span><strong>Perusahaan</strong><small>Untuk label, manajemen, atau badan usaha.</small></span></label>
                    </div>
                    <div class="sf-register__info"><span class="material-icons-outlined">info</span>Kamu dapat melengkapi data berikutnya setelah memilih jenis akun.</div>
                    <div v-if="isCompany" class="sf-register__section"><h2>Data perusahaan</h2><p>Lengkapi informasi perusahaan untuk melanjutkan.</p><div class="sf-register__grid">
                        <label>Nama perusahaan <input v-model="form.companyname" autocomplete="organization" required></label>
                        <label>NIB <input v-model="form.nibcompany_num" required></label>
                        <label>NPWP perusahaan <input v-model="form.npwpcompany_num" required></label>
                        <label>Jabatan <input v-model="form.position" required></label>
                    </div></div>
                </div>
                <div v-else-if="step === 1" class="sf-register__grid">
                    <label>Nama depan <input v-model="form.firstname" autocomplete="given-name" required></label>
                    <label>Nama belakang <input v-model="form.lastname" autocomplete="family-name"></label>
                    <label>Alamat email <input v-model="form.email" type="email" autocomplete="email" required></label>
                    <label>Nomor telepon <input v-model="form.phone" type="tel" autocomplete="tel" required></label>
                    <label>Nomor KTP <input v-model="form.ktp_num" required></label>
                    <label v-if="!isCompany">Nomor NPWP pribadi <input v-model="form.npwppersonal_num" required></label>
                </div>
                <div v-else-if="step === 2" class="sf-register__grid">
                    <label>Negara <select v-model="form.country"><option v-for="[code, country] in sortedCountries" :key="code" :value="code">{{ country }}</option></select></label>
                    <template v-if="form.country === 'ID'">
                        <label>Provinsi <select v-model="form.provinceid" @change="regionChange(1)"><option value="">Pilih provinsi</option><option v-for="region in regions.provinces" :key="region.id" :value="region.id">{{ region.name }}</option></select></label>
                        <label>Kabupaten/Kota <select v-model="form.cityid" @change="regionChange(2)"><option value="">Pilih kabupaten/kota</option><option v-for="region in regions.cities" :key="region.id" :value="region.id">{{ region.name }}</option></select></label>
                        <label>Kecamatan <select v-model="form.districtid" @change="regionChange(3)"><option value="">Pilih kecamatan</option><option v-for="region in regions.districts" :key="region.id" :value="region.id">{{ region.name }}</option></select></label>
                        <label>Kelurahan <select v-model="form.villageid" @change="regionChange(4)"><option value="">Pilih kelurahan</option><option v-for="region in regions.villages" :key="region.id" :value="region.id">{{ region.name }}</option></select></label>
                        <label>Kode pos <select v-model="form.zip"><option value="">Pilih kode pos</option><option v-for="region in regions.postcodes" :key="region.id" :value="region.id">{{ region.name }}</option></select></label>
                    </template>
                    <template v-else><label>Provinsi/negara bagian <input v-model="form.state" required></label><label>Kota <input v-model="form.city" required></label><label>Kode pos <input v-model="form.zip" required></label></template>
                    <label class="wide">Alamat utama <input v-model="form.address1" autocomplete="street-address" required></label>
                    <label class="wide">Alamat tambahan <input v-model="form.address2"></label>
                </div>
                <div v-else-if="step === 3" class="sf-register__grid">
                    <label>Foto KTP <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('ktp', $event)"><small>JPG, PNG, atau PDF. Maksimal 5 MB.</small></label>
                    <template v-if="isCompany">
                        <label>Dokumen NIB <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('nibcompany', $event)"></label>
                        <label>Dokumen NPWP perusahaan <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('npwpcompany', $event)"></label>
                        <label>Akta pendirian <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('deed', $event)"></label>
                        <label>SK pendirian <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('establishment', $event)"></label>
                    </template>
                    <label v-else>Dokumen NPWP pribadi <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setFile('npwppersonal', $event)"></label>
                </div>
                <div v-else-if="step === 4" class="sf-register__grid">
                    <label>Nama bank <input v-model="form.bankname" required></label><label>Cabang bank <input v-model="form.bankbranch" required></label>
                    <label>Nama pemilik rekening <input v-model="form.bankaccountname" required></label><label>Nomor rekening <input v-model="form.bankaccountnumber" inputmode="numeric" required></label>
                </div>
                <div v-else-if="step === 5" class="sf-register__grid">
                    <label>Kata sandi <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required minlength="8"><small>Minimal 8 karakter.</small></label>
                    <label>Konfirmasi kata sandi <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required></label>
                    <label class="sf-register__check wide"><input v-model="showPassword" type="checkbox"> Tampilkan kata sandi</label>
                </div>
                <div v-else class="sf-register__review">
                    <iframe :src="termsUrl" :title="'Syarat dan ketentuan ' + appName"></iframe>
                    <h2>Tanda tangan kontrak</h2><p>Tandatangani pada bidang putih atau unggah gambar tanda tangan.</p>
                    <canvas ref="signatureCanvas" width="600" height="190" aria-label="Bidang tanda tangan" @pointerdown="startDraw" @pointermove="draw" @pointerup="endDraw" @pointercancel="endDraw"></canvas>
                    <div class="sf-register__signature-actions"><button type="button" @click="clearSignature">Hapus tanda tangan</button><label>Unggah tanda tangan <input type="file" accept="image/png,image/jpeg" @change="uploadSignature"></label></div>
                    <label class="sf-register__check"><input v-model="form.contract_agree" type="checkbox"> Saya telah membaca dan menyetujui <a :href="termsUrl" target="_blank" rel="noopener">syarat dan ketentuan</a>.</label>
                    <label class="sf-register__check"><input v-model="form.stampfee_agree" type="checkbox"> Saya bersedia menanggung biaya eMeterai.</label>
                </div>
            </div>
            <div class="sf-register__actions"><div v-if="step === 0" class="sf-register__action-note"><span class="material-icons-outlined">verified_user</span>Data kamu aman & terlindungi</div><button v-else type="button" class="secondary" :disabled="busy" @click="goTo(step - 1)">← Kembali</button><button v-if="step < 6" type="button" class="primary" @click="goTo(step + 1)">Lanjutkan <span>→</span></button><button v-else type="button" class="primary" :disabled="busy" @click="submit">{{ busy ? 'Memproses…' : 'Daftar sekarang' }}</button></div>
        </section>
    </main>
</template>
