<script setup lang="ts">
import HomePage from './features/home/pages/HomePage.vue';
import MenPage from './features/men/pages/MenPage.vue';

const pathname = window.location.pathname.replace(/\/+$/, '') || '/';
const currentPage = pathname === '/men' ? MenPage : HomePage;
</script>

<template><component :is="currentPage" /></template>
