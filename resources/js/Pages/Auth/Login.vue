<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

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
    <Head title="Sign In - Tsotsiflix" />
    <div class="login-container">
        <div class="overlay"></div>
        <div class="container">
            <div class="row vh-100 align-items-center justify-content-center">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                    <div class="login-card">
                        <div class="card-body p-4 p-sm-5">
                            <h1 class="card-title text-center mb-4">Sign In</h1>
                            
                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <input
                                        type="text"
                                        class="form-control netflix-input"
                                        id="username"
                                        placeholder="username"
                                        v-model="form.username"
                                        required
                                        autofocus
                                    >
                                    <div v-if="form.errors.username" class="text-danger mt-1">
                                        {{ form.errors.username }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <input
                                        type="password"
                                        class="form-control netflix-input"
                                        id="password"
                                        placeholder="Password"
                                        v-model="form.password"
                                        required
                                    >
                                    <div v-if="form.errors.password" class="text-danger mt-1">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="remember"
                                        v-model="form.remember"
                                    >
                                    <label class="form-check-label text-muted" for="remember">
                                        Remember me
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button 
                                        type="submit" 
                                        class="btn btn-danger btn-lg netflix-button"
                                        :disabled="form.processing"
                                    >
                                        Sign In
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-container {
    min-height: 100vh;
    background: url('https://assets.nflxext.com/ffe/siteui/vlv3/ab4b0b22-2ddf-4d48-ae88-c201ae0267e2/0efe6360-4f6d-4b10-beb6-81e0762cfe81/ZA-en-20231030-popsignuptwoweeks-perspective_alpha_website_large.jpg') center/cover no-repeat fixed;
    position: relative;
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.8));
    pointer-events: none;
}

.login-card {
    background: rgba(0, 0, 0, 0.75);
    border-radius: 4px;
    color: white;
    backdrop-filter: blur(10px);
}

.card-title {
    font-size: 2rem;
    font-weight: 500;
}

.netflix-input {
    background: #333;
    border: none;
    border-radius: 4px;
    color: white;
    padding: 1rem 1.25rem;
    height: auto;
    font-size: 1rem;
}

.netflix-input:focus {
    background: #454545;
    color: white;
    box-shadow: none;
    border: none;
}

.netflix-input::placeholder {
    color: #8c8c8c;
}

.form-check-input {
    background-color: #333;
    border-color: #737373;
}

.form-check-input:checked {
    background-color: #E50914;
    border-color: #E50914;
}

.netflix-button {
    font-weight: bold;
    font-size: 1.1rem;
    padding: 1rem;
    background-color: #E50914;
    border: none;
    transition: all 0.2s ease-in-out;
}

.netflix-button:hover:not(:disabled) {
    background-color: #f40612;
    transform: scale(1.02);
}

.netflix-button:disabled {
    background-color: #E50914;
    opacity: 0.7;
}

.text-danger {
    color: #E87C03 !important;
}
</style>