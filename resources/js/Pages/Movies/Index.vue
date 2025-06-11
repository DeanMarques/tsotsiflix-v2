<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout 
        :genres="genres"
        @genre-selected="selectedGenre = String($event)">
        <!-- Hero Carousel Section -->
        <div class="position-relative hero-section">
            <template v-for="(movie, index) in carouselMovies" :key="movie.id">
                <div v-show="activeSlide === index" 
                     class="position-absolute w-100 h-100 carousel-item-fade"
                     :class="{ 'active': activeSlide === index }">
                    <img 
                        :src="movie.backdrop_path" 
                        :alt="movie.title"
                        class="w-100 h-100 object-fit-cover"
                    />
                    <div class="gradient-overlay"></div>
                    <div class="position-absolute bottom-0 start-0 p-5 text-white col-12 col-md-6">
                        <h1 class="hero-title mb-3">{{ movie.title }}</h1>
                        <p class="hero-overview mb-4">{{ movie.overview }}</p>
                        <div class="d-flex gap-3">
                            <button 
                                class="btn btn-light btn-lg netflix-button"
                                @click="selectedMovie = movie; showTrailerModal = true"
                            >
                                <i class="bi bi-info-circle me-2"></i> More Info
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Carousel Controls -->
            <button 
                class="carousel-control prev"
                @click="activeSlide = (activeSlide - 1 + carouselMovies.length) % carouselMovies.length"
            >
                <i class="bi bi-chevron-left fs-1"></i>
            </button>
            <button 
                class="carousel-control next"
                @click="activeSlide = (activeSlide + 1) % carouselMovies.length"
            >
                <i class="bi bi-chevron-right fs-1"></i>
            </button>
        </div>

        <!-- Movies Grid -->
        <div class="movies-container">
            <div class="container-fluid px-4 py-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">{{ sectionHeading }}</h2>
                    <Link
                        :href="route('movies.scan')"
                        method="post"
                        as="button"
                        class="btn btn-outline-light netflix-button-outline"
                    >
                        <i class="bi bi-plus-lg me-2"></i>
                        Add Movies
                    </Link>
                </div>

                <!-- Movies grid with proper spacing -->
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">
                    <div v-for="movie in movies" :key="movie.id" class="col">
                        <div 
                            class="movie-card"
                            @click="selectedMovie = movie; showTrailerModal = true"
                        >
                            <img 
                                :src="movie.poster_path" 
                                :alt="movie.title"
                                class="movie-poster"
                            />
                            <div class="movie-overlay">
                                <h5 class="movie-title">{{ movie.title }}</h5>
                                <button class="btn btn-light btn-sm movie-info-btn">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div v-if="loading" class="text-center mt-4">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <!-- Intersection observer target -->
                <div ref="observerTarget" class="py-4"></div>
            </div>
        </div>

        <!-- Movie Modal -->
        <VideoModal 
            :show="showTrailerModal"
            :movie="selectedMovie"
            :initial-mode="playMode"
            @close="showTrailerModal = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VideoModal from '@/Components/VideoModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';

const props = defineProps({
    movies: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1
        })
    },
    genres: {
        type: Array,
        default: () => []
    },
    carouselMovies: {
        type: Array,
        default: () => []
    }
});

const showTrailerModal = ref(false);
const selectedMovie = ref(null);
const activeSlide = ref(0);
const selectedGenre = ref('all');
const playMode = ref('info');

// Debug watch for selectedGenre
watch(selectedGenre, (newValue) => {
    console.log('Selected genre changed:', newValue);
});

const loading = ref(false);
const observerTarget = ref(null);
const allMovies = ref([]);

// Update allMovies when props.movies changes
watch(() => props.movies, (newMovies) => {
    if (newMovies.current_page === 1) {
        // Reset movies array if it's the first page
        allMovies.value = [...newMovies.data];
    } else {
        // Append movies if it's not the first page
        allMovies.value = [...allMovies.value, ...newMovies.data];
    }
}, { immediate: true });

// Movies is now from our accumulated array
const movies = computed(() => allMovies.value);

// Watch for genre changes to reset pagination
watch(selectedGenre, (newValue) => {
    // Reset movies array when genre changes
    allMovies.value = [];
    router.get('/', { 
        genre: newValue === 'all' ? null : newValue 
    }, {
        preserveState: true,
        preserveScroll: false,
        replace: true
    });
});

// Setup intersection observer for infinite scroll
onMounted(() => {
    // Auto advance carousel
    setInterval(() => {
        activeSlide.value = (activeSlide.value + 1) % props.carouselMovies.length;
    }, 8000);

    // Setup infinite scroll
    useIntersectionObserver(observerTarget, ([{ isIntersecting }]) => {
        if (isIntersecting && !loading.value && props.movies.next_page_url) {
            loading.value = true;
            router.get(props.movies.next_page_url, {
                genre: selectedGenre.value === 'all' ? null : selectedGenre.value
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                }
            });
        }
    });
});

// Get the current section heading
const sectionHeading = computed(() => {
    if (selectedGenre.value === 'all') {
        return 'All Movies';
    }
    const genre = props.genres.find(g => g.id.toString() === selectedGenre.value);
    return genre ? genre.name : 'Movies';
});
</script>

<style scoped>
.hero-section {
    height: 80vh;
    margin-bottom: -100px;
}

.gradient-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        to bottom,
        transparent 0%,
        rgba(0,0,0,0.4) 40%,
        rgba(0,0,0,0.9) 80%,
        rgba(0,0,0,1) 100%
    );
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.hero-overview {
    font-size: 1.25rem;
    max-width: 600px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.carousel-item-fade {
    transition: opacity 0.8s ease-in-out;
}

.carousel-item-fade.active {
    opacity: 1;
}

.carousel-item-fade:not(.active) {
    opacity: 0;
}

.movies-container {
    background: linear-gradient(to bottom, #141414 0%, #000000 100%);
    min-height: 100vh;
    color: white;
    padding-top: 100px;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 500;
    color: #e5e5e5;
}

.movie-card {
    position: relative;
    border-radius: 4px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    aspect-ratio: 2/3;
}

.movie-poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.3s ease;
}

.movie-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.9));
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.movie-card:hover {
    transform: scale(1.05);
    z-index: 2;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

.movie-card:hover .movie-overlay {
    transform: translateY(0);
}

.movie-card:hover .movie-poster {
    filter: brightness(0.7);
}

.movie-title {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: white;
}

.movie-info-btn {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.movie-card:hover .movie-info-btn {
    opacity: 1;
}

.netflix-button {
    font-weight: 500;
    padding: 0.8rem 2rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.netflix-button:hover {
    transform: scale(1.05);
}

.netflix-button-outline {
    color: white;
    border: 2px solid rgba(255,255,255,0.7);
    padding: 0.5rem 1.5rem;
    transition: all 0.2s ease;
}

.netflix-button-outline:hover {
    border-color: white;
    background: rgba(255,255,255,0.1);
}

.carousel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 60px;
    height: 100px;
    border: none;
    background: rgba(0,0,0,0.5);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 2;
}

.carousel-control:hover {
    background: rgba(0,0,0,0.7);
    opacity: 1;
}

.hero-section:hover .carousel-control {
    opacity: 1;
}

.carousel-control.prev {
    left: 0;
    border-radius: 0 4px 4px 0;
}

.carousel-control.next {
    right: 0;
    border-radius: 4px 0 0 4px;
}

@media (max-width: 768px) {
    .hero-section {
        height: 60vh;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-overview {
        font-size: 1rem;
    }

    .carousel-control {
        opacity: 0.7;
        width: 40px;
        height: 60px;
    }
}
</style>
