<script setup>
import { computed, ref } from 'vue';

defineProps({ appName: String });

const hour = new Date().getHours();
const greeting = computed(() => hour < 11 ? 'Selamat pagi' : hour < 15 ? 'Selamat siang' : hour < 19 ? 'Selamat sore' : 'Selamat malam');
const dismissed = ref(sessionStorage.getItem('sf-welcome-dismissed') === '1');
function dismiss() {
    dismissed.value = true;
    sessionStorage.setItem('sf-welcome-dismissed', '1');
}
</script>

<template>
    <div v-if="!dismissed" class="sf-welcome" role="status">
        <div><span class="sf-welcome__eyebrow">{{ appName }} STUDIO</span><p><strong>{{ greeting }}.</strong> Ruang kerja musikmu siap digunakan.</p></div>
        <button type="button" @click="dismiss" aria-label="Tutup sambutan">×</button>
    </div>
</template>
