<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const isScrolled = ref(false);

// Handle scroll effect for navbar
const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <nav 
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 ease-in-out"
        :class="isScrolled 
            ? 'bg-white/95 backdrop-blur-lg shadow-lg border-b border-gray-200/50' 
            : 'bg-white/80 backdrop-blur-sm border-b border-gray-100/30'"
    >
        <!-- Primary Navigation Menu -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link href="/" class="flex items-center gap-3 group">
                        <img src="/images/logos/logo.png" alt="EVALIO Logo" class="h-16 md:h-20">
                        <span class="text-2xl md:text-3xl font-bold text-[#d9bf8c] transition-opacity duration-300 group-hover:opacity-80">EVALIO</span>
                    </Link>
                </div>

                <!-- Button zur Startseite -->
                <div>
                    <a
                        href="https://evalio-nutzungsdauer.de/"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-[#d9bf8c] rounded-lg transition-all duration-300 hover:bg-[#c9af7c] hover:shadow-lg hover:scale-105"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Zur Startseite</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</template>
