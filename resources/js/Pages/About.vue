<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <MainLayout title="Über uns">
        <div class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Über uns</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        Wer wir sind
                    </p>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                        Wir sind ein innovatives Team, das sich auf die Entwicklung moderner Webanwendungen spezialisiert hat.
                    </p>
                </div>

                <div class="mt-10">
                    <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                        <div class="relative">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Unsere Mission</h3>
                            <p class="mt-2 text-base text-gray-500">
                                Wir entwickeln innovative und benutzerfreundliche Webanwendungen, die echten Mehrwert für unsere Kunden schaffen.
                            </p>
                        </div>

                        <div class="relative">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Unsere Vision</h3>
                            <p class="mt-2 text-base text-gray-500">
                                Eine Welt, in der Technologie Menschen verbindet und das Leben einfacher macht.
                            </p>
                        </div>

                        <div class="relative">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Unsere Werte</h3>
                            <p class="mt-2 text-base text-gray-500">
                                Qualität, Innovation und Kundenzufriedenheit stehen im Mittelpunkt all unserer Aktivitäten.
                            </p>
                        </div>

                        <div class="relative">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Unser Team</h3>
                            <p class="mt-2 text-base text-gray-500">
                                Erfahrene Entwickler und Designer, die mit Leidenschaft großartige Software entwickeln.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
