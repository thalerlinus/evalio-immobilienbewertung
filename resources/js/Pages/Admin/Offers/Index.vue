<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    offers: {
        type: Object,
        required: true,
    },
});

const showPriceDialog = ref(false);
const selectedOffer = ref(null);

const priceForm = useForm({
    price: '',
});

const priceDialogTitle = computed(() => {
    if (!selectedOffer.value) {
        return '';
    }

    return `Preis für ${selectedOffer.value.number} festlegen`;
});

const openPriceDialog = (offer) => {
    selectedOffer.value = offer;
    priceForm.price = offer.base_price_eur ?? offer.net_total_eur ?? '';
    showPriceDialog.value = true;
};

const closePriceDialog = () => {
    showPriceDialog.value = false;
    selectedOffer.value = null;
    priceForm.reset();
    priceForm.clearErrors();
};

const submitPrice = () => {
    if (!selectedOffer.value) {
        return;
    }

    priceForm.put(route('admin.offers.price.update', selectedOffer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePriceDialog();
        },
    });
};

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

const priceDisplay = (offer) => (offer.price_on_request ? 'Auf Anfrage' : formatCurrency(offer.gross_total_eur));
const netDisplay = (offer) => (offer.price_on_request ? '—' : formatCurrency(offer.net_total_eur));
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
                <!-- Desktop Tabelle -->
                <div class="hidden md:block">
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
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="flex flex-col">
                                        <span class="font-semibold">
                                            {{ priceDisplay(offer) }}
                                        </span>
                                        <span v-if="!offer.price_on_request && offer.net_total_eur" class="text-xs text-gray-500">
                                            Netto: {{ netDisplay(offer) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openPriceDialog(offer)"
                                            class="inline-flex items-center rounded-full border border-primary px-3 py-1 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white"
                                        >
                                            Preis setzen
                                        </button>
                                        <a
                                            :href="offer.public_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white transition hover:bg-primary-dark"
                                        >
                                            Öffnen
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!offers.data.length">
                                <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                    Keine Angebote gefunden.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Ansicht -->
                <div class="md:hidden divide-y divide-gray-200">
                    <div v-for="offer in offers.data" :key="offer.id" class="p-4 hover:bg-gray-50">
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
                                    <div>
                                        <span class="font-medium">Betrag:</span>
                                        <span class="font-semibold text-gray-900">{{ priceDisplay(offer) }}</span>
                                    </div>
                                    <div v-if="!offer.price_on_request && offer.net_total_eur">
                                        <span class="font-medium">Netto:</span>
                                        <span class="font-semibold text-gray-900">{{ netDisplay(offer) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <button
                                    type="button"
                                    @click="openPriceDialog(offer)"
                                    class="inline-flex items-center rounded-full border border-primary px-3 py-1 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white"
                                >
                                    Preis setzen
                                </button>
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
                    </div>
                    <div v-if="!offers.data.length" class="px-4 py-6 text-center text-sm text-gray-500">
                        Keine Angebote gefunden.
                    </div>
                </div>

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
                                link.active ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                        />
                    </div>
                </nav>
            </div>
        </div>
    </AdminLayout>

    <transition name="fade">
        <div
            v-if="showPriceDialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">{{ priceDialogTitle }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Bitte geben Sie den Netto-Betrag (ohne MwSt.) für dieses Angebot ein. Lassen Sie das Feld leer, um den Preis wieder auf „Auf Anfrage“ zu setzen.
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submitPrice">
                    <div>
                        <label for="manual-price" class="block text-sm font-medium text-gray-700">Netto-Preis in EUR</label>
                        <input
                            id="manual-price"
                            v-model.number="priceForm.price"
                            type="number"
                            min="0"
                            step="1"
                            placeholder="z. B. 1499"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40"
                        />
                        <p v-if="priceForm.errors.price" class="mt-2 text-sm text-red-600">
                            {{ priceForm.errors.price }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="closePriceDialog"
                            :disabled="priceForm.processing"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="priceForm.processing"
                        >
                            <svg
                                v-if="priceForm.processing"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </transition>
</template>
