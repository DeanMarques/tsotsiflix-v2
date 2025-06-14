<template>
    <div v-if="show" class="modal fade show d-block scrollbar-dark" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark text-white d-flex flex-column h-100">
                <!-- Header - Consistent across all states -->
                <div class="modal-header border-0 flex-shrink-0">
                    <div class="d-flex align-items-center gap-3">
                        <button v-if="showVideo" 
                                class="btn btn-dark rounded-circle" 
                                @click="showInfo">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button v-else 
                                type="button" 
                                class="btn-close btn-close-white" 
                                @click="emit('close')" 
                                aria-label="Close">
                        </button>
                        <h5 class="modal-title text-white mb-0">{{ movie.title }}</h5>
                    </div>
                </div>

                <!-- Modal Body - Changes based on state -->
                <div class="modal-body p-0 flex-grow-1 d-flex flex-column">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="d-flex justify-content-center align-items-center min-vh-100">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="hasError" class="d-flex justify-content-center align-items-center min-vh-100">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Unable to load {{ playMode === 'trailer' ? 'trailer' : 'movie' }}
                        </div>
                    </div>

                    <!-- Video Player -->
                    <div v-else-if="showVideo" class="bg-black">
                        <video v-if="playMode === 'movie'"
                            class="w-100"
                            style="max-height: calc(100vh - 56px); aspect-ratio: 16/9;"
                            :src="videoUrl"
                            controlsList="nodownload noplaybackrate nopictureinpicture"
                            disablePictureInPicture
                            oncontextmenu="return false;"
                            @contextmenu.prevent
                            controls
                            autoplay
                            preload="auto"
                        ></video>
                        <iframe v-else-if="playMode === 'trailer'"
                            class="w-100"
                            style="max-height: calc(100vh - 56px); aspect-ratio: 16/9;"
                            :src="videoUrl"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>

                    <!-- Movie Details Section -->
                    <div v-else>
                        <!-- Hero Section with Backdrop -->
                        <div class="position-relative" style="height: 40vh; background-size: cover;" 
                             :style="{ backgroundImage: `url(${movie.backdrop_path})` }">
                            <div class="position-absolute w-100 h-100 bg-gradient-dark"></div>
                            <div class="position-relative h-100 d-flex flex-column justify-content-end p-4">
                                <h1 class="display-4 fw-bold mb-3">{{ movie.title }}</h1>
                                <div class="d-flex flex-wrap gap-3 mb-4">
                                    <span v-if="movie.release_date" class="fs-5">
                                        {{ new Date(movie.release_date).getFullYear() }}
                                    </span>
                                    <span v-if="movie.runtime" class="fs-5">
                                        {{ movie.runtime }} min
                                    </span>
                                    <span v-for="genre in movie.genres" :key="genre.id" class="fs-5">
                                        {{ genre.name }}
                                    </span>
                                </div>
                                <div class="d-flex gap-3">
                                    <button class="btn btn-dark btn-lg"
                                        @click="playMovie"
                                        :disabled="!movie?.local_path">
                                        <i class="bi bi-play-fill me-2"></i> Play
                                    </button>
                                    <button class="btn btn-dark btn-lg"
                                        @click="playTrailer"
                                        :disabled="!movie?.trailer_url">
                                        <i class="bi bi-film me-2"></i> Trailer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="container-fluid py-4">
                            <div class="row">
                                <div class="col-12 col-lg-8">
                                    <p class="lead">{{ movie.overview }}</p>
                                    <div v-if="movie.director" class="mt-4">
                                        <p class="lead">Director: {{ movie.director }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cast Section -->
                            <div v-if="movie.cast?.length" class="mt-4">
                                <p class="lead mb-3">Cast</p>
                                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">                                    
                                    <div v-for="actor in movie.cast.slice(0, 8)" :key="actor.id" class="col text-center">
                                        <img :src="actor.profile_path ? 'https://image.tmdb.org/t/p/w185' + actor.profile_path : 'https://via.placeholder.com/185x278'"
                                            :alt="actor.name"
                                            class="rounded-circle mb-2"
                                            width="64"
                                            height="64"
                                            style="object-fit: cover;">
                                        <h6 class="mb-0 small">{{ actor.name }}</h6>
                                        <p class="text-secondary small">{{ actor.character }}</p>
                                    </div>                                   
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    movie: Object
});

const emit = defineEmits(['close']);

const videoUrl = ref('');
const isLoading = ref(false);
const hasError = ref(false);
const showVideo = ref(false);
const playMode = ref('info'); // 'info', 'trailer', or 'movie'

watch(() => props.show, (isVisible) => {
    // Add/remove body class to prevent background scrolling
    if (isVisible) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}, { immediate: true });

watch(() => props.movie, async (newMovie) => {
    if (newMovie) {
        isLoading.value = true;
        hasError.value = false;
        
        // Always start in info mode
        playMode.value = 'info';
        showVideo.value = false;
        
        isLoading.value = false;
    } else {
        videoUrl.value = '';
        hasError.value = false;
        showVideo.value = false;
        playMode.value = 'info';
    }
}, { immediate: true });

const playTrailer = () => {
    if (props.movie?.trailer_url) {
        videoUrl.value = props.movie.trailer_url;
        playMode.value = 'trailer';
        showVideo.value = true;
        hasError.value = false;
    } else {
        hasError.value = true;
        console.log('No trailer URL available for:', props.movie?.title);
    }
};

const playMovie = async () => {
    if (props.movie?.local_path) {
        try {
            isLoading.value = true;
            const response = await fetch(`/movies/${props.movie.id}/secure-url`);
            const data = await response.json();
            
            // Use the full URL from the response
            videoUrl.value = data.url;
            playMode.value = 'movie';
            showVideo.value = true;
            hasError.value = false;
        } catch (error) {
            hasError.value = true;
            console.error('Error loading movie:', error);
        } finally {
            isLoading.value = false;
        }
    }
};

const showInfo = () => {
    if (showVideo.value) {
        // If we're in video mode, go back to info
        showVideo.value = false;
        playMode.value = 'info';
    } else {
        // If we're in info mode, emit close to go back to dashboard
        emit('close');
    }
};
</script>

<style scoped>
/* Custom styles to enhance Bootstrap */
.bg-gradient-dark {
    background: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.9));
}

.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: scale(1.05);
}

/* Custom scrollbar styling */
/* .scrollbar-dark {
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
} */

@media (max-width: 768px) {
    .btn-lg {
        width: 100%;
    }
}
</style>
