<script setup>
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-gray-950 text-white py-12" id="kontakt">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <!-- Unternehmensinfo -->
                <div>
                    <h4 class="text-xl font-bold mb-4 text-[#d9bf8c]">EVALIO</h4>
                    <p class="text-gray-300 mb-4">
                        Spezialisiertes Sachverständigenbüro für Immobilienbewertung. Rechtssichere Gutachten zur Restnutzungsdauer, zum Verkehrswert und zur Kaufpreisaufteilung.
                    </p>
                    <p class="text-sm text-gray-400 italic">
                        Zertifizierte Sachverständige nach DIN EN ISO/IEC 17024 (DAkkS)
                    </p>
                </div>
                
                <!-- Schnellzugriff -->
                <div>
                    <h4 class="text-xl font-bold mb-4">Schnellzugriff</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="#funktioniert" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>So funktioniert's</span>
                            </a>
                        </li>
                        <li>
                            <a href="#rechner" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>Online-Rechner</span>
                            </a>
                        </li>
                        <li>
                            <a href="#rechtsgrundlage" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>Rechtsgrundlage</span>
                            </a>
                        </li>
                        <li>
                            <a href="#ueber-uns" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>Über uns</span>
                            </a>
                        </li>
                        <li>
                            <a href="#faq" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>FAQ</span>
                            </a>
                        </li>
                        <li>
                            <a href="#kontakt" class="text-gray-300 hover:text-[#d9bf8c] transition flex items-center gap-2">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>Kontakt</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Kontaktinformationen -->
                <div>
                    <h4 class="text-xl font-bold mb-4">Kontaktinformationen</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-gray-300">
                            <svg class="h-5 w-5 text-[#d9bf8c] mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span>Bergiusstraße 13, 86199 Augsburg</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-[#d9bf8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:+4982165058020" class="text-gray-300 hover:text-[#d9bf8c] transition">
                                +49 (0)821 650 580 20
                            </a>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-[#d9bf8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:info@evalio-nutzungsdauer.de" class="text-gray-300 hover:text-[#d9bf8c] transition break-all">
                                info@evalio-nutzungsdauer.de
                            </a>
                        </li>
                        <li class="flex items-center gap-3 text-gray-300">
                            <svg class="h-5 w-5 text-[#d9bf8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <span>Mo–Fr 9:00–18:00 Uhr</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-4">
                    <!-- Rechtliche Links -->
                    <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                        <a href="#" class="text-gray-400 hover:text-[#d9bf8c] transition">
                            Datenschutz
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="#" class="text-gray-400 hover:text-[#d9bf8c] transition">
                            Impressum
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="#" class="text-gray-400 hover:text-[#d9bf8c] transition">
                            AGB
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="/login" class="text-gray-400 hover:text-[#d9bf8c] transition">
                            Admin Login
                        </a>
                    </div>
                </div>
                
                <!-- Copyright -->
                <div class="text-center">
                    <p class="text-sm text-gray-400">
                        &copy; {{ currentYear }} EVALIO Immobilienbewertung – Alle Rechte vorbehalten.
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Zertifizierte Sachverständige für Immobilienbewertung nach DIN EN ISO/IEC 17024 (DAkkS).
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
