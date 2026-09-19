import { createApp } from 'vue';
import LoginForm from './vue/LoginForm.vue';
import AppEnhancements from './vue/AppEnhancements.vue';
import RegisterWizard from './vue/RegisterWizard.vue';
import '../css/soundfresh-vue.css';
import '../css/register-vue.css';

const login = document.querySelector('[data-vue-login]');
if (login) {
    createApp(LoginForm, JSON.parse(login.dataset.vueLogin)).mount(login);
}

const app = document.querySelector('[data-vue-app]');
if (app) {
    createApp(AppEnhancements).mount(app);
}

const register = document.querySelector('[data-vue-register]');
if (register) {
    createApp(RegisterWizard, JSON.parse(register.dataset.vueRegister)).mount(register);
}
