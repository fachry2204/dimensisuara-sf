<script setup>
import { ref } from 'vue';

defineProps({
    action: String,
    csrf: String,
    oldEmail: String,
    oldRemember: Boolean,
    emailError: String,
    passwordError: String,
    forgotUrl: String,
    registerUrl: String,
    homeUrl: String,
    logoUrl: String,
});

const showPassword = ref(false);
</script>

<template>
    <div class="sf-form-panel">
        <a class="sf-logo" :href="homeUrl" aria-label="SoundFresh — beranda"><img :src="logoUrl" alt="SoundFresh"></a>
        <h2 id="login-heading">Selamat datang kembali</h2>
        <p class="sf-form-panel__intro">Masuk untuk melanjutkan perjalanan musikmu.</p>
        <form method="POST" :action="action">
            <input type="hidden" name="_token" :value="csrf">
            <div class="sf-field">
                <label for="email">Alamat email</label>
                <input id="email" name="email" type="email" class="sf-input" :class="{ 'is-invalid': emailError }" placeholder="nama@email.com" :value="oldEmail" required autocomplete="email" autofocus :aria-invalid="emailError ? 'true' : undefined" :aria-describedby="emailError ? 'email-error' : undefined">
                <span v-if="emailError" id="email-error" class="sf-error" role="alert">{{ emailError }}</span>
            </div>
            <div class="sf-field">
                <label for="password">Kata sandi</label>
                <div class="sf-input-wrap">
                    <input id="password" name="password" :type="showPassword ? 'text' : 'password'" class="sf-input sf-password-input" :class="{ 'is-invalid': passwordError }" placeholder="••••••••" required autocomplete="current-password" :aria-invalid="passwordError ? 'true' : undefined" :aria-describedby="passwordError ? 'password-error' : undefined">
                    <button class="sf-password-toggle" type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showPassword"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3.5-5.5 9-5.5 9 5.5 9 5.5-3.5 5.5-9 5.5S3 12 3 12Z"/><circle cx="12" cy="12" r="2.5"/><path v-if="!showPassword" d="M4 4l16 16"/></svg></button>
                </div>
                <span v-if="passwordError" id="password-error" class="sf-error" role="alert">{{ passwordError }}</span>
            </div>
            <div class="sf-options">
                <label class="sf-remember" for="remember"><input id="remember" type="checkbox" name="remember" value="1" :checked="oldRemember">Ingat saya</label>
                <a v-if="forgotUrl" :href="forgotUrl">Lupa kata sandi?</a>
            </div>
            <button class="sf-submit" type="submit">Masuk</button>
        </form>
        <p v-if="registerUrl" class="sf-login-register"><span>Belum punya akun? <a :href="registerUrl">Daftar sekarang</a></span></p>
    </div>
</template>
