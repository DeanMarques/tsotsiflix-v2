<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout 
        :genres="genres"
        @genre-selected="selectedGenre = $event ? Number($event) : null">
        <!-- Hero Carousel Section -->
        <div v-if="!selectedGenre && !searchQuery && carouselMovies.length !== 0" 
             id="heroCarousel" 
             class="carousel slide vh-80" 
             data-bs-ride="carousel">
            <div class="carousel-inner h-100">
                <div v-for="(movie, index) in carouselMovies" 
                     :key="movie.id"
                     class="carousel-item h-100"
                     :class="{ 'active': index === 0 }">
                    <img :src="movie.backdrop_path" 
                         :alt="movie.title"
                         class="w-100 h-100 hero-image-bg">
                    <div class="carousel-caption">
                        <div class="position-relative text-start col-12 col-md-6">
                            <h1 class="display-4 fw-bold mb-3">{{ movie.title }}</h1>
                            <button class="btn btn-dark btn-lg"
                                    @click="openMovieModal(movie)">
                                <i class="bi bi-info-circle me-2"></i> More Info
                            </button>
                        </div>
                    </div>
                    
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
            <div class="container-fluid mt-4">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h2 class="h3 text-white">{{ sectionHeading }}</h2>
                    <div class="d-flex ms-2 align-items-center">
                        <div class="input-group">
                            <input 
                                type="search" 
                                class="form-control bg-dark text-white border-secondary" 
                                placeholder="Search movies..." 
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

                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">
                    <div v-for="movie in allMovies" :key="movie.id" class="col">
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

                <!-- <div ref="observerTarget" style="height: 20px; margin-top: 20px;"></div> -->

                <div v-if="loading" class="text-center mt-4">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="allMovies.length === 0" class="text-center text-light mt-4">
                    <h3>No movies found</h3>
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
import { ref, computed, onMounted,onUnmounted, watch, nextTick } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';
import { router } from '@inertiajs/vue3'

const props = defineProps({
    movies: {
        type: Object,
        required: true
    },
    genres: {
        type: Array,
        default: () => []
    },
    carouselMovies: Array,
    filters: Object
});

// State Management
const showTrailerModal = ref(false);
const selectedMovie = ref(null);
const loading = ref(false);
const observerTarget = ref(null);
const allMovies = ref([...props.movies.data]);
const currentPage = ref(props.movies.current_page);
const lastPage = ref(props.movies.last_page);
const totalMovies = ref(props.movies.total);
const selectedGenre = ref(props.filters?.genre ? Number(props.filters.genre) : null);
const searchQuery = ref(props.filters?.search || '');

// First, add this ref to track the observer
const intersectionObserver = ref(null);

// Section heading
const sectionHeading = computed(() => {
    if (searchQuery.value) {
        return `Search Results: ${searchQuery.value}`;
    }
    
    console.log('Genre Selection:', {
        selectedGenre: selectedGenre.value,
        genres: props.genres,
        found: props.genres.find(g => g.id === selectedGenre.value)
    });
    
    if (!selectedGenre.value) {
        return 'All Movies';
    }
    
    const genre = props.genres.find(g => g.id === selectedGenre.value);
    return genre ? genre.name : 'Movies';
});

const openMovieModal = (movie) => {
    selectedMovie.value = movie;
    showTrailerModal.value = true;
};

// Replace the existing onMounted and watch handlers with these updated versions
const setupObserver = () => {
    // Cleanup existing observer
    if (intersectionObserver.value) {
        intersectionObserver.value.stop();
    }

    console.log('Setting up new observer with state:', {
        currentPage: currentPage.value,
        lastPage: lastPage.value,
        genre: selectedGenre.value
    });

    intersectionObserver.value = useIntersectionObserver(
        observerTarget,
        ([{ isIntersecting }]) => {
            const canLoadMore = !loading.value && 
                              currentPage.value < lastPage.value && 
                              isIntersecting;

            console.log('Observer check:', {
                isIntersecting,
                currentPage: currentPage.value,
                lastPage: lastPage.value,
                loading: loading.value,
                canLoadMore,
                genre: selectedGenre.value
            });

            if (canLoadMore) {
                loading.value = true;
                const nextPage = currentPage.value + 1;

                console.log('Loading next page:', {
                    page: nextPage,
                    genre: selectedGenre.value
                });

                router.post(
                    route('dashboard'),
                    { 
                        genre: selectedGenre.value,
                        search: searchQuery.value,
                        page: nextPage
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: (response) => {
                            const { movies } = response.props;
                            allMovies.value = [...allMovies.value, ...movies.data];
                            currentPage.value = movies.current_page;
                            lastPage.value = movies.last_page;
                            totalMovies.value = movies.total;
                            loading.value = false;

                            console.log('Loaded more movies:', {
                                newCount: movies.data.length,
                                total: allMovies.value.length,
                                genre: selectedGenre.value
                            });
                        }
                    }
                );
            }
        },
        { threshold: 0.1, rootMargin: '100px' }
    );
};

// Update the watch handler
watch([selectedGenre, searchQuery], () => {
    console.log('Filter changed:', { 
        genre: selectedGenre.value,
        search: searchQuery.value
    });
    
    loading.value = true;
    allMovies.value = [];
    currentPage.value = 1;
    
    router.post(
        route('dashboard'),
        { 
            genre: selectedGenre.value,
            search: searchQuery.value,
            page: 1
        },
        {
            preserveState: true,
            preserveScroll: false,
            onSuccess: (page) => {
                const { movies } = page.props;
                allMovies.value = [...movies.data];
                currentPage.value = movies.current_page;
                lastPage.value = movies.last_page;
                totalMovies.value = movies.total;
                loading.value = false;

                console.log('Filter results loaded:', {
                    count: movies.data.length,
                    currentPage: movies.current_page,
                    lastPage: movies.last_page,
                    genre: selectedGenre.value
                });

                // Setup new observer after filter change
                nextTick(() => {
                    setupObserver();
                });
            }
        }
    );
}, { immediate: true });

// Initial observer setup
onMounted(() => {
    setupObserver();
});

// Cleanup
onUnmounted(() => {
    if (intersectionObserver.value) {
        intersectionObserver.value.stop();
    }
});
</script>

<style scoped>
.vh-80 {
    height: 80vh;
}

.hero-image-bg {
    object-fit: cover;
    filter: brightness(0.7);
}

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


@media (max-width: 768px) {
    .vh-80 {
        height: 60vh;
    }
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