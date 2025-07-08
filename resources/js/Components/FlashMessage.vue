<template>
    <div v-if="show" 
         class="flash-message"
         :class="flash.type">
        {{ flash.message }}
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const show = ref(false);
const flash = ref(null);
const timeout = ref(null);

watch(() => usePage().props.flash, (newFlash) => {
    if (newFlash) {
        flash.value = newFlash;
        show.value = true;
        
        if (timeout.value) clearTimeout(timeout.value);
        
        timeout.value = setTimeout(() => {
            show.value = false;
        }, 3000);
    }
});
</script>

<style scoped>
.flash-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem;
    border-radius: 4px;
    z-index: 1050;
    animation: slideIn 0.3s ease-out;
}

.success {
    background-color: #198754;
    color: white;
}

.error {
    background-color: #dc3545;
    color: white;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>