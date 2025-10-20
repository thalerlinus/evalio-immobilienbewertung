<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout title="Dashboard">
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h2 class="text-3xl font-bold leading-tight text-gray-800">
                        Dashboard
                    </h2>
                </div>
                
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-semibold mb-4">Welcome back!</h3>
                        <p>You're successfully logged in to your dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
