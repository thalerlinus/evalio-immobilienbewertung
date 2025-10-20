<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
    propertyTypes: {
        type: Array,
        default: () => [],
    },
    renovationCategories: {
        type: Array,
        default: () => [],
    },
    timeWindowOptions: {
        type: Array,
        default: () => [],
    },
    extentOptions: {
        type: Array,
        default: () => [],
    },
    contactSettings: {
        type: Object,
        default: () => ({}),
    },
});

const submitting = ref(false);
const result = ref(null);
const offer = ref(null);
const errors = reactive({});

const defaultRenovations = () =>
    props.renovationCategories.map((category) => ({
        category_key: category.key,
        time_window_key: 'nicht',
        extent_percent: 0,
    }));

const form = reactive({
    property_type_key: '',
    gnd_override: '',
    baujahr: '',
    anschaffungsjahr: '',
    steuerjahr: '',
    bauweise: 'massiv',
    eigennutzung: false,
    renovations: defaultRenovations(),
    address: {
        street: '',
        zip: '',
        city: '',
        country: 'DE',
    },
    contact: {
        name: '',
        email: '',
        phone: '',
    },
    acceptTerms: false,
});

const categoryMap = computed(() => {
    const map = {};
    props.renovationCategories.forEach((category) => {
        map[category.key] = category;
    });
    return map;
});

const extentWeightMap = computed(() => {
    const map = {};
    props.extentOptions.forEach((option) => {
        map[option.value] = option.weight ?? (option.value ? option.value / 100 : 0);
    });
    return map;
});

const selectedPropertyType = computed(() =>
    props.propertyTypes.find((type) => type.key === form.property_type_key) ?? null,
);

const isRequestOnlyProperty = computed(() => Boolean(selectedPropertyType.value?.request_only));

const requestOnlyHeadline = computed(() => {
    const label = selectedPropertyType.value?.label;
    return label
        ? `Für ${label} ist keine Online-Ersteinschätzung möglich`
        : 'Für diese Immobilienart ist keine Online-Ersteinschätzung möglich';
});

const requestOnlyMessage = computed(() =>
    'Bitte kontaktieren Sie uns direkt, damit wir ein individuelles Angebot erstellen können.'
);

const supportEmail = computed(() => props.contactSettings?.support_email ?? 'kontakt@evalio.de');
const supportPhoneDisplay = computed(
    () => props.contactSettings?.support_phone_display ?? '+49 9999 99999'
);
const supportPhoneHref = computed(() => {
    const raw = props.contactSettings?.support_phone ?? supportPhoneDisplay.value;
    return raw ? String(raw).replace(/\s+/g, '') : '';
});
const supportName = computed(() => props.contactSettings?.support_name ?? 'Ihr Evalio-Team');

watch(isRequestOnlyProperty, (value) => {
    if (value) {
        result.value = null;
        offer.value = null;
        clearErrors();
    }
});

const factorFor = (categoryKey, windowKey) => {
    const category = categoryMap.value[categoryKey];
    if (!category) {
        return 0;
    }
    const factor = category.time_factors?.find((entry) => entry.key === windowKey);
    return factor ? factor.factor : 0;
};

const weightFor = (percent) => {
    const value = percent ?? 0;
    // Wenn ein exakter Wert in der Map existiert, verwenden wir ihn
    if (extentWeightMap.value[value] !== undefined) {
        return extentWeightMap.value[value];
    }
    // Ansonsten berechnen wir das Gewicht direkt aus dem Prozentsatz
    return value / 100;
};

const categoryPoints = (category, renovation) => {
    const weight = weightFor(renovation.extent_percent ?? 0);
    const factor = factorFor(category.key, renovation.time_window_key ?? 'nicht');
    return +(category.max_points * weight * factor).toFixed(2);
};

const totalPoints = computed(() =>
    form.renovations.reduce((sum, renovation) => {
        const category = categoryMap.value[renovation.category_key];
        if (!category) {
            return sum;
        }
        return sum + categoryPoints(category, renovation);
    }, 0),
);

const previewScore = computed(() => Math.round(totalPoints.value * 2) / 2);

const validationMessages = computed(() => Object.values(errors).flat().filter(Boolean));

const clearErrors = () => {
    Object.keys(errors).forEach((key) => {
        delete errors[key];
    });
};

const resetForm = () => {
    form.property_type_key = props.propertyTypes[0]?.key ?? '';
    form.gnd_override = '';
    form.baujahr = '';
    form.anschaffungsjahr = '';
    form.steuerjahr = '';
    form.bauweise = 'massiv';
    form.eigennutzung = false;
    form.renovations = defaultRenovations();
    form.address = {
        street: '',
        zip: '',
        city: '',
        country: 'DE',
    };
    form.contact = {
        name: '',
        email: '',
        phone: '',
    };
    form.acceptTerms = false;
    result.value = null;
    offer.value = null;
    clearErrors();
};

const formatNumber = (value, fractionDigits = 2) => {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '-';
    }
    return Number(value).toLocaleString('de-DE', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
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
    }).format(Number(value));
};

const formatIntervalLabel = (min, max) => {
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
};

const intervalLabel = computed(() => {
    if (!result.value) {
        return null;
    }

    return result.value.rnd_interval_label
        ?? formatIntervalLabel(result.value.rnd_min, result.value.rnd_max);
});

const validationMessageMap = {
    property_type_key: 'Bitte wählen Sie eine Immobilienart aus.',
    gnd_override: 'Die optionale Gesamtnutzungsdauer muss zwischen 1 und 200 Jahren liegen.',
    baujahr: 'Bitte geben Sie ein gültiges Baujahr ein.',
    anschaffungsjahr: 'Bitte geben Sie ein gültiges Anschaffungsjahr ein (mindestens Baujahr).',
    steuerjahr: 'Bitte geben Sie ein gültiges Steuerjahr ein (mindestens Anschaffungsjahr).',
    bauweise: 'Bitte wählen Sie eine gültige Bauweise aus.',
    eigennutzung: 'Bitte geben Sie an, ob Eigennutzung vorliegt.',
    'contact.email': 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'contact.phone': 'Bitte geben Sie eine gültige Telefonnummer ein.',
    'contact.name': 'Bitte geben Sie einen Namen mit höchstens 255 Zeichen ein.',
    notes: 'Die Notiz darf höchstens 1000 Zeichen enthalten.',
    'renovations.*': 'Bitte prüfen Sie die Angaben zu den Sanierungsmaßnahmen.',
    'address.street': 'Die Straße darf höchstens 255 Zeichen enthalten.',
    'address.zip': 'Die Postleitzahl darf höchstens 20 Zeichen enthalten.',
    'address.city': 'Der Ort darf höchstens 255 Zeichen enthalten.',
    'address.country': 'Bitte geben Sie ein gültiges Länderkürzel an.',
    accept_terms: 'Bitte bestätigen Sie die Datenschutzhinweise, bevor Sie fortfahren.',
};

const resolveValidationMessage = (key, fallback) => {
    if (validationMessageMap[key]) {
        return validationMessageMap[key];
    }

    const wildcardEntry = Object.entries(validationMessageMap).find(([mapKey]) =>
        mapKey.endsWith('.*') && key.startsWith(mapKey.replace('.*', ''))
    );

    if (wildcardEntry) {
        return wildcardEntry[1];
    }

    return fallback || 'Bitte überprüfen Sie Ihre Eingaben.';
};

const submit = async () => {
    if (submitting.value) {
        return;
    }

    if (isRequestOnlyProperty.value) {
        clearErrors();
        errors.general = [
            `${requestOnlyHeadline.value}. ${requestOnlyMessage.value}`,
        ];
        return;
    }

    clearErrors();

    if (!form.acceptTerms) {
        errors.accept_terms = [
            'Bitte bestätigen Sie die Zustimmung zur Verarbeitung Ihrer Angaben.',
        ];
        errors.general = [
            'Bitte bestätigen Sie die Pflicht-Checkbox, um fortzufahren.',
        ];
        return;
    }

    submitting.value = true;
    result.value = null;
    offer.value = null;

    const payload = {
        property_type_key: form.property_type_key || null,
        gnd_override: form.gnd_override ? Number(form.gnd_override) : null,
        baujahr: form.baujahr ? Number(form.baujahr) : null,
        anschaffungsjahr: form.anschaffungsjahr ? Number(form.anschaffungsjahr) : null,
        steuerjahr: form.steuerjahr ? Number(form.steuerjahr) : null,
        bauweise: form.bauweise || null,
        eigennutzung: Boolean(form.eigennutzung),
        renovations: form.renovations.map((item) => ({
            category_key: item.category_key,
            time_window_key: item.time_window_key,
            extent_percent: Number(item.extent_percent) || 0,
        })),
        address: {
            street: form.address.street || null,
            zip: form.address.zip || null,
            city: form.address.city || null,
            country: form.address.country || null,
        },
        contact: {
            name: form.contact.name || null,
            email: form.contact.email || null,
            phone: form.contact.phone || null,
        },
    };

    try {
        const response = await fetch('/api/rnd/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            const data = await response.json();
            result.value = data.data?.calculation ?? null;
            offer.value = data.data?.offer ?? null;

            const offerUrl = data.data?.offer?.public_url;
            if (offerUrl) {
                window.location.href = offerUrl;
                return;
            }
        } else if (response.status === 422) {
            const data = await response.json();
            if (data?.errors) {
                Object.entries(data.errors).forEach(([key, value]) => {
                    const messages = Array.isArray(value) ? value : [value];
                    errors[key] = messages.map((message) => resolveValidationMessage(key, message));
                });
            }

            errors.general = ['Bitte prüfen Sie Ihre Eingaben und korrigieren Sie die markierten Felder.'];
        } else {
            errors.general = [
                'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es erneut.',
            ];
        }
    } catch (error) {
        errors.general = [
            'Die Anfrage konnte nicht gesendet werden. Bitte überprüfen Sie Ihre Verbindung.',
        ];
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <MainLayout
        title="Evalio Immobilien-Rechner"
        :user="$page.props.auth?.user"
        :can-login="canLogin"
        :can-register="canRegister"
    >
        <section class="bg-slate-900 py-16 text-white">
            <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 text-center">
                <span class="mx-auto rounded-full bg-slate-800 px-4 py-1 text-sm uppercase tracking-widest text-slate-300">
                    Restnutzungsdauer & AfA ermitteln
                </span>
                <h1 class="text-3xl font-bold md:text-5xl">
                    Evalio Immobilien-Rechner
                </h1>
                <p class="text-lg text-slate-200 md:text-xl">
                    Erfassen Sie Objekt- und Sanierungsdaten, berechnen Sie die Restnutzungsdauer (RND), AfA-Satz
                    und erhalten Sie eine Empfehlung für ein Gutachten – direkt im Browser.
                </p>
            </div>
        </section>

        <section class="bg-slate-100 py-12">
            <div class="mx-auto max-w-4xl px-4">
                <div class="w-full space-y-6">
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-900">
                            Eingabemaske
                        </h2>
                    </div>

                    <form
                        @submit.prevent="submit"
                        class="space-y-8"
                        :aria-busy="submitting"
                    >
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                Objektinformationen
                            </h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">
                                        Immobilienart <span class="text-red-600">*</span>
                                    </label>
                                    <select
                                        v-model="form.property_type_key"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option disabled value="">
                                            Bitte wählen
                                        </option>
                                        <option
                                            v-for="type in propertyTypes"
                                            :key="type.key"
                                            :value="type.key"
                                        >
                                            {{ type.label }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="selectedPropertyType && !isRequestOnlyProperty"
                                        class="mt-2 text-sm text-slate-500"
                                    >
                                        Gesamtnutzungsdauer: <strong>{{ selectedPropertyType.gnd ?? '—' }} Jahre</strong>
                                        • Standardpreis: <strong>
                                            {{
                                                selectedPropertyType.price_standard_eur
                                                    ? formatCurrency(selectedPropertyType.price_standard_eur)
                                                    : 'auf Anfrage'
                                            }}
                                        </strong>
                                    </p>
                                    <div
                                        v-else-if="selectedPropertyType && isRequestOnlyProperty"
                                        class="mt-3 rounded-xl border-2 border-red-500 bg-red-50 p-4 text-sm text-red-700"
                                    >
                                        <span class="block text-base font-semibold text-red-700">
                                            {{ requestOnlyHeadline }}
                                        </span>
                                        <span class="mt-2 block text-sm text-red-700">
                                            {{ requestOnlyMessage }}
                                        </span>
                                        <span class="mt-4 block text-sm text-red-700">
                                            Ansprechpartner: {{ supportName }} · Telefon:
                                            <a :href="`tel:${supportPhoneHref}`" class="font-semibold underline decoration-red-400">
                                                {{ supportPhoneDisplay }}
                                            </a>
                                            · E-Mail:
                                            <a :href="`mailto:${supportEmail}`" class="font-semibold underline decoration-red-400">
                                                {{ supportEmail }}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                                <template v-if="!isRequestOnlyProperty">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Baujahr <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.baujahr"
                                            type="number"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="z. B. 1980"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Anschaffungsjahr <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.anschaffungsjahr"
                                            type="number"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="z. B. 2020"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Steuerjahr <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.steuerjahr"
                                            type="number"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="z. B. 2025"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Bauweise</label>
                                        <select
                                            v-model="form.bauweise"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="massiv">Massivbauweise</option>
                                            <option value="holz">Holzbauweise</option>
                                            <option value="unbekannt">Unbekannt</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2 pt-6">
                                        <input
                                            id="eigennutzung"
                                            v-model="form.eigennutzung"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label for="eigennutzung" class="text-sm font-medium text-slate-700">
                                            Eigennutzung
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div
                            v-if="!isRequestOnlyProperty"
                            class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                        >
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                Adresse (optional)
                            </h3>
                            <p class="mb-4 text-xs text-slate-500">
                                Hinweis: Diese Angaben beziehen sich auf die Adresse der Immobilie (Objektadresse).
                            </p>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Straße</label>
                                    <input
                                        v-model="form.address.street"
                                        type="text"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">PLZ</label>
                                    <input
                                        v-model="form.address.zip"
                                        type="text"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Ort</label>
                                    <input
                                        v-model="form.address.city"
                                        type="text"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Land</label>
                                    <input
                                        v-model="form.address.country"
                                        type="text"
                                        maxlength="2"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!isRequestOnlyProperty"
                            class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                        >
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-slate-900">
                                    Sanierungen & Zustandsbewertung
                                </h3>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div
                                    v-for="(category, index) in renovationCategories"
                                    :key="category.key"
                                    class="rounded-xl border border-slate-200 p-4"
                                >
                                    <h4 class="mb-3 text-sm font-semibold text-slate-900">
                                        {{ category.label }}
                                    </h4>

                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-slate-700">
                                            Umfang: {{ form.renovations[index].extent_percent }}%
                                            <span v-if="extentOptions.find(opt => opt.value === form.renovations[index].extent_percent)" class="text-slate-500">
                                                ({{ extentOptions.find(opt => opt.value === form.renovations[index].extent_percent).label }})
                                            </span>
                                        </label>
                                        <input
                                            type="range"
                                            v-model.number="form.renovations[index].extent_percent"
                                            :min="0"
                                            :max="100"
                                            :step="20"
                                            class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                        />
                                        <div class="flex justify-between text-xs text-slate-500">
                                            <span>0%</span>
                                            <span>20%</span>
                                            <span>40%</span>
                                            <span>60%</span>
                                            <span>80%</span>
                                            <span>100%</span>
                                        </div>
                                    </div>

                                    <label class="mt-3 block text-xs font-medium text-slate-700">
                                        Zeitpunkt
                                    </label>
                                    <select
                                        v-model="form.renovations[index].time_window_key"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option
                                            v-for="option in timeWindowOptions"
                                            :key="option.key"
                                            :value="option.key"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>

                                    <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                                        <div>Gewicht: {{ formatNumber(weightFor(form.renovations[index].extent_percent), 2) }}</div>
                                        <div>
                                            Zeitfaktor: {{ formatNumber(factorFor(category.key, form.renovations[index].time_window_key), 2) }}
                                        </div>
                                        <div class="font-semibold text-slate-900">
                                            Punkte: {{ formatNumber(categoryPoints(category, form.renovations[index]), 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!isRequestOnlyProperty"
                            class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                        >
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                Ihre Kontaktdaten
                            </h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="contact-name">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact-name"
                                        v-model.trim="form.contact.name"
                                        type="text"
                                        required
                                        autocomplete="name"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Max Mustermann"
                                    />
                                    <p
                                        v-if="errors['contact.name']?.length"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors['contact.name'][0] }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="contact-email">
                                        E-Mail-Adresse <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="contact-email"
                                        v-model.trim="form.contact.email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="name@example.de"
                                    />
                                    <p
                                        v-if="errors['contact.email']?.length"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors['contact.email'][0] }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700" for="contact-phone">
                                        Telefonnummer (optional)
                                    </label>
                                    <input
                                        id="contact-phone"
                                        v-model.trim="form.contact.phone"
                                        type="tel"
                                        inputmode="tel"
                                        autocomplete="tel"
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z. B. +49 171 1234567"
                                    />
                                    <p
                                        v-if="errors['contact.phone']?.length"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ errors['contact.phone'][0] }}
                                    </p>
                                </div>
                            </div>
                            <p class="mt-4 text-xs text-slate-500">
                                Wir senden Ihnen das Ergebnis sowie ein individuelles Angebot unmittelbar per E-Mail zu.
                                Die Telefonnummer hilft uns bei Rückfragen, bleibt jedoch freiwillig.
                            </p>
                        </div>

                        <div
                            v-if="!isRequestOnlyProperty"
                            class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                        >
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                Datenschutz & Zustimmung
                            </h3>
                            <div class="space-y-4 text-sm text-slate-700">
                                <label class="flex items-start gap-3" for="accept-terms">
                                    <input
                                        id="accept-terms"
                                        v-model="form.acceptTerms"
                                        type="checkbox"
                                        required
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="space-y-2 leading-snug">
                                        <span class="block">
                                            Ich stimme zu, dass meine Angaben aus dem Formular zur Beantwortung meiner Anfrage verarbeitet und gespeichert werden.<span class="text-red-500">*</span>
                                        </span>
                                        <span class="block text-xs text-slate-600">
                                            Hinweis: Sie können Ihre Einwilligung jederzeit für die Zukunft per E-Mail an
                                            <a :href="`mailto:${supportEmail}`" class="text-blue-600 hover:underline">{{ supportEmail }}</a>
                                            widerrufen.
                                        </span>
                                        <span class="block text-xs text-slate-600">
                                            Weitere Informationen finden Sie in unserer
                                            <a href="/datenschutzerklaerung" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">
                                                Datenschutzerklärung
                                            </a>.
                                        </span>
                                    </span>
                                </label>

                                <p
                                    v-if="errors.accept_terms?.length"
                                    class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
                                >
                                    {{ errors.accept_terms[0] }}
                                </p>

                                <p class="text-xs text-slate-600">
                                    Die
                                    <a href="/agb" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">AGB</a>
                                    und
                                    <a href="/datenschutzerklaerung" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">Datenschutzerklärung</a>
                                    habe ich zur Kenntnis genommen.
                                </p>

                                <p class="text-xs text-slate-500">
                                    <span class="font-semibold">*</span> Pflichtfeld
                                </p>
                            </div>
                        </div>

                        <div v-if="validationMessages.length" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <h4 class="font-semibold">Formularfehler</h4>
                            <ul class="mt-2 list-disc pl-5">
                                <li v-for="(msg, idx) in validationMessages" :key="idx">{{ msg }}</li>
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                :disabled="submitting || isRequestOnlyProperty || !form.acceptTerms || !form.property_type_key"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-blue-400 disabled:hover:bg-blue-400"
                            >
                                <svg
                                    v-if="submitting"
                                    class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>
                                {{ submitting ? 'Wird berechnet...' : 'Berechnen' }}
                            </button>
                            <button
                                type="button"
                                @click="resetForm"
                                class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white"
                                :class="{ 'opacity-50 cursor-not-allowed hover:bg-transparent': submitting }"
                                :disabled="submitting"
                            >
                                Zurücksetzen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
