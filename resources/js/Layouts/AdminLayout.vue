<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        default: 'Admin',
    },
});

const navItems = [
    {
        key: 'dashboard',
        label: 'Übersicht',
        route: 'admin.dashboard',
        match: ['admin.dashboard'],
    },
    {
        key: 'offers',
        label: 'Angebote',
        route: 'admin.offers.index',
        match: ['admin.offers.*'],
    },
    {
        key: 'values',
        label: 'Werte',
        route: 'admin.values.index',
        match: ['admin.values.index', 'admin.values.*'],
    },
    {
        key: 'settings',
        label: 'Settings',
        route: 'admin.settings.index',
        match: ['admin.settings.index', 'admin.settings.*'],
    },
];

const isActive = (match) => {
    if (Array.isArray(match)) {
        return match.some((pattern) => route().current(pattern));
    }

    return route().current(match);
};
</script>

<template>
    <MainLayout :title="title" :show-footer="false">
        <div class="bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b border-slate-200">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6 lg:px-8">
                <h2 class="mr-auto text-sm font-semibold uppercase tracking-widest text-slate-500">
                    Adminbereich
                </h2>
                <nav class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="route(item.route)"
                        class="inline-flex items-center rounded-full px-4 py-2 transition"
                        :class="isActive(item.match)
                            ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10'
                            : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </div>

        <slot />
    </MainLayout>
</template>
