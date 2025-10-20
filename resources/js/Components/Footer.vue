<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
            <div class="xl:grid xl:grid-cols-3 xl:gap-8">
                <!-- Company Info -->
                <div class="space-y-8 xl:col-span-1">
                    <div class="flex items-center">
                        <ApplicationLogo class="h-10 w-auto fill-current text-white" />
                        <span class="ml-3 text-xl font-bold">Ihr Unternehmen</span>
                    </div>
                    <p class="text-gray-300 text-base">
                        Wir bieten innovative Lösungen und erstklassigen Service für unsere Kunden.
                        Vertrauen Sie auf unsere Expertise und Erfahrung.
                    </p>
                    <div class="flex space-x-6">
                        <!-- Social Media Links -->
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">Facebook</span>
                            <FontAwesomeIcon icon="fab fa-facebook" class="h-6 w-6" />
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">Instagram</span>
                            <FontAwesomeIcon icon="fab fa-instagram" class="h-6 w-6" />
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">Twitter</span>
                            <FontAwesomeIcon icon="fab fa-x-twitter" class="h-6 w-6" />
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">LinkedIn</span>
                            <FontAwesomeIcon icon="fab fa-linkedin" class="h-6 w-6" />
                        </a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="mt-12 grid grid-cols-2 gap-8 xl:mt-0 xl:col-span-2">
                    <div class="md:grid md:grid-cols-2 md:gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Navigation</h3>
                            <ul class="mt-4 space-y-4">
                                <li>
                                    <Link href="/" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Startseite
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/about" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Über uns
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/services" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Services
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/contact" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Kontakt
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-12 md:mt-0">
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Services</h3>
                            <ul class="mt-4 space-y-4">
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Webentwicklung
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        App Entwicklung
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Beratung
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Support
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="md:grid md:grid-cols-2 md:gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Unternehmen</h3>
                            <ul class="mt-4 space-y-4">
                                <li>
                                    <Link href="/about" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Über uns
                                    </Link>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Team
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Karriere
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        News
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-12 md:mt-0">
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Rechtliches</h3>
                            <ul class="mt-4 space-y-4">
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Datenschutz
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Impressum
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        AGB
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-base text-gray-300 hover:text-white transition-colors">
                                        Cookie-Richtlinie
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="mt-12 border-t border-gray-700 pt-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Adresse</h4>
                        <p class="mt-2 text-base text-gray-300">
                            Musterstraße 123<br>
                            12345 Musterstadt<br>
                            Deutschland
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Kontakt</h4>
                        <p class="mt-2 text-base text-gray-300">
                            Tel: +49 (0) 123 456 789<br>
                            E-Mail: info@ihrunternehmen.de
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Öffnungszeiten</h4>
                        <p class="mt-2 text-base text-gray-300">
                            Mo - Fr: 9:00 - 18:00 Uhr<br>
                            Sa: 10:00 - 14:00 Uhr
                        </p>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="mt-12 border-t border-gray-700 pt-8">
                <p class="text-base text-gray-400 xl:text-center">
                    &copy; {{ currentYear }} Ihr Unternehmen. Alle Rechte vorbehalten.
                </p>
            </div>
        </div>
    </footer>
</template>
