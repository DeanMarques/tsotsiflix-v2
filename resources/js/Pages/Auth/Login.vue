<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import TsotsiflixLogo from '@/Components/TsotsiflixLogo.vue';

const form = useForm({
    username: '',
    password: '',
    remember: false
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-dark text-white">
        <!-- Logo -->
        <div class="text-center">
            <Link href="/">
                <TsotsiflixLogo TsotsiflixLogo className="text-decoration-none fs-1" /> 
            </Link>
        </div>

        <!-- Login Form -->
        <div class="w-100" style="max-width: 400px;">
            <form @submit.prevent="submit">
                <div class="mb-3">
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        class="form-control form-control-lg bg-dark text-white border-secondary"
                        :class="{ 'is-invalid': form.errors.username }"
                        placeholder="Username"
                        required
                        autofocus
                    />
                    <div class="invalid-feedback" v-if="form.errors.username">
                        {{ form.errors.username }}
                    </div>
                </div>

                <div class="mb-3">
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="form-control form-control-lg bg-dark text-white border-secondary"
                        :class="{ 'is-invalid': form.errors.password }"
                        placeholder="Password"
                        required
                    />
                    <div class="invalid-feedback" v-if="form.errors.password">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="form-check-input bg-dark border-secondary"
                            name="remember"
                        />
                        <label class="form-check-label text-secondary" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-danger btn-lg" :disabled="form.processing">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

.text-danger {
    color: #E50914 !important;
}



.text-white {
    color: #ffffff !important;
}

/* Placeholder styling */
.form-control::placeholder {
    color: #6c757d !important; /* Bootstrap's text-secondary color */
    opacity: 1;
}

.form-control:focus::placeholder {
    opacity: 0.75;
}

.display-4 {
    font-size: 2.5rem;
    font-weight: 500;
}

.fw-bold {
    font-weight: 700 !important;
}

.form-control {
    border: 1px solid #444;
    border-radius: 0.25rem;
}

.form-control:focus {
    border-color: #E50914;
    box-shadow: none;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
}
</style>