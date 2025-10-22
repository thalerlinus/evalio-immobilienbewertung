<script setup>
import { computed, reactive, ref, watch } from 'vue';

import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    offer: {
        type: Object,
        required: true,
    },
    contactSettings: {
        type: Object,
        default: () => ({}),
    },
    gaPackages: {
        type: Array,
        default: () => [],
    },
});

const offer = ref(JSON.parse(JSON.stringify(props.offer)));
const availablePackages = ref([...props.gaPackages]);

watch(
    () => props.offer,
    (value) => {
        offer.value = JSON.parse(JSON.stringify(value ?? {}));
    },
    { deep: true },
);

const mergeObjects = (target, source) => ({
    ...(target ?? {}),
    ...(source ?? {}),
});

const updateOffer = (payload) => {
    const mergedFormInputs = mergeObjects(offer.value.form_inputs, payload?.form_inputs);

    if (payload?.form_inputs?.contact) {
        mergedFormInputs.contact = mergeObjects(offer.value.form_inputs?.contact, payload.form_inputs.contact);
    }

    if (payload?.form_inputs?.property) {
        mergedFormInputs.property = mergeObjects(offer.value.form_inputs?.property, payload.form_inputs.property);
    }

    if (payload?.form_inputs?.address) {
        mergedFormInputs.address = mergeObjects(offer.value.form_inputs?.address, payload.form_inputs.address);
    }

    if (payload?.form_inputs?.renovations) {
        mergedFormInputs.renovations = payload.form_inputs.renovations;
    }

    if (payload?.form_inputs?.notes !== undefined) {
        mergedFormInputs.notes = payload.form_inputs.notes;
    }

    offer.value = {
        ...offer.value,
        ...payload,
        calculation: mergeObjects(offer.value.calculation, payload?.calculation),
        pricing: mergeObjects(offer.value.pricing, payload?.pricing),
        form_inputs: mergedFormInputs,
    };
};

const selectedPackageKey = computed({
    get: () => offer.value.selected_package_key ?? offer.value.pricing?.ga_package?.key ?? null,
    set: (value) => {
        offer.value.selected_package_key = value;
    },
});

const isUpdatingPackage = ref(false);
const packageUpdateMessage = reactive({
    status: 'idle',
    message: '',
});

watch(
    () => props.gaPackages,
    (value) => {
        availablePackages.value = [...(value ?? [])];
    },
    { deep: true },
);

const updatePackage = async (packageKey) => {
    if (! offer.value?.token || offer.value.is_confirmed || isUpdatingPackage.value) {
        return;
    }

    isUpdatingPackage.value = true;
    packageUpdateMessage.status = 'pending';
    packageUpdateMessage.message = '';

    try {
        const response = await fetch(`/angebote/${offer.value.token}/package`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ ga_package_key: packageKey }),
        });

        const responseBody = await response.json().catch(() => ({}));

        if (! response.ok) {
            const errorMessage = responseBody?.message
                ?? 'Die Auswahl konnte nicht aktualisiert werden.';
            throw new Error(errorMessage);
        }

        if (responseBody?.data) {
            updateOffer(responseBody.data);
        }

        packageUpdateMessage.status = 'success';
        packageUpdateMessage.message = responseBody?.message
            ?? 'Ihre Auswahl wurde aktualisiert.';
    } catch (error) {
        packageUpdateMessage.status = 'error';
        packageUpdateMessage.message = error?.message
            ?? 'Die Auswahl konnte nicht aktualisiert werden.';
    } finally {
        isUpdatingPackage.value = false;
    }
};

const formatPackagePrice = (value) => {
    if (value === null || value === undefined) {
        return 'Preis auf Anfrage';
    }

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const propertyInputs = computed(() => offer.value.form_inputs?.property ?? {});
const addressInputs = computed(() => offer.value.form_inputs?.address ?? {});
const contactInputs = computed(() => offer.value.form_inputs?.contact ?? {});
const renovationInputs = computed(() => offer.value.form_inputs?.renovations ?? []);
const notes = computed(() => offer.value.form_inputs?.notes ?? null);
const customerData = computed(() => offer.value.customer ?? {});

const hasAddress = computed(() =>
    Object.values(addressInputs.value ?? {}).some((value) => value && String(value).trim() !== '')
);

const hasBillingAddress = computed(() => 
    customerData.value?.billing_street || customerData.value?.billing_zip || customerData.value?.billing_city
);

const hasRenovations = computed(() => (renovationInputs.value ?? []).length > 0);
const hasNotes = computed(() => {
    const value = notes.value;
    return typeof value === 'string' ? value.trim().length > 0 : false;
});

const timeWindowLabels = {
    nicht: 'Keine Sanierung',
    bis_5: 'In den letzten 5 Jahren',
    bis_10: 'In den letzten 5–10 Jahren',
    bis_15: 'In den letzten 10–15 Jahren',
    bis_20: 'In den letzten 15–20 Jahren',
    ueber_20: 'Vor mehr als 20 Jahren',
    weiss_nicht: 'Weiß nicht',
};

const constructionTypeLabels = {
    massiv: 'Massivbauweise',
    holz: 'Holzbauweise',
    unbekannt: 'Unbekannt',
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const priceOnRequest = computed(() => Boolean(offer.value?.pricing?.price_on_request));

const displayLineItemAmount = (value) => (priceOnRequest.value ? '—' : formatCurrency(value));

const netTotalDisplay = computed(() =>
    priceOnRequest.value
        ? 'Auf Anfrage'
        : formatCurrency(offer.value?.pricing?.net_total_eur)
);

const vatLabel = computed(() => {
    if (priceOnRequest.value) {
        return 'Mehrwertsteuer';
    }

    const percent = offer.value?.pricing?.vat_percent ?? 19;
    return `Mehrwertsteuer ${percent} %`;
});

const vatAmountDisplay = computed(() =>
    priceOnRequest.value
        ? '—'
        : formatCurrency(offer.value?.pricing?.vat_amount_eur)
);

const grossTotalDisplay = computed(() =>
    priceOnRequest.value
        ? 'Auf Anfrage'
        : formatCurrency(offer.value?.pricing?.gross_total_eur)
);

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

const formatOptional = (value, suffix = '') => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return suffix ? `${value} ${suffix}`.trim() : value;
};

const formatBoolean = (value) => {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? 'Ja' : 'Nein';
};

const formatConstructionType = (value) => {
    if (! value) {
        return '—';
    }

    return constructionTypeLabels[value] ?? value;
};

const formatTimeWindow = (key) => {
    if (! key) {
        return '—';
    }

    return timeWindowLabels[key] ?? key;
};

const extentLabels = {
    0: 'Nicht durchgeführt',
    20: 'Nur Ausbesserungsarbeiten',
    40: 'Vereinzelte Maßnahmen',
    60: 'Teilweise erneuert',
    80: 'Überwiegend erneuert',
    100: 'Vollständig saniert'
};

const formatExtentPercent = (value) => {
    if (value === null || value === undefined) {
        return '—';
    }

    const numValue = Number(value);
    
    if (numValue === 0) {
        return 'Keine Sanierung';
    }

    return extentLabels[numValue] || `${value} %`;
};

const phoneHref = (value) => {
    if (! value) {
        return null;
    }

    return `tel:${String(value).replace(/\s+/g, '')}`;
};

const statusLabel = computed(() => {
    if (offer.value.is_confirmed) {
        return 'bestätigt';
    }

    const status = offer.value.status;

    if (! status) {
        return 'offen';
    }

    const map = {
        accepted: 'bestätigt',
        confirmed: 'bestätigt',
        sent: 'versendet',
        pending: 'offen',
        draft: 'Entwurf',
    };

    return map[status] ?? status;
});

const rndIntervalLabel = computed(() => {
    const calculation = offer.value.calculation ?? {};

    if (calculation.rnd_interval_label) {
        return calculation.rnd_interval_label;
    }

    const min = calculation.rnd_min ?? null;
    const max = calculation.rnd_max ?? null;

    if (min == null && max == null) {
        return null;
    }

    if (min != null && max != null) {
        if (min === max) {
            return `rd. ${min} Jahre`;
        }

        return `rd. ${min} – ${max} Jahre`;
    }

    const value = min != null ? min : max;
    return value != null ? `rd. ${value} Jahre` : null;
});

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const supportEmail = computed(() => props.contactSettings?.support_email ?? 'kontakt@evalio.de');
const supportPhoneDisplay = computed(
    () => props.contactSettings?.support_phone_display ?? '+49 9999 99999'
);
const supportPhoneHref = computed(() => {
    const raw = props.contactSettings?.support_phone ?? supportPhoneDisplay.value;
    return raw ? String(raw).replace(/\s+/g, '') : '';
});
const supportName = computed(() => props.contactSettings?.support_name ?? 'Ihr Evalio-Team');

const confirmationState = reactive({
    status: 'idle',
    message: '',
    error: '',
});

const confirming = ref(false);

const CONFIRMATION_ERROR_MESSAGE = 'Die Bestätigung konnte nicht verarbeitet werden.';
const CONFIRMATION_SUCCESS_MESSAGE = 'Vielen Dank! Ihr Angebot wurde bestätigt.';
const CONFIRMATION_ALREADY_DONE = 'Dieses Angebot wurde bereits bestätigt.';
const CONSENT_REQUIRED_MESSAGE = 'Bitte bestätigen Sie den Hinweis zum Widerruf sowie die AGB und Widerrufsbelehrung.';

const confirmationConsent = ref(false);

if (offer.value.is_confirmed) {
    confirmationState.status = 'success';
    confirmationState.message = CONFIRMATION_ALREADY_DONE;
}

watch(
    () => offer.value.is_confirmed,
    (value) => {
        if (value) {
            confirmationState.status = 'success';
            confirmationState.message = confirmationState.message || CONFIRMATION_ALREADY_DONE;
        }
    },
);

watch(confirmationConsent, (value) => {
    if (value && confirmationState.status === 'error' && confirmationState.error === CONSENT_REQUIRED_MESSAGE) {
        confirmationState.status = 'idle';
        confirmationState.error = '';
    }
});

const confirmOffer = async () => {
    if (! offer.value?.token || confirming.value) {
        return;
    }

    if (! confirmationConsent.value) {
        confirmationState.status = 'error';
        confirmationState.message = '';
        confirmationState.error = CONSENT_REQUIRED_MESSAGE;
        return;
    }

    confirming.value = true;
    confirmationState.status = 'pending';
    confirmationState.message = '';
    confirmationState.error = '';

    try {
        const response = await fetch(`/angebote/${offer.value.token}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({}),
        });

        const responseBody = await response.json().catch(() => ({}));

        if (! response.ok) {
            const errorMessage = responseBody?.message
                ?? CONFIRMATION_ERROR_MESSAGE;
            throw new Error(errorMessage);
        }

        if (responseBody?.data) {
            updateOffer(responseBody.data);
        }

        confirmationState.status = 'success';
        confirmationState.message = responseBody?.message
            ?? CONFIRMATION_SUCCESS_MESSAGE;
    } catch (error) {
        confirmationState.status = 'error';
        confirmationState.error = error?.message
            ?? CONFIRMATION_ERROR_MESSAGE;
    } finally {
        confirming.value = false;
    }
};
</script>

<template>
    <MainLayout title="Ihr individuelles Angebot">
        <section class="bg-[#d9bf8c] py-16 pb-24 text-slate-900">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-slate-700">
                    Angebot {{ offer.number }}
                </p>
                <h1 class="text-3xl font-bold sm:text-4xl">Vielen Dank für Ihr Vertrauen!</h1>
                <p class="mt-4 max-w-2xl text-slate-800">
                    Hier finden Sie alle Details zu Ihrem individuell kalkulierten Gutachten-Angebot.
                    Bei Fragen stehen wir Ihnen jederzeit gerne zur Verfügung.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-6 text-sm text-slate-800">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/60 px-4 py-1.5 font-medium">
                        Status: <span class="font-semibold capitalize">{{ statusLabel }}</span>
                    </span>
                    <span>Erstellt am {{ formatDateTime(offer.created_at) }}</span>
                    <span v-if="offer.accepted_at" class="flex items-center gap-1 text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l3-3z" clip-rule="evenodd" />
                        </svg>
                        Bestätigt am {{ formatDateTime(offer.accepted_at) }}
                    </span>
                </div>
            </div>
        </section>

        <section class="-mt-12 pb-16 sm:-mt-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="rounded-3xl bg-white p-8 shadow-xl ring-1 ring-black/5">
                            <h2 class="text-xl font-semibold text-gray-900">Ihre Angebotsdetails</h2>
                            <p class="mt-2 text-sm text-gray-500">
                                Basierend auf Ihrer Berechnung mit unserem RND-Kalkulator.
                            </p>

                            <div
                                v-if="priceOnRequest"
                                class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"
                            >
                                Wir prüfen dieses Objekt individuell und melden uns mit dem konkreten Angebotspreis, sobald er vorliegt.
                            </div>

                            <!-- Immobilien-Adresse -->
                            <div v-if="hasAddress" class="mt-6 rounded-2xl bg-gradient-to-br from-[#d9bf8c]/10 to-[#d9bf8c]/5 p-6 border border-[#d9bf8c]/20">
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-[#d9bf8c]">
                                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                                    </svg>
                                    Objektadresse
                                </h3>
                                <p class="mt-2 text-base font-medium text-gray-900">
                                    {{ addressInputs.street || '—' }}<br>
                                    {{ addressInputs.zip || '—' }} {{ addressInputs.city || '—' }}
                                </p>
                            </div>

                            <dl class="mt-8 grid gap-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Immobilienart</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ offer.calculation?.property_type ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Empfehlung</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ offer.calculation?.recommendation ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">RND Dauer</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ rndIntervalLabel ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Abschreibung (AfA)</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ offer.calculation?.afa_percent_label ?? '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-8 rounded-3xl bg-white p-8 shadow-xl ring-1 ring-black/5">
                            <h2 class="text-xl font-semibold text-gray-900">Preistransparenz</h2>
                            <p
                                v-if="priceOnRequest"
                                class="mt-2 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"
                            >
                                Der Preis wird aktuell individuell kalkuliert. Sie erhalten die genaue Angebotsumme nach unserer manuellen Prüfung per E-Mail.
                            </p>
                            <p v-else class="mt-2 text-sm text-gray-500">
                                Alle Beträge verstehen sich netto zuzüglich gesetzlicher Mehrwertsteuer.
                            </p>

                            <div
                                v-if="availablePackages.length"
                                class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-6"
                            >
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Zusatzoption wählen
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Entscheiden Sie sich für genau eine Zusatzleistung. Sie können Ihre Auswahl bis zur Bestätigung anpassen.
                                </p>

                                <div class="mt-4 grid gap-3">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left text-sm transition hover:border-[#d9bf8c] hover:bg-white"
                                        :class="{
                                            'border-[#d9bf8c] bg-white shadow-sm ring-1 ring-[#d9bf8c]/20': !selectedPackageKey,
                                            'border-slate-200 bg-slate-100': Boolean(selectedPackageKey),
                                        }"
                                        :disabled="isUpdatingPackage || offer.is_confirmed"
                                        @click="selectedPackageKey = null; updatePackage(null)"
                                    >
                                        <span class="font-medium text-slate-900">Kein Zusatzpaket</span>
                                        <span class="text-sm text-slate-700">inklusive</span>
                                    </button>

                                    <button
                                        v-for="pkg in availablePackages"
                                        :key="pkg.key"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left text-sm transition hover:border-[#d9bf8c] hover:bg-white"
                                        :class="{
                                            'border-[#d9bf8c] bg-white shadow-sm ring-1 ring-[#d9bf8c]/20': selectedPackageKey === pkg.key,
                                            'border-slate-200 bg-white': selectedPackageKey !== pkg.key,
                                        }"
                                        :disabled="isUpdatingPackage || offer.is_confirmed"
                                        @click="selectedPackageKey = pkg.key; updatePackage(pkg.key)"
                                    >
                                        <span class="flex flex-col">
                                            <span class="font-medium text-slate-900">{{ pkg.label }}</span>
                                            <span class="text-xs text-slate-500">Einmalige Zusatzleistung</span>
                                        </span>
                                        <span class="text-sm font-semibold text-slate-900">{{ formatPackagePrice(pkg.price_eur) }}</span>
                                    </button>
                                </div>

                                <div v-if="packageUpdateMessage.status !== 'idle'" class="mt-3">
                                    <p
                                        v-if="packageUpdateMessage.status === 'success'"
                                        class="text-sm text-emerald-600"
                                    >
                                        {{ packageUpdateMessage.message }}
                                    </p>
                                    <p
                                        v-else-if="packageUpdateMessage.status === 'error'"
                                        class="text-sm text-red-600"
                                    >
                                        {{ packageUpdateMessage.message }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-slate-500"
                                    >
                                        Aktualisiere Auswahl …
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-slate-500">
                                                Leistung
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-sm font-semibold text-slate-500">
                                                Preis (netto)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr
                                            v-for="item in offer.pricing?.line_items || []"
                                            :key="item.key"
                                        >
                                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                                {{ item.label ?? 'Position' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-slate-700">
                                                {{ displayLineItemAmount(item.amount_eur) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <dl class="mt-6 space-y-3 text-sm text-slate-600">
                                <div class="flex justify-between">
                                    <dt>Zwischensumme (netto)</dt>
                                    <dd class="font-semibold text-slate-900">
                                        {{ netTotalDisplay }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>{{ vatLabel }}</dt>
                                    <dd class="font-semibold text-slate-900">
                                        {{ vatAmountDisplay }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 px-4 py-3 text-base font-semibold text-white shadow-lg">
                                    <dt>Gesamtbetrag (brutto)</dt>
                                    <dd>{{ grossTotalDisplay }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Angebot bestätigen Block -->
                        <div class="mt-8 rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Angebot bestätigen</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Mit einem Klick bestätigen Sie das Angebot und erhalten Ihre Bestätigung per E-Mail.
                            </p>

                            <div v-if="offer.is_confirmed || confirmationState.status === 'success'" class="mt-4 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
                                <div class="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-5 w-5">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l3-3z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Vielen Dank! Das Angebot ist bestätigt.</p>
                                        <p class="mt-1" v-if="confirmationState.message">
                                            {{ confirmationState.message }}
                                        </p>
                                        <p class="mt-1" v-else-if="offer.accepted_at">
                                            Bestätigt am {{ formatDateTime(offer.accepted_at) }}.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="mt-4 space-y-4">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                                    <label class="flex items-start gap-3" for="confirmation-consent">
                                        <input
                                            id="confirmation-consent"
                                            v-model="confirmationConsent"
                                            type="checkbox"
                                            required
                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-[#d9bf8c] focus:ring-[#d9bf8c]"
                                        />
                                        <span class="space-y-2 leading-snug">
                                            <span class="block text-sm font-semibold text-slate-900">
                                                Ich bin einverstanden und verlange ausdrücklich, dass Sie vor Ablauf der Widerrufsfrist mit der Ausführung der beauftragten Dienstleistung beginnen.<span class="text-red-500">*</span>
                                            </span>
                                            <span class="block text-sm text-slate-700">
                                                Mir ist bekannt, dass ich bei vollständiger Vertragserfüllung durch Sie mein Widerrufsrecht verliere.
                                            </span>
                                            <span class="block text-sm text-slate-700">
                                                Die
                                                <a href="/agb" target="_blank" rel="noreferrer" class="text-[#c4a875] hover:underline hover:text-[#d9bf8c]">AGB</a>
                                                und
                                                <a href="/widerrufsbelehrung" target="_blank" rel="noreferrer" class="text-[#c4a875] hover:underline hover:text-[#d9bf8c]">Widerrufsbelehrung</a>
                                                habe ich zur Kenntnis genommen und akzeptiert.
                                            </span>
                                            <span class="block text-sm text-slate-500">
                                                Alle Preise verstehen sich inkl. MwSt.
                                            </span>
                                            <span class="block text-slate-500">
                                                <span class="font-semibold">*</span> Pflichtfeld
                                            </span>
                                        </span>
                                    </label>
                                    <p
                                        v-if="confirmationState.status === 'error' && confirmationState.error === CONSENT_REQUIRED_MESSAGE"
                                        class="mt-3 rounded-lg bg-red-100 px-3 py-2 text-red-700"
                                    >
                                        {{ CONSENT_REQUIRED_MESSAGE }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    :disabled="confirming || ! offer.can_confirm || ! confirmationConsent"
                                    @click="confirmOffer"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#d9bf8c] px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40"
                                >
                                    <svg
                                        v-if="confirming"
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    Angebot jetzt bestätigen
                                </button>
                                <p class="text-xs text-slate-500">
                                    Wir senden Ihnen sofort eine Bestätigungs-E-Mail. Bei einer positiven Empfehlung erhält auch das Evalio-Team eine Benachrichtigung.
                                </p>
                                <p
                                    v-if="confirmationState.status === 'error' && confirmationState.error && confirmationState.error !== CONSENT_REQUIRED_MESSAGE"
                                    class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
                                >
                                    {{ confirmationState.error }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">Ihre Eingaben aus dem Rechner</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Zur Nachverfolgung speichern wir alle Formularangaben, die Sie für die Berechnung bereitgestellt haben.
                            </p>

                            <div class="mt-6 space-y-8">
                                <section>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Objektdaten
                                    </h3>
                                    <dl class="mt-3 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
                                        <div>
                                            <dt class="font-medium text-slate-500">Immobilienart</dt>
                                            <dd class="mt-1 font-semibold text-slate-900">
                                                {{ offer.calculation?.property_type ?? formatOptional(propertyInputs.property_type_key) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-500">Baujahr</dt>
                                            <dd class="mt-1 font-semibold text-slate-900">
                                                {{ formatOptional(propertyInputs.baujahr) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-500">Anschaffungsjahr</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(propertyInputs.anschaffungsjahr) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Steuerjahr</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(propertyInputs.steuerjahr) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Ermittlungsjahr</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(propertyInputs.ermittlungsjahr) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Bauweise</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatConstructionType(propertyInputs.bauweise) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Eigennutzung</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatBoolean(propertyInputs.eigennutzung) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <section>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Kontaktdaten
                                    </h3>
                                    <dl class="mt-3 grid gap-4 text-sm text-gray-600">
                                        <div>
                                            <dt class="font-medium text-gray-500">E-Mail</dt>
                                            <dd class="mt-1 font-semibold text-gray-900 break-words">
                                                <a v-if="contactInputs.email" :href="`mailto:${contactInputs.email}`" class="text-[#d9bf8c] hover:underline hover:text-[#c4a875]">
                                                    {{ contactInputs.email }}
                                                </a>
                                                <span v-else>—</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Telefon</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                <a
                                                    v-if="contactInputs.phone"
                                                    :href="phoneHref(contactInputs.phone)"
                                                    class="text-[#d9bf8c] hover:underline hover:text-[#c4a875]"
                                                >
                                                    {{ contactInputs.phone }}
                                                </a>
                                                <span v-else>—</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Name</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(contactInputs.name) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <section v-if="hasBillingAddress">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Rechnungsadresse
                                    </h3>
                                    <dl class="mt-3 grid gap-4 text-sm text-gray-600 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <dt class="font-medium text-gray-500">Straße</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(customerData.billing_street) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">PLZ</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(customerData.billing_zip) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">Ort</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(customerData.billing_city) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <section v-if="hasRenovations">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Sanierungsangaben
                                    </h3>
                                    <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50 text-gray-500">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold">Kategorie</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Umfang</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Zeitpunkt</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                <tr v-for="item in renovationInputs" :key="item.category_key">
                                                    <td class="px-4 py-3 font-medium text-gray-900">
                                                        {{ item.label ?? item.category_key ?? 'Kategorie' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        {{ formatExtentPercent(item.extent_percent) }}
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        {{ formatTimeWindow(item.time_window_key) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <section v-if="hasNotes">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Notizen
                                    </h3>
                                    <p class="mt-3 whitespace-pre-line rounded-2xl bg-gray-50 p-4 text-sm text-gray-700">
                                        {{ notes }}
                                    </p>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Nächste Schritte</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#d9bf8c]/20 text-xs font-semibold text-[#c4a875]">
                                        1
                                    </span>
                                    <span>
                                        Prüfen Sie die Angebotsdetails und die empfohlenen Leistungen.
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#d9bf8c]/20 text-xs font-semibold text-[#c4a875]">
                                        2
                                    </span>
                                    <span>
                                        Bei Rückfragen oder individuellen Wünschen kontaktieren Sie uns gerne.
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#d9bf8c]/20 text-xs font-semibold text-[#c4a875]">
                                        3
                                    </span>
                                    <span>
                                        Bestätigen Sie das Angebot, damit wir gemeinsam die nächsten Schritte starten können.
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-3xl bg-[#d9bf8c]/10 p-6 text-sm text-indigo-900">
                            <h3 class="text-lg font-semibold text-indigo-900">Sie haben Fragen?</h3>
                            <p class="mt-2">
                                Unser Expertenteam unterstützt Sie persönlich bei allen Anliegen rund um Ihr Gutachten.
                            </p>
                            <p class="mt-4 font-semibold">
                                Ansprechpartner: {{ supportName }}<br>
                                Telefon:
                                <a :href="`tel:${supportPhoneHref}`" class="text-[#d9bf8c]">
                                    {{ supportPhoneDisplay }}
                                </a><br>
                                E-Mail:
                                <a :href="`mailto:${supportEmail}`" class="text-[#d9bf8c]">
                                    {{ supportEmail }}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
