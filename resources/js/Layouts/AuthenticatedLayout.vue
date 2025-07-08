<template>
    <div>
        <nav class="navbar navbar-expand-lg fixed-top transition-all"
             :class="[isScrolled ? 'navbar-scrolled' : 'navbar-transparent']">
            <div class="container-fluid px-4">
                <!-- Logo -->
                <Link :href="route('dashboard')" class="navbar-brand">
                    <TsotsiflixLogo className="text-decoration-none" />
                </Link>

                <!-- Hamburger -->
                <button
                    class="navbar-toggler"
                    type="button"
                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                    :aria-expanded="showingNavigationDropdown"
                    aria-controls="navbarContent"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation -->
                <div class="collapse navbar-collapse"
                     :class="{ 'show': showingNavigationDropdown }"
                     id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <Link :href="route('dashboard')"
                                  class="nav-link text-white"
                                  :class="{ 'active': route().current('dashboard') }">
                                Home
                            </Link>
                        </li>
                        <li class="nav-item dropdown">
                            <button class="nav-link text-white dropdown-toggle text-light border-0 bg-transparent"
                                    type="button"
                                    id="genreDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                Genres
                            </button>
                            <ul class="dropdown-menu bg-dark"
                                aria-labelledby="genreDropdown">
                                <li>
                                    <button class="dropdown-item text-white"
                                            @click="handleGenreSelect('all')"
                                            :class="{ 'active': selectedGenre === 'all' }">
                                        All Genres
                                    </button>
                                </li>
                                <li v-for="genre in genres" :key="genre.id">
                                    <button class="dropdown-item text-white"
                                            @click="handleGenreSelect(genre.id.toString())"
                                            :class="{ 'active': selectedGenre === genre.id.toString() }">
                                        {{ genre.name }}
                                    </button>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <Link :href="route('watchlist.dashboard')"
                                  class="nav-link text-white"
                                  :class="{ 'active': route().current('watchlist.dashboard') }">
                                Watchlist
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link :href="route('watched.dashboard')"
                                class="nav-link text-white"
                                :class="{ 'active': route().current('watched.dashboard') }">
                                Watched
                            </Link>
                        </li>
                    </ul>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-white text-decoration-none"
                                type="button"
                                id="userDropdown"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            {{ $page.props.auth.user.username }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end bg-dark"
                            aria-labelledby="userDropdown">
                            <li v-if="$page.props.auth.user.is_admin">
                                <Link :href="route('admin.dashboard')"
                                      class="dropdown-item text-white">
                                    Admin
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('logout')"
                                      method="post"
                                      as="button"
                                      class="dropdown-item text-white">
                                    Log Out
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="content-wrapper">
            <FlashMessage />
            <slot />
        </main>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import TsotsiflixLogo from '@/Components/TsotsiflixLogo.vue';
import { Link } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

const props = defineProps({
    genres: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['genre-selected']);

const showingNavigationDropdown = ref(false);
const isScrolled = ref(false);
const selectedGenre = ref('all');

const handleGenreSelect = (genreId) => {
    selectedGenre.value = genreId;
    emit('genre-selected', genreId);
    showingNavigationDropdown.value = false;
};

onMounted(() => {
    window.addEventListener('scroll', () => {
        isScrolled.value = window.scrollY > 50;
    });
});
</script>

<style scoped>
.navbar {
    padding: 1rem 0;
    z-index: 1000;
}

.navbar-transparent {
    background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
}

.navbar-scrolled {
    background-color: #141414;
}

.transition-all {
    transition: all 0.3s ease;
}

.dropdown-menu.bg-dark {
    background-color: rgba(0, 0, 0, 0.9);
}

.dropdown-menu.bg-dark .dropdown-item {
    color: #fff;
}

.dropdown-menu.bg-dark .dropdown-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.dropdown-menu.bg-dark .dropdown-item.active {
    background-color: rgba(255, 255, 255, 0.2);
}

.nav-link {
    font-size: 0.9rem;
    transition: color 0.2s ease;
}

.nav-link:hover {
    color: rgba(255, 255, 255, 0.7) !important;
}

.dropdown-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}
</style>