<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    offers: {
        type: Object,
        required: true,
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
    <Head title="Admin · Angebote" />

    <AdminLayout title="Angebotsverwaltung">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Angebote</h1>
                <p class="mt-2 text-sm text-gray-500">Überblick über alle generierten Angebote.</p>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
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
                        <tr v-for="offer in offers.data" :key="offer.id" class="hover:bg-gray-50">
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
                                    class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-indigo-700"
                                >
                                    Öffnen
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!offers.data.length">
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                Keine Angebote gefunden.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <nav class="flex items-center justify-between border-t border-gray-200 px-6 py-4" v-if="offers.links?.length">
                    <div class="text-sm text-gray-500">
                        Seite {{ offers.current_page }} von {{ offers.last_page }} · {{ offers.total }} Einträge gesamt
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="link in offers.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="inline-flex min-w-[36px] items-center justify-center rounded-full px-3 py-1 text-sm font-medium transition"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                        />
                    </div>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
