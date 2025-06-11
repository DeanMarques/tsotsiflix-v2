<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const showContent = ref(false);
const introFinished = ref(false);

onMounted(() => {
    // Start the intro sequence
    setTimeout(() => {
        showContent.value = true;
    }, 4000);
    
    // Mark intro as finished to trigger main content animation
    setTimeout(() => {
        introFinished.value = true;
    }, 5000);
});
</script>

<template>
    <Head title="Welcome to Tsotsiflix" />
    <div class="min-vh-100 bg-black position-relative">
        <!-- Netflix style intro animation -->
        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-black" 
             :class="{ 'opacity-0 invisible': showContent }"
             style="z-index: 1000; transition: opacity 0.5s ease-out;">
            <div class="netflix-logo">
                Tsotsiflix
            </div>
        </div>

        <!-- Main content -->
        <div class="opacity-0" :class="{ 'opacity-100': introFinished }" style="transition: opacity 1s ease-in">
            <div class="min-vh-100 position-relative" 
                 style="background: url('https://assets.nflxext.com/ffe/siteui/vlv3/ab4b0b22-2ddf-4d48-ae88-c201ae0267e2/0efe6360-4f6d-4b10-beb6-81e0762cfe81/ZA-en-20231030-popsignuptwoweeks-perspective_alpha_website_large.jpg') center/cover no-repeat">
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient"></div>
                <div class="container position-relative">
                    <div class="row min-vh-100 align-items-center">
                        <div class="col-md-7 text-white text-center text-md-start">
                            <h1 class="display-1 fw-bold mb-4 text-shadow netflix-title">Welcome to Tsotsiflix</h1>                            
                            <div class="d-flex gap-3 justify-content-center justify-content-md-start">
                                <Link v-if="canLogin" 
                                      :href="route('login')" 
                                      class="btn btn-netflix px-5 py-3 fw-bold fs-5">
                                    Sign In
                                </Link>                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>                   
        </div>
    </div>
</template>

<style scoped>
.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.bg-gradient {
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0.8) 100%);
    pointer-events: none;
}

.netflix-logo {
    color: #E50914;
    font-size: 7rem;
    font-weight: 700;
    text-transform: uppercase;
    animation: netflixStyle 3s ease-out;
}

.netflix-title {
    color: #FFFFFF;
}

.btn-netflix {
    background-color: #E50914;
    color: white;
    transition: all 0.2s ease-in-out;
    border: none;
}

.btn-netflix:hover {
    background-color: #f40612;
    transform: scale(1.05);
    color: white;
}





@keyframes netflixStyle {
    0% {
        opacity: 0;
        transform: scale(10);
    }
    25% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        transform: scale(1);
    }
    75% {
        transform: scale(1);
    }
    100% {
        transform: scale(0);
    }
}

@media (max-width: 768px) {
    .netflix-title {
        font-size: 3rem;
    }   
   
}
</style>
