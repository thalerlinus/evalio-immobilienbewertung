<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    recentOffers: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDateTime = (value) => {
    if (! value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const statusLabel = (status, acceptedAt) => {
    if (acceptedAt) {
        return 'bestätigt';
    }

    switch (status) {
        case 'accepted':
        case 'confirmed':
            return 'bestätigt';
        case 'sent':
            return 'versendet';
        case 'pending':
            return 'offen';
        default:
            return status ?? 'offen';
    }
};
</script>

<template>
    <Head title="Admin" />

    <AdminLayout title="Admin-Dashboard">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Admin-Dashboard</h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Überblick über aktuelle Kennzahlen und Aktivitäten.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('admin.offers.index')"
                        class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Angebotsverwaltung öffnen
                    </Link>
                    <Link
                        :href="route('admin.values.index')"
                        class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100"
                    >
                        Werte bearbeiten
                    </Link>
                    <Link
                        :href="route('admin.settings.index')"
                        class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100"
                    >
                        Einstellungen anpassen
                    </Link>
                </div>
            </div>

            <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Angebote gesamt</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.total_offers ?? 0 }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Bestätigte Angebote</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.confirmed_offers ?? 0 }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Immobilienarten</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.property_types ?? 0 }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Kontakt-Einstellungen</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.contact_settings ?? 0 }}</p>
                </div>
            </section>

            <section class="mt-12 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Letzte Angebote</h2>
                        <p class="text-sm text-gray-500">Aktuelle Aktivitäten aus dem Angebotsprozess.</p>
                    </div>
                    <a
                        :href="route('admin.offers.index')"
                        class="inline-flex items-center rounded-full bg-primary/10 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-indigo-100"
                    >
                        Alle Angebote ansehen
                    </a>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200">
                    <!-- Desktop Tabelle -->
                    <table class="hidden md:table min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nummer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Kunde</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Immobilienart</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Erstellt</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Betrag</th>
                                <th class="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="offer in recentOffers" :key="offer.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ offer.number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="font-medium text-gray-900">{{ offer.customer?.name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ offer.customer?.email ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ offer.property_type ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold" :class="offer.accepted_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                        {{ statusLabel(offer.status, offer.accepted_at) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDateTime(offer.created_at) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ formatCurrency(offer.gross_total_eur) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a
                                        :href="offer.public_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white transition hover:bg-primary-dark"
                                    >
                                        Öffnen
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="!recentOffers.length">
                                <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                    Keine Angebote vorhanden.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Mobile Card Ansicht -->
                    <div class="md:hidden divide-y divide-gray-200">
                        <div v-for="offer in recentOffers" :key="offer.id" class="p-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="text-sm font-semibold text-gray-900">{{ offer.number }}</p>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" :class="offer.accepted_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                            {{ statusLabel(offer.status, offer.accepted_at) }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900">{{ offer.customer?.name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ offer.customer?.email ?? '—' }}</p>
                                    <div class="mt-2 flex flex-col gap-1 text-xs text-gray-600">
                                        <div><span class="font-medium">Immobilie:</span> {{ offer.property_type ?? '—' }}</div>
                                        <div><span class="font-medium">Erstellt:</span> {{ formatDateTime(offer.created_at) }}</div>
                                        <div><span class="font-medium">Betrag:</span> <span class="font-semibold text-gray-900">{{ formatCurrency(offer.gross_total_eur) }}</span></div>
                                    </div>
                                </div>
                                <a
                                    :href="offer.public_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex-shrink-0 inline-flex items-center rounded-full bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-dark"
                                >
                                    Öffnen
                                </a>
                            </div>
                        </div>
                        <div v-if="!recentOffers.length" class="px-4 py-6 text-center text-sm text-gray-500">
                            Keine Angebote vorhanden.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
