<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const showingNavigationDropdown = ref(false);
const isScrolled = ref(false);

const scrollToSection = (sectionId) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
    // Close mobile menu after navigation
    showingNavigationDropdown.value = false;
};

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
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <!-- Logo -->
                    <div class="shrink-0 flex items-center">
                        <Link href="/" class="flex items-center space-x-3 group">
                            <div class="relative">
                                <ApplicationLogo
                                    class="block h-12 w-auto fill-current text-indigo-600 group-hover:text-indigo-700 transition-colors duration-300"
                                />
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-20 rounded-lg transition-opacity duration-300"></div>
                            </div>
                            <div class="hidden md:block">
                                <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    Ihr Unternehmen
                                </h1>
                                <p class="text-xs text-gray-500 -mt-1">Digital Solutions</p>
                            </div>
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-1 lg:ml-12 lg:flex">
                        <Link
                            href="/"
                            class="relative px-4 py-2 text-sm font-semibold text-gray-700 rounded-full transition-all duration-300 ease-in-out group hover:text-indigo-600"
                            :class="route().current('home') || route().current('/') 
                                ? 'text-indigo-600 bg-indigo-50' 
                                : 'hover:bg-gray-50'"
                        >
                            <span class="relative z-10">Startseite</span>
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        </Link>
                        
                        <button
                            @click="scrollToSection('about')"
                            class="relative px-4 py-2 text-sm font-semibold text-gray-700 rounded-full transition-all duration-300 ease-in-out group hover:text-indigo-600 hover:bg-gray-50"
                        >
                            <span class="relative z-10">Über uns</span>
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        </button>
                        
                        <button
                            @click="scrollToSection('services')"
                            class="relative px-4 py-2 text-sm font-semibold text-gray-700 rounded-full transition-all duration-300 ease-in-out group hover:text-indigo-600 hover:bg-gray-50"
                        >
                            <span class="relative z-10">Services</span>
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        </button>
                        
                        <button
                            @click="scrollToSection('contact')"
                            class="relative px-6 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full transition-all duration-300 ease-in-out hover:from-indigo-700 hover:to-purple-700 hover:shadow-lg hover:shadow-indigo-500/25 transform hover:scale-105"
                        >
                            Kontakt
                        </button>
                    </div>
                </div>

                <!-- Right side actions -->
                <div class="hidden lg:flex lg:items-center lg:space-x-4">
                    <!-- Theme Toggle (Optional) -->
                    <button class="p-2 text-gray-500 hover:text-indigo-600 transition-colors duration-300 rounded-full hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <Dropdown align="right" width="56">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex items-center space-x-3 px-4 py-2 text-sm font-medium text-gray-700 bg-white/80 border border-gray-200/50 rounded-full hover:bg-white hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-300 group"
                                >
                                    <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-semibold">
                                            {{ $page.props.auth.user ? $page.props.auth.user.name.charAt(0).toUpperCase() : 'G' }}
                                        </span>
                                    </div>
                                    <span class="hidden md:block">
                                        {{ $page.props.auth.user ? $page.props.auth.user.name : 'Gast' }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="py-2">
                                    <template v-if="$page.props.auth.user">
                                        <div class="px-4 py-2 border-b border-gray-100">
                                            <p class="text-sm font-medium text-gray-900">{{ $page.props.auth.user.name }}</p>
                                            <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                                        </div>
                                        <DropdownLink
                                            v-if="$page.props.auth.user.is_admin"
                                            :href="route('admin.dashboard')"
                                            class="flex items-center space-x-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                                            </svg>
                                            <span>Admin-Dashboard</span>
                                        </DropdownLink>
                                        <DropdownLink :href="route('profile.edit')" class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Profil</span>
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center space-x-2 text-red-600 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            <span>Abmelden</span>
                                        </DropdownLink>
                                    </template>
                                    <template v-else>
                                        <DropdownLink :href="route('login')" class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                            </svg>
                                            <span>Anmelden</span>
                                        </DropdownLink>
                                    </template>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="flex items-center lg:hidden">
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="inline-flex items-center justify-center p-2 rounded-full text-gray-400 hover:text-indigo-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-indigo-600 transition-all duration-300"
                    >
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                :class="{
                                    hidden: showingNavigationDropdown,
                                    'inline-flex': !showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{
                                    hidden: !showingNavigationDropdown,
                                    'inline-flex': showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div
            :class="{ 
                'translate-y-0 opacity-100': showingNavigationDropdown, 
                '-translate-y-4 opacity-0 pointer-events-none': !showingNavigationDropdown 
            }"
            class="lg:hidden absolute top-full left-0 right-0 bg-white/95 backdrop-blur-lg border-b border-gray-200/50 shadow-lg transition-all duration-300 ease-in-out"
        >
            <div class="px-4 py-6 space-y-2">
                <Link 
                    href="/" 
                    class="block px-4 py-3 text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-300"
                    :class="route().current('/') ? 'text-indigo-600 bg-indigo-50' : ''"
                >
                    Startseite
                </Link>
                
                <button
                    @click="scrollToSection('about')"
                    class="w-full text-left px-4 py-3 text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-300"
                >
                    Über uns
                </button>
                
                <button
                    @click="scrollToSection('services')"
                    class="w-full text-left px-4 py-3 text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-300"
                >
                    Services
                </button>
                
                <button
                    @click="scrollToSection('contact')"
                    class="w-full text-left px-4 py-3 text-base font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-300"
                >
                    Kontakt
                </button>
            </div>

            <!-- Mobile User Menu -->
            <div class="px-4 py-4 border-t border-gray-200/50">
                <template v-if="$page.props.auth.user">
                    <div class="flex items-center space-x-3 px-4 py-3 bg-gray-50 rounded-lg mb-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')" class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Profil</span>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user.is_admin"
                            :href="route('admin.dashboard')"
                            class="flex items-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                            </svg>
                            <span>Admin-Dashboard</span>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="flex items-center space-x-2 text-red-600 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Abmelden</span>
                        </ResponsiveNavLink>
                    </div>
                </template>
                <template v-else>
                    <div class="space-y-1">
                        <ResponsiveNavLink :href="route('login')" class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Anmelden</span>
                        </ResponsiveNavLink>
                    </div>
                </template>
            </div>
        </div>
    </nav>
</template>