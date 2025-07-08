<template>
    <Head title="My Watchlist" />

    <AuthenticatedLayout :genres="genres">
        <div class="bg-dark min-vh-100 py-5 overflow-auto scrollbar-dark">
            <div class="container-fluid mt-4">
                <!-- Update header to match Dashboard -->
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h2 class="h3 text-white">My Watchlist</h2>
                    <div class="d-flex ms-2 align-items-center">
                        <div class="input-group">
                            <input 
                                type="search" 
                                class="form-control bg-dark text-white border-secondary" 
                                placeholder="Search watchlist..." 
                                v-model="searchQuery"
                            >
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button"
                                :disabled="loading"
                            >
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Update empty state to match Dashboard style -->
                <div v-if="displayedMovies.length === 0" class="text-center text-white mt-4">
                    <h3>No movies in watchlist</h3>
                    <p class="lead">Start adding movies to your watchlist to see them here.</p>
                    <Link :href="route('dashboard')" class="btn btn-outline-light mt-3">
                        Browse Movies
                    </Link>
                </div>

                <!-- Update grid to match Dashboard -->
                <div v-else class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">
                    <div v-for="movie in displayedMovies" :key="movie.id" class="col">
                        <div class="card bg-dark text-white h-100 movie-card"
                            @click="openMovieModal(movie)">
                            <img :src="movie.poster_path" 
                                 :alt="movie.title"
                                 class="card-img h-100 object-fit-cover">
                            <div class="card-img-overlay d-flex flex-column bg-dark-gradient opacity-0">
                                <h5 class="card-title">{{ movie.title }}
                                    <span><i class="bi bi-info-circle"></i></span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match loading and empty states -->
                <div v-if="loading" class="text-center mt-4">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div ref="observerTarget" class="py-4"></div>
            </div>
        </div>

        <VideoModal 
            :show="showTrailerModal"
            :movie="selectedMovie"            
            @close="showTrailerModal = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VideoModal from '@/Components/VideoModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';

const props = defineProps({
    watchlist: {
        type: Array,
        required: true
    },
    genres: {
        type: Array,
        default: () => []
    }
});

// State Management
const showTrailerModal = ref(false);
const selectedMovie = ref(null);
const searchQuery = ref('');
const loading = ref(false);
const observerTarget = ref(null);
const itemsPerPage = 6;
const currentPage = ref(1);

// Filter movies based on search
const filteredMovies = computed(() => {
    if (!searchQuery.value) return props.watchlist;
    
    const query = searchQuery.value.toLowerCase();
    return props.watchlist.filter(movie => 
        movie.title.toLowerCase().includes(query) ||
        movie.overview.toLowerCase().includes(query)
    );
});

// Paginated movies for display
const displayedMovies = computed(() => {
    const start = 0;
    const end = currentPage.value * itemsPerPage;
    return filteredMovies.value.slice(start, end);
});

// Infinite scroll handler
onMounted(() => {
    useIntersectionObserver(observerTarget, ([{ isIntersecting }]) => {
        if (isIntersecting && 
            !loading.value && 
            displayedMovies.value.length < filteredMovies.value.length) {
            loading.value = true;
            setTimeout(() => {
                currentPage.value++;
                loading.value = false;
            }, 300);
        }
    });
});

const openMovieModal = (movie) => {
    selectedMovie.value = movie;
    showTrailerModal.value = true;
};
</script>

<style scoped>
.bg-dark-gradient {
    background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.9));
    transition: opacity 0.3s;
}

.card:hover .bg-dark-gradient {
    opacity: 1 !important;
}

.card {
    aspect-ratio: 2/3;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

.scrollbar-dark {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.2) rgba(0,0,0,0.2);
}

.scrollbar-dark::-webkit-scrollbar {
    width: 8px;
}

.scrollbar-dark::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.2);
}

.scrollbar-dark::-webkit-scrollbar-thumb {
    background-color: rgba(255,255,255,0.2);
    border-radius: 4px;
}
</style>