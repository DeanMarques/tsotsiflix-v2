<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="container-fluid mt-5 pt-4">
            <div class="row">
                <!-- Collapsible Sidebar -->
                <div class="col-auto px-0">
                    <div id="sidebar" class="collapse collapse-horizontal show border-end bg-dark" style="min-height: calc(100vh - 100px);">
                        <div id="sidebar-nav" class="list-group border-0 rounded-0 text-sm-start min-vh-100">
                            <div class="p-3">
                                <span class="fs-5 text-white">Admin Dashboard</span>
                            </div>
                            <ul class="nav nav-pills flex-column mb-auto">
                                <li class="nav-item">
                                    <a @click.prevent="currentSection = 'movies'" 
                                       href="#" 
                                       :class="['nav-link text-white', currentSection === 'movies' ? 'active' : '']">
                                        <i class="bi bi-film me-2"></i>
                                        Movies
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a @click.prevent="currentSection = 'users'" 
                                       href="#" 
                                       :class="['nav-link text-white', currentSection === 'users' ? 'active' : '']">
                                        <i class="bi bi-people me-2"></i>
                                        Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col">
                    <!-- Toggle Button -->
                    <div class="mb-3">
                        <button class="btn btn-dark" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#sidebar">
                            <i class="bi bi-list"></i>
                            Toggle Sidebar
                        </button>
                    </div>

                    <!-- Movies Section -->
                    <div v-if="currentSection === 'movies'">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h4 mb-0">Movies ({{ movies.total }})</h2>
                            <button @click="scanMovies" class="btn btn-danger" :disabled="form.processing">
                                <i class="bi bi-search me-2"></i>
                                {{ form.processing ? 'Scanning...' : 'Scan for New Movies' }}
                            </button>
                             <button @click="moveMovies" class="btn btn-danger" :disabled="form.processing">
                                <i class="bi bi-search me-2"></i>
                                {{ form.processing ? 'Moving...' : 'Scan for New Movies' }}
                            </button>
                        </div>
                       

                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark">
                                        <thead>
                                            <tr>
                                                <th>Poster</th>
                                                <th>Title</th>
                                                <th>Release Date</th>
                                                <th>Genres</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="movie in movies.data" :key="movie.id">
                                                <td>
                                                    <img 
                                                        :src="movie.poster_path ? `https://image.tmdb.org/t/p/w92${movie.poster_path}` : ''" 
                                                        :alt="movie.title"
                                                        class="img-fluid"
                                                        style="max-width: 50px;"
                                                    >
                                                </td>
                                                <td>{{ movie.title }}</td>
                                                <td>{{ formatDate(movie.release_date) }}</td>
                                                <td>
                                                    <span v-for="genre in movie.genres" 
                                                          :key="genre.id" 
                                                          class="badge bg-secondary me-1">
                                                        {{ genre.name }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr v-if="movies.data.length === 0">
                                                <td colspan="4" class="text-center">No movies found</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Movies Pagination -->
                                <nav v-if="movies.links.length > 3" class="mt-4">
                                    <ul class="pagination">
                                        <li v-for="(link, key) in movies.links" 
                                            :key="key" 
                                            class="page-item"
                                            :class="{ 'active': link.active }">
                                            <Link v-if="link.url" 
                                                  :href="link.url" 
                                                  class="page-link bg-dark text-white border-secondary"
                                                  :class="{ 'bg-primary': link.active }"
                                                  preserve-scroll
                                                  v-html="link.label" />
                                            <span v-else 
                                                  class="page-link bg-dark text-white border-secondary"
                                                  v-html="link.label" />
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Users Section -->
                    <div v-if="currentSection === 'users'">
                        <h2 class="h4 mb-4">Users ({{ users.total }})</h2>
                        
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Last Activity</th>
                                                <th>Admin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="user in users.data" :key="user.id">
                                                <td>{{ user.username }}</td>
                                                <td>{{ formatDate(user.last_login_at) }}</td>
                                                <td>
                                                    <span :class="['badge', user.is_admin ? 'bg-danger' : 'bg-secondary']">
                                                        {{ user.is_admin ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr v-if="users.data.length === 0">
                                                <td colspan="3" class="text-center">No users found</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Users Pagination -->
                                <nav v-if="users.links.length > 3" class="mt-4">
                                    <ul class="pagination">
                                        <li v-for="(link, key) in users.links" 
                                            :key="key" 
                                            class="page-item"
                                            :class="{ 'active': link.active }">
                                            <Link v-if="link.url" 
                                                  :href="link.url" 
                                                  class="page-link bg-dark text-white border-secondary"
                                                  :class="{ 'bg-primary': link.active }"
                                                  preserve-scroll
                                                  v-html="link.label" />
                                            <span v-else 
                                                  class="page-link bg-dark text-white border-secondary"
                                                  v-html="link.label" />
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

// Define props from the controller
const props = defineProps({
    movies: {
        type: Object,
        required: true
    },
    users: {
        type: Object,
        required: true
    }
});

const currentSection = ref('movies');
const form = useForm({});

function scanMovies() {
    form.post(route('admin.movies.scan'), {
        preserveScroll: true
    });
}

function moveMovies() {
    form.post(route('admin.movies.move'), {
        preserveScroll: true
    });
}

function formatDate(date) {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<style scoped>
.dashboard-container {
    min-height: calc(100vh - 120px);
    position: relative;
}

.sidebar-toggle {
    position: fixed;
    top: 80px;
    left: 10px;
    z-index: 1030;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.offcanvas-md {
    width: 250px;
}

.offcanvas-body {
    padding: 0;
}

.nav-link {
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.2s ease;
    padding: 0.75rem 1rem;
    border-radius: 0;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
    color: #fff;
    background-color: #0d6efd;
}

.main-content {
    transition: margin-left 0.3s ease;
    padding: 1rem;
    width: 100%;
}

/* Desktop styles */
@media (min-width: 768px) {
    .sidebar-toggle {
        display: none;
    }

    .offcanvas-md {
        position: static;
        top: auto;
        left: auto;
        height: auto;
        width: 250px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
    }

    .main-content {
        margin-left: 250px;
    }

    .offcanvas-md:not(.show) + .main-content {
        margin-left: 0;
    }
}

/* Mobile styles */
@media (max-width: 767.98px) {
    .main-content {
        margin-left: 0;
        padding-top: 3rem;
    }

    .offcanvas-md.show + .main-content {
        margin-left: 0;
    }
}
</style>