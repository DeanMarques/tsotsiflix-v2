<script setup>
const props = defineProps({
    className: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <span class="tsotsiflix-logo" :class="className">TSOTSIFLIX</span>
</template>

<style scoped>
.tsotsiflix-logo {
    color: #E50914;
    font-size: 1.8rem;
    font-weight: bold;
    font-family: 'Arial Black', 'Arial Bold', Gadget, sans-serif;
    text-transform: uppercase;
    letter-spacing: -1px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}
</style>
