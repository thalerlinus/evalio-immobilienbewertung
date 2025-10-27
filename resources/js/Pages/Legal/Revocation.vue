<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <MainLayout title="Widerrufsbelehrung">
        <div class="bg-white py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-bold text-slate-900 mb-4">Widerrufsbelehrung</h1>
                    <p class="text-lg text-slate-600">Informationen zum Widerrufsrecht</p>
                </div>

                <!-- Content -->
                <div class="prose prose-slate max-w-none">
                    <section class="mb-10">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">Widerrufsrecht</h2>
                        <p class="text-slate-700 mb-4">
                            Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen.
                        </p>
                        
                        <div class="bg-slate-50 rounded-lg p-6 mb-6">
                            <p class="text-slate-700 mb-4">
                                Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.
                            </p>
                            <p class="text-slate-700">
                                Um Ihr Widerrufsrecht auszuüben, müssen Sie uns mittels einer eindeutigen Erklärung (z.B. ein mit der Post versandter Brief oder E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren.
                            </p>
                        </div>

                        <div class="bg-slate-50 rounded-lg p-6">
                            <p class="font-semibold text-slate-900 mb-2">Kontakt für Widerruf:</p>
                            <p class="text-slate-700">Martin W. Halm M.Sc.</p>
                            <p class="text-slate-700">Pater-Roth-Straße 1</p>
                            <p class="text-slate-700">86157 Augsburg</p>
                            <p class="text-slate-700">
                                E-Mail: <a href="mailto:martin.halm@evalio-immo.de" class="text-[#d9bf8c] hover:text-[#c9af7c] transition">martin.halm@evalio-immo.de</a>
                            </p>
                            <p class="text-slate-700">Tel: +49 821 650 580 20</p>
                        </div>
                    </section>

                    <section class="mb-10">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">Folgen des Widerrufs</h2>
                        <p class="text-slate-700 mb-4">
                            Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist.
                        </p>
                        <p class="text-slate-700">
                            Haben Sie verlangt, dass die Dienstleistungen während der Widerrufsfrist beginnen sollen, so haben Sie uns einen angemessenen Betrag zu zahlen, der dem Anteil der bis zu dem Zeitpunkt, zu dem Sie uns von der Ausübung des Widerrufsrechts hinsichtlich dieses Vertrags unterrichten, bereits erbrachten Dienstleistungen im Vergleich zum Gesamtumfang der im Vertrag vorgesehenen Dienstleistungen entspricht.
                        </p>
                    </section>
                </div>

                <!-- Back Button -->
                <div class="mt-12 pt-8 border-t border-slate-200">
                    <a
                        href="/"
                        class="inline-flex items-center gap-2 text-[#d9bf8c] hover:text-[#c9af7c] font-semibold transition"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Zurück zur Startseite
                    </a>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
