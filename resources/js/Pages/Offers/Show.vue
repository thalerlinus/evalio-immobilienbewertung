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
const discountCodeInput = ref(offer.value.pricing?.discount_code ?? '');
const isApplyingDiscount = ref(false);
const discountMessage = reactive({
    status: 'idle',
    message: '',
});

watch(
    () => props.offer,
    (value) => {
        offer.value = JSON.parse(JSON.stringify(value ?? {}));
    },
    { deep: true },
);

watch(
    () => offer.value?.pricing?.discount_code,
    (value) => {
        discountCodeInput.value = value ?? '';
    },
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

const applyDiscountCode = async () => {
    if (! offer.value?.token || offer.value.is_confirmed || isApplyingDiscount.value) {
        return;
    }

    const rawCode = discountCodeInput.value ?? '';
    const normalizedCode = rawCode.trim() ? rawCode.trim().toUpperCase() : null;

    if (normalizedCode) {
        discountCodeInput.value = normalizedCode;
    }

    isApplyingDiscount.value = true;
    discountMessage.status = 'pending';
    discountMessage.message = '';

    try {
        const response = await fetch(`/angebote/${offer.value.token}/discount`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                code: normalizedCode,
            }),
        });

        const responseBody = await response.json().catch(() => ({}));

        if (! response.ok) {
            const errorMessage = responseBody?.message
                ?? 'Der Rabattcode konnte nicht angewendet werden.';
            throw new Error(errorMessage);
        }

        if (responseBody?.data) {
            updateOffer(responseBody.data);
        }

        discountMessage.status = 'success';
        discountMessage.message = responseBody?.message
            ?? 'Rabattcode wurde angewendet.';
    } catch (error) {
        discountMessage.status = 'error';
        discountMessage.message = error?.message
            ?? 'Der Rabattcode konnte nicht angewendet werden.';
    } finally {
        isApplyingDiscount.value = false;
    }
};

const removeDiscountCode = () => {
    if (isApplyingDiscount.value) {
        return;
    }

    discountCodeInput.value = '';
    applyDiscountCode();
};

const formatPackagePrice = (value) => {
    if (value === null || value === undefined) {
        return 'Preis auf Anfrage';
    }

    const grossValue = applyVatToValue(value);

    if (grossValue === null) {
        return 'Preis auf Anfrage';
    }

    return formatCurrency(grossValue);
};

const propertyInputs = computed(() => offer.value.form_inputs?.property ?? {});
const addressInputs = computed(() => offer.value.form_inputs?.address ?? {});
const contactInputs = computed(() => offer.value.form_inputs?.contact ?? {});
const renovationInputs = computed(() => offer.value.form_inputs?.renovations ?? []);
const notes = computed(() => offer.value.form_inputs?.notes ?? null);
const customerData = computed(() => offer.value.customer ?? {});

const POSITIVE_RECOMMENDATION = 'Gutachten ist sinnvoll, eine Beauftragung wird empfohlen';

const recommendationText = computed(() => offer.value.calculation?.recommendation ?? null);

const recommendationDisplay = computed(() => {
    if (! recommendationText.value) {
        return null;
    }

    if (recommendationText.value === POSITIVE_RECOMMENDATION) {
        return `Empfehlung: ${recommendationText.value}`;
    }

    return recommendationText.value;
});

const billingForm = reactive({
    name: '',
    company: '',
    email: '',
    street: '',
    zip: '',
    city: '',
});

const billingErrors = reactive({});
const savingBillingAddress = ref(false);
const billingMessage = reactive({
    status: 'idle',
    message: '',
});

const syncBillingForm = (customer) => {
    const fallback = offer.value.form_inputs?.billing_address ?? {};
    const contactFallback = offer.value.form_inputs?.contact ?? {};

    billingForm.name = customer?.billing_name
        ?? fallback.name
        ?? customer?.name
        ?? contactFallback.name
        ?? '';

    billingForm.company = customer?.billing_company
        ?? fallback.company
        ?? '';

    billingForm.email = customer?.billing_email
        ?? fallback.email
        ?? customer?.email
        ?? contactFallback.email
        ?? '';

    billingForm.street = customer?.billing_street
        ?? fallback.street
        ?? '';
    billingForm.zip = customer?.billing_zip
        ?? fallback.zip
        ?? '';
    billingForm.city = customer?.billing_city
        ?? fallback.city
        ?? '';
};

watch(
    () => offer.value.customer,
    (customer) => {
        syncBillingForm(customer ?? {});
    },
    { deep: true, immediate: true },
);

const hasAddress = computed(() =>
    Object.values(addressInputs.value ?? {}).some((value) => value && String(value).trim() !== '')
);

const hasBillingAddress = computed(() => {
    const street = customerData.value?.billing_street;
    const zip = customerData.value?.billing_zip;
    const city = customerData.value?.billing_city;
    const name = customerData.value?.billing_name;
    const email = customerData.value?.billing_email;

    return [street, zip, city, name, email].every((value) => {
        if (value === null || value === undefined) {
            return false;
        }

        return String(value).trim().length > 0;
    });
});

const clearBillingErrors = () => {
    Object.keys(billingErrors).forEach((key) => {
        delete billingErrors[key];
    });
};

const resetBillingMessage = () => {
    if (billingMessage.status !== 'idle') {
        billingMessage.status = 'idle';
        billingMessage.message = '';
    }
};

const setBillingErrors = (errors) => {
    clearBillingErrors();

    Object.entries(errors ?? {}).forEach(([key, value]) => {
        if (! key.startsWith('billing_address.')) {
            return;
        }

        const field = key.replace('billing_address.', '');
        const messages = Array.isArray(value) ? value : [value];

        if (messages.length > 0) {
            billingErrors[field] = messages[0];
        }
    });
};

const validateBillingForm = () => {
    clearBillingErrors();

    const trimmedName = billingForm.name?.trim() ?? '';
    const trimmedCompany = billingForm.company?.trim() ?? '';
    const trimmedEmail = billingForm.email?.trim() ?? '';
    const trimmedStreet = billingForm.street?.trim() ?? '';
    const trimmedZip = billingForm.zip?.trim() ?? '';
    const trimmedCity = billingForm.city?.trim() ?? '';

    if (! trimmedName) {
        billingErrors.name = 'Bitte geben Sie den Rechnungsempfänger an.';
    }

    if (trimmedCompany && trimmedCompany.length > 255) {
        billingErrors.company = 'Die Firmenbezeichnung ist zu lang.';
    }

    if (! trimmedEmail) {
        billingErrors.email = 'Bitte geben Sie eine gültige Rechnungs-E-Mail an.';
    } else if (! billingEmailPattern.test(trimmedEmail)) {
        billingErrors.email = 'Bitte geben Sie eine gültige Rechnungs-E-Mail an.';
    }

    if (! trimmedStreet) {
        billingErrors.street = 'Bitte geben Sie Ihre Straße und Hausnummer ein.';
    }

    if (! trimmedZip) {
        billingErrors.zip = 'Bitte geben Sie eine gültige Postleitzahl ein.';
    } else if (! /^\d{5}$/.test(trimmedZip)) {
        billingErrors.zip = 'Die Postleitzahl muss aus fünf Ziffern bestehen.';
    }

    if (! trimmedCity) {
        billingErrors.city = 'Bitte geben Sie Ihren Wohnort an.';
    }

    return Object.keys(billingErrors).length === 0;
};

const BILLING_SAVE_ERROR = 'Die Rechnungsadresse konnte nicht gespeichert werden.';
const billingEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const saveBillingAddress = async () => {
    if (! offer.value?.token || offer.value.is_confirmed || savingBillingAddress.value) {
        return;
    }

    billingMessage.status = 'idle';
    billingMessage.message = '';

    const isValid = validateBillingForm();

    if (! isValid) {
        billingMessage.status = 'error';
        billingMessage.message = 'Bitte prüfen Sie die markierten Eingaben.';
        return;
    }

    savingBillingAddress.value = true;

    try {
        const response = await fetch(`/angebote/${offer.value.token}/billing-address`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                billing_address: {
                    name: billingForm.name?.trim() ?? '',
                    company: billingForm.company?.trim() || null,
                    email: billingForm.email?.trim() ?? '',
                    street: billingForm.street?.trim() ?? '',
                    zip: billingForm.zip?.trim() ?? '',
                    city: billingForm.city?.trim() ?? '',
                },
            }),
        });

        const responseBody = await response.json().catch(() => ({}));

        if (! response.ok) {
            if (responseBody?.errors) {
                setBillingErrors(responseBody.errors);
            }

            const errorMessage = responseBody?.message ?? BILLING_SAVE_ERROR;
            throw new Error(errorMessage);
        }

        if (responseBody?.data) {
            updateOffer(responseBody.data);
        }

        billingMessage.status = 'success';
        billingMessage.message = responseBody?.message ?? 'Rechnungsadresse gespeichert.';
    } catch (error) {
        billingMessage.status = 'error';
        billingMessage.message = error?.message ?? BILLING_SAVE_ERROR;
    } finally {
        savingBillingAddress.value = false;
    }
};

watch(
    () => billingForm.name,
    (value) => {
        resetBillingMessage();

        if (billingErrors.name && (value?.trim()?.length ?? 0) > 0) {
            delete billingErrors.name;
        }
    },
);

watch(
    () => billingForm.email,
    (value) => {
        resetBillingMessage();

        if (billingErrors.email) {
            const trimmed = value?.trim() ?? '';

            if (billingEmailPattern.test(trimmed)) {
                delete billingErrors.email;
            }
        }
    },
);

watch(
    () => billingForm.company,
    (value) => {
        resetBillingMessage();

        if (billingErrors.company) {
            const trimmed = value?.trim() ?? '';

            if (! trimmed || trimmed.length <= 255) {
                delete billingErrors.company;
            }
        }
    },
);

watch(
    () => billingForm.street,
    (value) => {
        resetBillingMessage();

        if (billingErrors.street && (value?.trim()?.length ?? 0) > 0) {
            delete billingErrors.street;
        }
    },
);

watch(
    () => billingForm.zip,
    (value) => {
        resetBillingMessage();

        if (billingErrors.zip) {
            const trimmed = value?.trim() ?? '';

            if (/^\d{5}$/.test(trimmed)) {
                delete billingErrors.zip;
            }
        }
    },
);

watch(
    () => billingForm.city,
    (value) => {
        resetBillingMessage();

        if (billingErrors.city && (value?.trim()?.length ?? 0) > 0) {
            delete billingErrors.city;
        }
    },
);

watch(
    hasBillingAddress,
    (value) => {
        if (value && confirmationState.status === 'error' && confirmationState.error === BILLING_REQUIRED_MESSAGE) {
            confirmationState.status = 'idle';
            confirmationState.error = '';
        }
    },
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
    fertig: 'Fertigbauweise',
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

const vatPercent = computed(() => offer.value?.pricing?.vat_percent ?? 19);

const applyVatToValue = (value) => {
    if (value === null || value === undefined) {
        return null;
    }

    return Math.round(value * (100 + vatPercent.value) / 100);
};

const priceOnRequest = computed(() => Boolean(offer.value?.pricing?.price_on_request));

const hasDiscount = computed(() => {
    const code = offer.value?.pricing?.discount_code;
    const percent = offer.value?.pricing?.discount_percent;
    return Boolean(code && percent !== null && percent !== undefined);
});

const discountPercent = computed(() => offer.value?.pricing?.discount_percent ?? null);
const discountNetAmount = computed(() => offer.value?.pricing?.discount_eur ?? 0);
const discountGrossAmount = computed(() => {
    if (! hasDiscount.value || priceOnRequest.value) {
        return null;
    }

    return applyVatToValue(discountNetAmount.value);
});

const discountGrossDisplay = computed(() => {
    if (discountGrossAmount.value === null || discountGrossAmount.value === undefined) {
        return '—';
    }

    return formatCurrency(discountGrossAmount.value);
});

const displayLineItemAmount = (value) => {
    if (priceOnRequest.value) {
        return '—';
    }

    if (value === null || value === undefined) {
        return '—';
    }

    const grossAmount = applyVatToValue(value);
    return grossAmount === null ? '—' : formatCurrency(grossAmount);
};

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
const supportName = computed(() => props.contactSettings?.support_name ?? 'Ihr EVALIO-Team');

const BILLING_REQUIRED_MESSAGE = 'Bitte ergänzen Sie Ihre Rechnungsadresse, bevor Sie das Angebot bestätigen.';

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

    if (! hasBillingAddress.value) {
        confirmationState.status = 'error';
        confirmationState.message = '';
        confirmationState.error = BILLING_REQUIRED_MESSAGE;
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
                            <h2 class="text-xl font-semibold text-gray-900">Ihre Ersteinschätzung</h2>
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
                            <div v-if="hasAddress" class="mt-6 rounded-2xl bg-gradient-to-br from-[#d9bf8c]/10 to-[#d9bf8c]/5 p-4 border border-[#d9bf8c]/20 sm:p-6">
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0 text-[#d9bf8c]">
                                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Objektadresse</span>
                                </h3>
                                <p class="mt-2 text-sm font-medium text-gray-900 break-words sm:text-base">
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
                                        {{ recommendationDisplay ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Restnutzungsdauer nach Ersteinschätzung</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ rndIntervalLabel ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">voraussichtliche Abschreibung (AfA)</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">
                                        {{ offer.calculation?.afa_percent_label ?? '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-8 rounded-3xl bg-white p-8 shadow-xl ring-1 ring-black/5">
                            <h2 class="text-xl font-semibold text-gray-900">Preise</h2>
                            <p
                                v-if="priceOnRequest"
                                class="mt-2 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"
                            >
                                Der Preis wird aktuell individuell kalkuliert. Sie erhalten die genaue Angebotsumme nach unserer manuellen Prüfung per E-Mail.
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
                                        class="flex w-full flex-col items-start justify-between gap-2 rounded-2xl border px-4 py-3 text-left text-sm transition hover:border-[#d9bf8c] hover:bg-white sm:flex-row sm:items-center"
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
                                        class="flex w-full flex-col items-start justify-between gap-2 rounded-2xl border px-4 py-3.5 text-left text-sm transition hover:border-[#d9bf8c] hover:bg-white sm:flex-row sm:items-center sm:py-3"
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
                                        <span class="text-sm font-semibold text-slate-900 sm:text-right">{{ formatPackagePrice(pkg.price_eur) }}</span>
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

                            <div
                                v-if="!priceOnRequest"
                                class="mt-6 rounded-2xl border border-slate-200 bg-white p-6"
                            >
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Rabattcode einlösen
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Tragen Sie Ihren Rabattcode ein, um einen prozentualen Nachlass auf den Gesamtbetrag zu erhalten.
                                </p>

                                <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center" @submit.prevent="applyDiscountCode">
                                    <input
                                        v-model="discountCodeInput"
                                        type="text"
                                        placeholder="z. B. EVALIO10"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :disabled="isApplyingDiscount || offer.is_confirmed"
                                    />
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-[#d9bf8c] px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40"
                                            :disabled="isApplyingDiscount || offer.is_confirmed"
                                        >
                                            <svg
                                                v-if="isApplyingDiscount"
                                                class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                            </svg>
                                            Rabatt anwenden
                                        </button>
                                        <button
                                            v-if="hasDiscount"
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-100"
                                            :disabled="isApplyingDiscount || offer.is_confirmed"
                                            @click="removeDiscountCode"
                                        >
                                            Rabatt entfernen
                                        </button>
                                    </div>
                                </form>

                                <div v-if="discountMessage.status !== 'idle'" class="mt-3">
                                    <p
                                        v-if="discountMessage.status === 'success'"
                                        class="text-sm text-emerald-600"
                                    >
                                        {{ discountMessage.message }}
                                    </p>
                                    <p
                                        v-else-if="discountMessage.status === 'error'"
                                        class="text-sm text-red-600"
                                    >
                                        {{ discountMessage.message }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-slate-500"
                                    >
                                        Rabattcode wird geprüft …
                                    </p>
                                </div>

                                <p
                                    v-if="hasDiscount && discountPercent !== null"
                                    class="mt-2 text-xs font-medium text-emerald-600"
                                >
                                    Aktiver Rabatt: {{ offer.pricing.discount_code }} ({{ discountPercent }} %)
                                </p>
                            </div>

                            <!-- Desktop: Tabelle -->
                            <div class="mt-6 hidden overflow-hidden rounded-2xl border border-slate-200 sm:block">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-slate-500">
                                                Leistung
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-sm font-semibold text-slate-500">
                                                Preis (brutto)
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

                            <!-- Mobile: Card-Layout -->
                            <div class="mt-6 space-y-3 sm:hidden">
                                <div
                                    v-for="item in offer.pricing?.line_items || []"
                                    :key="item.key"
                                    class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                >
                                    <span class="text-sm font-medium text-slate-900">
                                        {{ item.label ?? 'Position' }}
                                    </span>
                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ displayLineItemAmount(item.amount_eur) }}
                                    </span>
                                </div>
                            </div>

                            <dl class="mt-6 space-y-3 text-sm text-slate-600">
                                <div v-if="hasDiscount && !priceOnRequest" class="flex justify-between text-sm">
                                    <dt>
                                        Rabattcode {{ offer.pricing?.discount_code }}
                                        <span v-if="discountPercent !== null"> ({{ discountPercent }} %)</span>
                                    </dt>
                                    <dd class="font-semibold text-emerald-600">
                                        -{{ discountGrossDisplay }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 px-4 py-3 text-base font-semibold text-white shadow-lg">
                                    <dt>Gesamtbetrag (inkl. MwSt.)</dt>
                                    <dd>{{ grossTotalDisplay }}</dd>
                                </div>
                            </dl>
                            <p v-if="!priceOnRequest" class="mt-3 text-xs text-slate-500">
                                Alle oben genannten Preise enthalten bereits 19&nbsp;% MwSt.
                            </p>
                        </div>

                        <div class="mt-8 rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Rechnungsadresse</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Wir benötigen Ihre Rechnungsanschrift, um die Bestätigung und spätere Unterlagen korrekt auszustellen.
                            </p>

                            <form class="mt-4 grid gap-4 md:grid-cols-2" @submit.prevent="saveBillingAddress">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="billing-name">
                                        Rechnungsempfänger <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="billing-name"
                                        v-model="billingForm.name"
                                        type="text"
                                        required
                                        autocomplete="name"
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.name }"
                                        placeholder="z. B. Max Mustermann"
                                    />
                                    <p v-if="billingErrors.name" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.name }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="billing-company">
                                        Firma (optional)
                                    </label>
                                    <input
                                        id="billing-company"
                                        v-model="billingForm.company"
                                        type="text"
                                        autocomplete="organization"
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.company }"
                                        placeholder="z. B. Evalio GmbH"
                                    />
                                    <p v-if="billingErrors.company" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.company }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="billing-email">
                                        Rechnungs-E-Mail <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="billing-email"
                                        v-model="billingForm.email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.email }"
                                        placeholder="z. B. buchhaltung@firma.de"
                                    />
                                    <p v-if="billingErrors.email" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.email }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="billing-street">
                                        Straße &amp; Hausnummer <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="billing-street"
                                        v-model="billingForm.street"
                                        type="text"
                                        required
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.street }"
                                        placeholder="z. B. Musterstraße 123"
                                    />
                                    <p v-if="billingErrors.street" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.street }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700" for="billing-zip">
                                        PLZ <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="billing-zip"
                                        v-model="billingForm.zip"
                                        type="text"
                                        inputmode="numeric"
                                        pattern="\d{5}"
                                        maxlength="5"
                                        required
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.zip }"
                                        placeholder="z. B. 12345"
                                    />
                                    <p v-if="billingErrors.zip" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.zip }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700" for="billing-city">
                                        Ort <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="billing-city"
                                        v-model="billingForm.city"
                                        type="text"
                                        required
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': billingErrors.city }"
                                        placeholder="z. B. Berlin"
                                    />
                                    <p v-if="billingErrors.city" class="mt-1 text-xs text-red-600">
                                        {{ billingErrors.city }}
                                    </p>
                                </div>

                                <div class="md:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-[#d9bf8c] px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40"
                                        :disabled="offer.is_confirmed || savingBillingAddress"
                                    >
                                        <svg
                                            v-if="savingBillingAddress"
                                            class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                        </svg>
                                        Rechnungsadresse speichern
                                    </button>

                                    <p
                                        v-if="billingMessage.status !== 'idle'"
                                        :class="[
                                            'text-sm',
                                            billingMessage.status === 'success' ? 'text-emerald-600' : 'text-red-600',
                                        ]"
                                    >
                                        {{ billingMessage.message }}
                                    </p>
                                </div>

                                <p v-if="!hasBillingAddress" class="md:col-span-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    Bitte ergänzen Sie Ihre Rechnungsadresse, um das Angebot bestätigen zu können.
                                </p>
                            </form>
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
                                            class="mt-1 h-4 w-4 flex-shrink-0 rounded border-slate-300 text-[#d9bf8c] focus:ring-[#d9bf8c]"
                                        />
                                        <span class="space-y-2 leading-relaxed">
                                            <span class="block text-sm font-semibold text-slate-900">
                                                Ich bin einverstanden und verlange ausdrücklich, dass Sie vor Ablauf der Widerrufsfrist mit der Ausführung der beauftragten Dienstleistung beginnen.<span class="text-red-500">*</span>
                                            </span>
                                            <span class="block text-xs text-slate-700 sm:text-sm">
                                                Mir ist bekannt, dass ich bei vollständiger Vertragserfüllung durch Sie mein Widerrufsrecht verliere.
                                            </span>
                                            <span class="block text-xs text-slate-700 sm:text-sm">
                                                Die
                                                <a href="https://evalio-nutzungsdauer.de/wp-content/uploads/2025/11/EVALIO_AGB´s.pdf" target="_blank" rel="noreferrer" class="underline text-[#c4a875] hover:text-[#d9bf8c]">AGB</a>
                                                und
                                                <a href="https://evalio-nutzungsdauer.de/wp-content/uploads/2025/11/Widerrufsbelehrung.pdf" target="_blank" rel="noreferrer" class="underline text-[#c4a875] hover:text-[#d9bf8c]">Widerrufsbelehrung</a>
                                                habe ich zur Kenntnis genommen und akzeptiert.
                                            </span>
                                            <span class="block text-xs text-slate-500">
                                                Alle Preise verstehen sich inkl. MwSt.
                                            </span>
                                            <span class="block text-xs text-slate-500">
                                                <span class="font-semibold">*</span> Pflichtfeld
                                            </span>
                                        </span>
                                    </label>
                                    <p
                                        v-if="confirmationState.status === 'error' && confirmationState.error === CONSENT_REQUIRED_MESSAGE"
                                        class="mt-3 rounded-lg bg-red-100 px-3 py-2 text-xs text-red-700 sm:text-sm"
                                    >
                                        {{ CONSENT_REQUIRED_MESSAGE }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    :disabled="confirming || ! offer.can_confirm || ! confirmationConsent"
                                    @click="confirmOffer"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#d9bf8c] px-6 py-4 text-base font-semibold text-slate-900 shadow-lg transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40 sm:py-3"
                                >
                                    <svg
                                        v-if="confirming"
                                        class="h-5 w-5 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    Gutachten jetzt verbindlich beauftragen
                                </button>
                                <p class="text-xs text-slate-500">
                                    Wir senden Ihnen sofort eine Bestätigungs-E-Mail. Bei einer positiven Empfehlung erhält auch das EVALIO-Team eine Benachrichtigung.
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
                                            <dt class="font-medium text-gray-500">Rechnungsempfänger</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ formatOptional(customerData.billing_name) }}
                                            </dd>
                                        </div>
                                        <div v-if="customerData.billing_company" class="sm:col-span-2">
                                            <dt class="font-medium text-gray-500">Firma</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">
                                                {{ customerData.billing_company }}
                                            </dd>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <dt class="font-medium text-gray-500">Rechnungs-E-Mail</dt>
                                            <dd class="mt-1 font-semibold text-gray-900 break-words">
                                                <a
                                                    v-if="customerData.billing_email"
                                                    :href="`mailto:${customerData.billing_email}`"
                                                    class="text-[#d9bf8c] hover:text-[#c4a875] hover:underline"
                                                >
                                                    {{ customerData.billing_email }}
                                                </a>
                                                <span v-else>—</span>
                                            </dd>
                                        </div>
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
                                    
                                    <!-- Desktop: Tabelle -->
                                    <div class="mt-3 hidden overflow-hidden rounded-2xl border border-gray-200 sm:block">
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

                                    <!-- Mobile: Card-Layout -->
                                    <div class="mt-3 space-y-3 sm:hidden">
                                        <div
                                            v-for="item in renovationInputs"
                                            :key="item.category_key"
                                            class="rounded-2xl border border-gray-200 bg-white p-4"
                                        >
                                            <h4 class="font-semibold text-gray-900">
                                                {{ item.label ?? item.category_key ?? 'Kategorie' }}
                                            </h4>
                                            <dl class="mt-2 space-y-1 text-sm">
                                                <div class="flex justify-between">
                                                    <dt class="text-gray-500">Umfang:</dt>
                                                    <dd class="font-medium text-gray-700">
                                                        {{ formatExtentPercent(item.extent_percent) }}
                                                    </dd>
                                                </div>
                                                <div class="flex justify-between">
                                                    <dt class="text-gray-500">Zeitpunkt:</dt>
                                                    <dd class="font-medium text-gray-700">
                                                        {{ formatTimeWindow(item.time_window_key) }}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
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
