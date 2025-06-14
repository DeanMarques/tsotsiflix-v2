<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout 
        :genres="genres"
        @genre-selected="selectedGenre = String($event)">
        <!-- Hero Carousel Section -->
        <div v-if="props.currentGenre === null" id="heroCarousel" class="carousel slide vh-80" data-bs-ride="carousel">
            <div class="carousel-inner h-100">
                <div v-for="(movie, index) in carouselMovies" 
                     :key="movie.id"
                     class="carousel-item h-100"
                     :class="{ 'active': index === 0 }">
                    <img :src="movie.backdrop_path" 
                         :alt="movie.title"
                         class="w-100 h-100">
                    <div class="carousel-caption">
                        <div class="position-relative text-start col-12 col-md-6">
                            <h1 class="display-4 fw-bold mb-3">{{ movie.title }}</h1>
                            <p class="lead mb-4">{{ movie.overview }}</p>
                            <button class="btn btn-dark btn-lg"
                                    @click="openMovieModal(movie)">
                                <i class="bi bi-info-circle me-2"></i> More Info
                            </button>
                        </div>
                    </div>
                    <div class="overlay-gradient"></div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <!-- Movies Grid -->
        <div class="bg-dark min-vh-100 py-5 overflow-auto scrollbar-dark">
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3 text-white">{{ sectionHeading }}</h2>                   
                </div>

                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">
                    <div v-for="movie in movies" :key="movie.id" class="col">
                        <div class="card bg-dark text-white h-100 movie-card" data-bs-toggle="modal" data-bs-target="#videoModal"
                            @click="openMovieModal(movie)">
                            <img :src="movie.poster_path" 
                                 :alt="movie.title"
                                 class="card-img h-100 object-fit-cover">
                            <div class="card-img-overlay d-flex flex-column bg-dark-gradient opacity-0">
                                <h5 class="card-title">{{ movie.title }} <span>
                                    <i class="bi bi-info-circle"></i>
                                </span></h5>
                               
                            </div>
                        </div>
                    </div>
                </div>

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
    },
    currentGenre: {
        type: [String, Number, null],
        default: null
    }
});

const showTrailerModal = ref(false);
const selectedMovie = ref(null);
const activeSlide = ref(0);
// Initialize selectedGenre from props
const selectedGenre = ref(props.currentGenre || 'all');
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
    // Reset movies array and go back to page 1 when genre changes
    allMovies.value = [];
    router.get(route('dashboard'), { 
        genre: newValue === 'all' ? null : newValue,
        page: 1
    }, {
        preserveState: false, // Don't preserve state to ensure clean reload
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            loading.value = false; // Reset loading state
        }
    });
});

// Setup intersection observer for infinite scroll
onMounted(() => {
    // Auto advance carousel
    // setInterval(() => {
    //     activeSlide.value = (activeSlide.value + 1) % props.carouselMovies.length;
    // }, 8000);

    // Setup infinite scroll
    useIntersectionObserver(observerTarget, ([{ isIntersecting }]) => {
        if (isIntersecting && !loading.value && props.movies.current_page < props.movies.last_page) {
            loading.value = true;
            
            const queryParams = {
                page: props.movies.current_page + 1
            };
            
            if (props.currentGenre) {
                queryParams.genre = props.currentGenre;
            }
            
            router.get(route('dashboard'), queryParams, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                },
                onError: () => {
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

const openMovieModal = (movie) => {
    selectedMovie.value = {
        ...movie,
        credits: movie.credits || { cast: [] },
        genres: movie.genres || []
    };
    showTrailerModal.value = true;
};
</script>


