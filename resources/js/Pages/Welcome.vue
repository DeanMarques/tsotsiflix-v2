<template>
    <div class="min-vh-100 container-fluid bg-dark text-white d-flex align-items-center justify-content-center">
        <div class="text-center">            
            <TsotsiflixLogo className="text-decoration-none mb-3 fs-1" v-show="showTitle" /> 
            <p class="text-white mb-4 fs-5" v-show="showTitle">
                Work in Progress V2 version..
            </p>
            <!-- Sign in button with fade-in animation -->
            <div v-show="showButton" class="fade-in mt-2">
                <Link :href="route('login')" class="btn btn-danger btn-lg px-5">
                    Sign In
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import TsotsiflixLogo from '@/Components/TsotsiflixLogo.vue';

const showTitle = ref(true);
const showButton = ref(false);

onMounted(() => {
    // Show button after 3 seconds
    setTimeout(() => {
        showButton.value = true;
    }, 3000);
});
</script>

<style scoped>
.fade-in {
    animation: fadeIn 1s ease-in;
}

@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>
