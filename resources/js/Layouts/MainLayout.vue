<script setup>
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    title: {
        type: String,
        default: 'Willkommen',
    },
    user: {
        type: Object,
        default: null,
    },
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col overflow-x-hidden">
        <Head :title="title" />
        
        <!-- Navigation -->
        <Navbar 
            :user="user" 
            :can-login="canLogin" 
            :can-register="canRegister" 
        />

        <!-- Main Content with top padding for fixed navbar -->
        <main class="flex-grow pt-20 w-full overflow-x-hidden">
            <slot />
        </main>

        <!-- Footer -->
        <Footer v-if="showFooter" />
    </div>
</template>
