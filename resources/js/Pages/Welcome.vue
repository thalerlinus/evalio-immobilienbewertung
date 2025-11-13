<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { computed, nextTick, reactive, ref, watch } from 'vue';

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
const currentStep = ref(1);
const showSteuerjahrTooltip = ref(false);
const activeRenovationInfo = ref(null);
const currentYear = new Date().getFullYear();

const defaultRenovations = () =>
    props.renovationCategories.map((category) => ({
        category_key: category.key,
        time_window_key: 'nicht',
        extent_percent: 0,
    }));

const form = reactive({
    property_type_key: '',
    property_type_category: '', // 'mfh' oder 'wgh' für Mehrfamilien-/Wohn-Geschäftshäuser
    unit_count: null, // Anzahl der Wohneinheiten
    gnd_override: '',
    baujahr: '',
    anschaffungsjahr: '',
    steuerjahr: '',
    bauweise: 'massiv',
    eigennutzung: null,
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
    billing_address: {
        street: '',
        zip: '',
        city: '',
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

// Zeitfenster-Optionen in logischer Reihenfolge
const timeWindowOptionsOrdered = computed(() => [
    { key: 'nicht', label: 'Nein, nicht saniert', needsExtent: false },
    { key: 'weiss_nicht', label: 'Weiß ich nicht', needsExtent: false },
    { key: 'bis_5', label: 'Ja, in den letzten 5 Jahren', needsExtent: true },
    { key: 'bis_10', label: 'Ja, vor 5 – 10 Jahren', needsExtent: true },
    { key: 'bis_15', label: 'Ja, vor 10 – 15 Jahren', needsExtent: true },
    { key: 'bis_20', label: 'Ja, vor 15 – 20 Jahren', needsExtent: true },
    { key: 'ueber_20', label: 'Ja, vor über 20 Jahren', needsExtent: true },
]);

// Umfang-Labels für den Slider
const extentLabels = {
    20: 'Nur Ausbesserungsarbeiten',
    40: 'Vereinzelte Maßnahmen',
    60: 'Teilweise erneuert',
    80: 'Überwiegend erneuert',
    100: 'Vollständig saniert'
};

const getExtentLabel = (percent) => {
    return extentLabels[percent] || `${percent}%`;
};

const renovationDisplayConfig = {
    baeder_wc: {
        question: 'Wurden die Bäder und WC-Anlagen saniert?',
        info: 'Wurden z. B. lediglich einzelne Elemente des Badezimmers getauscht, wie ein WC oder Waschbecken? – Bis hin zu: das ganze Badezimmer wurde erneuert.'
    },
    innenausbau: {
        question: 'Wurde der Innenausbau saniert?',
        info: 'Wie viel wurde im Bereich des Innenausbaus erneuert? Zum Innenausbau zählen u. a.:\n• Bodenbeläge\n• Wand- und Deckenverkleidungen\n• Zimmertüren'
    },
    fenster_tueren: {
        question: 'Wurden die Fenster und Außentüren erneuert?',
        info: 'Wurden lediglich einzelne Fenster getauscht? – Bis hin zu: alle Fenster, inklusive der Eingangstüre.'
    },
    heizung: {
        question: 'Wurde die Heizung saniert oder umgebaut?',
        info: 'Wurde lediglich der Brenner einer Heizung getauscht bzw. die Heizung instand gesetzt? – Bis hin zu: es wurde eine neue Heizung verbaut und die Heizkörper in den Wohnräumen getauscht.'
    },
    leitungen: {
        question: 'Wurden die Leitungen saniert?',
        info: 'Wurden lediglich undichte oder verstopfte Stellen erneuert bzw. Absicherungen für die Elektrik nachgerüstet? – Bis hin zu: alle Leitungen des Gebäudes wurden erneuert.'
    },
    dach_waermeschutz: {
        question: 'Wurde das Dach saniert und/oder der Wärmeschutz verbessert?',
        info: 'Wurden lediglich undichte Stellen ausgebessert? – Über: das Dach wurde neu eingedeckt, aber die Dämmung ist unverändert. – Bis hin zu: der komplette Dachstuhl oder die Abdichtung (Flachdach) inkl. Dämmung wurden erneuert.'
    },
    aussenwaende: {
        question: 'Wurden die Außenwände saniert oder gedämmt?',
        info: 'Wurde die Fassade lediglich gesäubert und Putzschäden repariert? – Über: die Fassade wurde neu verputzt, aber es wurde keine Dämmung aufgebracht. – Bis hin zu: die komplette Fassade wurde neu verkleidet und eine Dämmung aufgebracht oder erneuert.'
    },
};

const selectedPropertyType = computed(() =>
    props.propertyTypes.find((type) => type.key === form.property_type_key) ?? null,
);

// Gruppierte Property Types für die Anzeige
const displayPropertyTypes = computed(() => {
    return [
        { key: 'eigentumswohnung', label: 'Eigentumswohnung', category: 'single' },
        { key: 'einfamilienhaus', label: 'Einfamilienhaus', category: 'single' },
        { key: 'zweifamilienhaus', label: 'Zweifamilienhaus', category: 'single' },
        { key: 'dreifamilienhaus', label: 'Dreifamilienhaus', category: 'single' },
        { key: 'mfh', label: 'Mehrfamilienhaus', category: 'multi' },
        { key: 'wgh', label: 'Wohn- und Geschäftshaus', category: 'multi' },
        { key: 'gewerbeobjekt', label: 'Gewerbeobjekt', category: 'single' },
        { key: 'sonstiges', label: 'Sonstiges', category: 'single' },
    ];
});

// Watch für automatische property_type_key Selektion bei MFH/WGH
watch([() => form.property_type_category, () => form.unit_count], ([category, count]) => {
    if (!category || category === 'single') {
        return;
    }
    
    if (!count || count < 4) {
        form.property_type_key = '';
        return;
    }
    
    if (category === 'mfh') {
        form.property_type_key = count <= 10 ? 'mfh_4_10' : 'mfh_10_plus';
    } else if (category === 'wgh') {
        form.property_type_key = count <= 10 ? 'wgh_10_minus' : 'wgh_10_plus';
    }
});

const selectPropertyCategory = (categoryKey) => {
    if (categoryKey === 'mfh' || categoryKey === 'wgh') {
        form.property_type_category = categoryKey;
        form.unit_count = null;
        form.property_type_key = '';
    } else {
        form.property_type_category = 'single';
        form.property_type_key = categoryKey;
        form.unit_count = null;
    }
};

const needsExtentInput = (timeWindowKey) => {
    const option = timeWindowOptionsOrdered.value.find(opt => opt.key === timeWindowKey);
    return option ? option.needsExtent : false;
};

watch(() => form.anschaffungsjahr, (value) => {
    if (value === null || value === undefined || value === '') {
        return;
    }

    const numeric = Number(value);
    if (Number.isNaN(numeric)) {
        return;
    }

    if (numeric > currentYear) {
        form.anschaffungsjahr = currentYear;
    }
});

watch(() => form.steuerjahr, (value) => {
    if (value === null || value === undefined || value === '') {
        return;
    }

    const numeric = Number(value);
    if (Number.isNaN(numeric)) {
        return;
    }

    if (numeric > currentYear) {
        form.steuerjahr = currentYear;
    }
});

const isRequestOnlyProperty = computed(() => Boolean(selectedPropertyType.value?.request_only));

const isPriceOnRequestSelection = computed(() => {
    const property = selectedPropertyType.value;
    if (!property) {
        return false;
    }

    return property.price_standard_eur == null;
});

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
    form.property_type_key = '';
    form.property_type_category = 'single';
    form.unit_count = null;
    form.gnd_override = '';
    form.baujahr = '';
    form.anschaffungsjahr = '';
    form.steuerjahr = '';
    form.bauweise = 'massiv';
    form.eigennutzung = null;
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
    currentStep.value = 1;
    activeRenovationInfo.value = null;
    clearErrors();
};

const goToNextStep = () => {
    clearErrors();
    
    if (currentStep.value === 1) {
        // Validierung für Schritt 1
        if (!form.property_type_key) {
            errors.property_type_key = ['Bitte wählen Sie eine Immobilienart aus.'];
            return;
        }
        // Bei MFH/WGH: Validierung der Wohnungsanzahl
        if ((form.property_type_category === 'mfh' || form.property_type_category === 'wgh') && !form.unit_count) {
            errors.unit_count = ['Bitte geben Sie die Anzahl der Wohneinheiten an.'];
            return;
        }
        if ((form.property_type_category === 'mfh' || form.property_type_category === 'wgh') && form.unit_count < 4) {
            errors.unit_count = ['Ein Mehrfamilienhaus muss mindestens 4 Wohneinheiten haben.'];
            return;
        }
        if (isRequestOnlyProperty.value) {
            errors.general = [
                `${requestOnlyHeadline.value}. ${requestOnlyMessage.value}`,
            ];
            return;
        }
        if (!form.baujahr) {
            errors.baujahr = ['Bitte geben Sie ein gültiges Baujahr ein.'];
            return;
        }
        if (!form.anschaffungsjahr) {
            errors.anschaffungsjahr = ['Bitte geben Sie ein gültiges Anschaffungsjahr ein.'];
            return;
        }
        if (Number(form.anschaffungsjahr) > currentYear) {
            errors.anschaffungsjahr = ['Das Anschaffungsjahr darf nicht in der Zukunft liegen.'];
            return;
        }
        if (!form.steuerjahr) {
            errors.steuerjahr = ['Bitte geben Sie ein gültiges Steuerjahr ein.'];
            return;
        }
        if (Number(form.steuerjahr) > currentYear) {
            errors.steuerjahr = ['Das Steuerjahr darf nicht in der Zukunft liegen.'];
            return;
        }
        if (form.eigennutzung === null) {
            errors.eigennutzung = ['Bitte geben Sie an, ob die Immobilie vermietet wird.'];
            return;
        }
        if (form.eigennutzung === true) {
            errors.eigennutzung = ['Unsere Ersteinschätzung steht nur für vermietete Immobilien zur Verfügung. Bitte kontaktieren Sie uns persönlich.'];
            return;
        }
    }
    
    if (currentStep.value === 2) {
        // Validierung für Schritt 2 (Sanierungen) - keine spezifische Validierung nötig
        // Sanierungen sind optional
    }
    
    if (currentStep.value < 3) {
        currentStep.value++;
        // Nach oben scrollen
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const goToPreviousStep = () => {
    clearErrors();
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
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

const NEGATIVE_RECOMMENDATION = 'Ein Gutachten ist für Sie allein auf Grundlage dieser Abfrage nicht sinnvoll. Kontaktieren Sie uns gerne und wir prüfen für Sie, ob es Möglichkeiten für eine verkürzte Restnutzungsdauer gibt.';

const recommendationText = computed(() => result.value?.recommendation ?? null);

const recommendationIsNegative = computed(() => recommendationText.value === NEGATIVE_RECOMMENDATION);

const validationMessageMap = {
    property_type_key: 'Bitte wählen Sie eine Immobilienart aus.',
    gnd_override: 'Die optionale Gesamtnutzungsdauer muss zwischen 1 und 200 Jahren liegen.',
    baujahr: 'Bitte geben Sie ein gültiges Baujahr ein.',
    anschaffungsjahr: 'Bitte geben Sie ein gültiges Anschaffungsjahr ein (mindestens Baujahr).',
    steuerjahr: 'Bitte geben Sie ein gültiges Steuerjahr ein (mindestens Baujahr).',
    bauweise: 'Bitte wählen Sie eine gültige Bauweise aus.',
    eigennutzung: 'Bitte bestätigen Sie, ob die Immobilie vermietet wird.',
    'contact.email': 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'contact.phone': 'Bitte geben Sie eine gültige Telefonnummer ein.',
    'contact.name': 'Bitte geben Sie einen Namen mit höchstens 255 Zeichen ein.',
    notes: 'Die Notiz darf höchstens 1000 Zeichen enthalten.',
    'renovations.*': 'Bitte prüfen Sie die Angaben zu den Sanierungsmaßnahmen.',
    'address.street': 'Die Straße darf höchstens 255 Zeichen enthalten.',
    'address.zip': 'Die Postleitzahl muss genau 5 Ziffern enthalten.',
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

    const ensureStepThreeVisible = () => {
        currentStep.value = 3;
        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    if (!form.address.street || form.address.street.trim() === '') {
        errors['address.street'] = ['Bitte geben Sie eine Straße ein.'];
        ensureStepThreeVisible();
        return;
    }
    if (!form.address.zip || form.address.zip.trim() === '') {
        errors['address.zip'] = ['Bitte geben Sie eine Postleitzahl ein.'];
        ensureStepThreeVisible();
        return;
    }
    if (!/^\d{5}$/.test(form.address.zip.trim())) {
        errors['address.zip'] = ['Die Postleitzahl muss genau 5 Ziffern enthalten.'];
        ensureStepThreeVisible();
        return;
    }
    if (!form.address.city || form.address.city.trim() === '') {
        errors['address.city'] = ['Bitte geben Sie einen Ort ein.'];
        ensureStepThreeVisible();
        return;
    }

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
        billing_address: {
            street: form.billing_address.street || null,
            zip: form.billing_address.zip || null,
            city: form.billing_address.city || null,
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

            await nextTick();

            if (typeof window !== 'undefined' && typeof document !== 'undefined') {
                const target = document.getElementById('recommendation-negative-box');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
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
        title="EVALIO Nutzungsdauer – Ersteinschätzung"
        :user="$page.props.auth?.user"
        :can-login="canLogin"
        :can-register="canRegister"
    >
    <section v-if="currentStep === 1" class="relative overflow-hidden bg-gradient-to-br from-slate-800 via-slate-900 to-black py-8 text-white sm:py-12 md:py-16">
            <!-- Dekorative Elemente -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -left-4 top-0 h-72 w-72 rounded-full bg-[#d9bf8c]/20 blur-3xl"></div>
                <div class="absolute -right-4 bottom-0 h-96 w-96 rounded-full bg-[#d9bf8c]/20 blur-3xl"></div>
                <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#d9bf8c]/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto flex max-w-5xl flex-col gap-5 px-4 text-center sm:gap-6 md:gap-8">
                <!-- Badge -->
                <div class="mx-auto inline-flex items-center gap-1.5 rounded-full bg-[#d9bf8c]/10 px-3 py-1.5 backdrop-blur-sm ring-1 ring-[#d9bf8c]/30 sm:gap-2 sm:px-4 sm:py-2">
                    <svg class="h-4 w-4 text-[#d9bf8c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold uppercase tracking-wide text-[#d9bf8c]">
                        Professionelle Immobilienbewertung
                    </span>
                </div>

                <!-- Hauptüberschrift -->
                <h1 class="text-3xl font-extrabold leading-tight sm:text-4xl md:text-6xl lg:text-7xl">
                    <span class="block">Restnutzungsdauer &</span>
                    <span class="block bg-gradient-to-r from-[#d9bf8c] to-[#c4a875] bg-clip-text text-transparent">
                        AfA-Satz berechnen
                    </span>
                </h1>

                <!-- Beschreibung -->
                <p class="mx-auto max-w-3xl text-base leading-relaxed text-slate-300 sm:text-lg md:text-xl">
                    Ermitteln Sie in wenigen Minuten die Restnutzungsdauer (RND) und den AfA-Satz Ihrer Immobilie.
                    Erfassen Sie Objektdaten, bewerten Sie durchgeführte Sanierungen und erhalten Sie eine fundierte
                    Empfehlung – schnell, präzise und direkt online.
                </p>

                <!-- Feature-Liste -->
                <div class="mx-auto mt-3 grid max-w-2xl gap-3 sm:mt-4 sm:grid-cols-3 sm:gap-4">
                    <div class="flex flex-col items-center gap-1.5 rounded-xl bg-white/5 p-3 backdrop-blur-sm ring-1 ring-[#d9bf8c]/20 sm:gap-2 sm:p-4">
                        <svg class="hidden h-8 w-8 text-[#d9bf8c] sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span class="text-sm font-semibold text-white">Sofortige Berechnung</span>
                    </div>
                    <div class="flex flex-col items-center gap-1.5 rounded-xl bg-white/5 p-3 backdrop-blur-sm ring-1 ring-[#d9bf8c]/20 sm:gap-2 sm:p-4">
                        <svg class="hidden h-8 w-8 text-[#d9bf8c] sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="text-sm font-semibold text-white">Rechtssicher</span>
                    </div>
                    <div class="flex flex-col items-center gap-1.5 rounded-xl bg-white/5 p-3 backdrop-blur-sm ring-1 ring-[#d9bf8c]/20 sm:gap-2 sm:p-4">
                        <svg class="hidden h-8 w-8 text-[#d9bf8c] sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="text-sm font-semibold text-white">Detaillierte Auswertung</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-gradient-to-b from-slate-50 to-white py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="w-full space-y-6">
                 

                    <!-- Fortschrittsanzeige -->
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                        <!-- Desktop Version -->
                        <div class="hidden items-center justify-between md:flex">
                            <div class="flex flex-1 items-center">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
                                        :class="currentStep >= 1 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                    >
                                        1
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <div class="font-semibold" :class="currentStep >= 1 ? 'text-slate-900' : 'text-slate-500'">
                                            Objektinformationen
                                        </div>
                                        <div class="text-xs text-slate-500">Immobilienart & Adresse</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mx-4 h-px flex-1 bg-slate-300"></div>
                            <div class="flex flex-1 items-center">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
                                        :class="currentStep >= 2 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                    >
                                        2
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <div class="font-semibold" :class="currentStep >= 2 ? 'text-slate-900' : 'text-slate-500'">
                                            Zustandsbewertung
                                        </div>
                                        <div class="text-xs text-slate-500">Sanierungen</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mx-4 h-px flex-1 bg-slate-300"></div>
                            <div class="flex flex-1 items-center">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
                                        :class="currentStep >= 3 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                    >
                                        3
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <div class="font-semibold" :class="currentStep >= 3 ? 'text-slate-900' : 'text-slate-500'">
                                            Kontaktdaten
                                        </div>
                                        <div class="text-xs text-slate-500">Abschluss</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile Version -->
                        <div class="flex items-center justify-between gap-1 md:hidden">
                            <!-- Schritt 1 -->
                            <div class="flex flex-1 flex-col items-center px-1">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold sm:h-10 sm:w-10"
                                    :class="currentStep >= 1 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                >
                                    1
                                </div>
                                <div class="mt-1.5 text-center">
                                    <div class="text-[11px] font-semibold leading-tight sm:text-xs" :class="currentStep >= 1 ? 'text-slate-900' : 'text-slate-500'">
                                        Objekt
                                    </div>
                                    <div class="text-[9px] leading-tight text-slate-500 sm:text-[10px]">Grunddaten</div>
                                </div>
                            </div>
                            
                            <!-- Verbindungslinie -->
                            <div class="h-px w-4 flex-shrink-0 bg-slate-300 sm:w-8"></div>
                            
                            <!-- Schritt 2 -->
                            <div class="flex flex-1 flex-col items-center px-1">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold sm:h-10 sm:w-10"
                                    :class="currentStep >= 2 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                >
                                    2
                                </div>
                                <div class="mt-1.5 text-center">
                                    <div class="text-[11px] font-semibold leading-tight sm:text-xs" :class="currentStep >= 2 ? 'text-slate-900' : 'text-slate-500'">
                                        Zustand
                                    </div>
                                    <div class="text-[9px] leading-tight text-slate-500 sm:text-[10px]">Sanierungen</div>
                                </div>
                            </div>
                            
                            <!-- Verbindungslinie -->
                            <div class="h-px w-4 flex-shrink-0 bg-slate-300 sm:w-8"></div>
                            
                            <!-- Schritt 3 -->
                            <div class="flex flex-1 flex-col items-center px-1">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold sm:h-10 sm:w-10"
                                    :class="currentStep >= 3 ? 'bg-[#d9bf8c] text-slate-900' : 'bg-slate-200 text-slate-500'"
                                >
                                    3
                                </div>
                                <div class="mt-1.5 text-center">
                                    <div class="text-[11px] font-semibold leading-tight sm:text-xs" :class="currentStep >= 3 ? 'text-slate-900' : 'text-slate-500'">
                                        Kontakt & Adresse
                                    </div>
                                    <div class="text-[9px] leading-tight text-slate-500 sm:text-[10px]">Abschluss</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form
                        @submit.prevent="submit"
                        class="space-y-8"
                        :aria-busy="submitting"
                    >
                        <!-- Schritt 1: Objektinformationen -->
                        <div v-show="currentStep === 1" class="space-y-6">
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="mb-6 text-lg font-semibold text-slate-900">
                                    Um welche Immobilienart handelt es sich? <span class="text-red-600">*</span>
                                </h3>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                    <button
                                        v-for="type in displayPropertyTypes"
                                        :key="type.key"
                                        type="button"
                                        @click="selectPropertyCategory(type.key)"
                                        class="group relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 p-4 transition-all hover:border-[#d9bf8c] hover:bg-[#d9bf8c]/5"
                                        :class="{
                                            'border-[#d9bf8c] bg-[#d9bf8c]/10 ring-2 ring-[#d9bf8c] ring-offset-2': 
                                                (type.category === 'single' && form.property_type_key === type.key) ||
                                                (type.category === 'multi' && form.property_type_category === type.key),
                                            'border-slate-200': 
                                                (type.category === 'single' && form.property_type_key !== type.key) &&
                                                (type.category === 'multi' && form.property_type_category !== type.key)
                                        }"
                                    >
                                        <!-- Icon aus public/images/icons -->
                                        <div class="flex h-24 w-24 items-center justify-center">
                                            <img 
                                                :src="`/images/icons/${type.key}.png`" 
                                                :alt="type.label"
                                                class="h-20 w-20 object-contain transition-opacity"
                                                :class="{ 
                                                    'opacity-100': 
                                                        (type.category === 'single' && form.property_type_key === type.key) ||
                                                        (type.category === 'multi' && form.property_type_category === type.key),
                                                    'opacity-60 group-hover:opacity-100':
                                                        (type.category === 'single' && form.property_type_key !== type.key) &&
                                                        (type.category === 'multi' && form.property_type_category !== type.key)
                                                }"
                                            />
                                        </div>
                                        <span class="text-center text-xs font-medium text-slate-700 transition-colors group-hover:text-slate-900"
                                              :class="{ 
                                                'text-slate-900 font-semibold': 
                                                    (type.category === 'single' && form.property_type_key === type.key) ||
                                                    (type.category === 'multi' && form.property_type_category === type.key)
                                              }">
                                            {{ type.label }}
                                        </span>
                                        <!-- Checkmark für ausgewählte Option -->
                                        <div v-if="(type.category === 'single' && form.property_type_key === type.key) || (type.category === 'multi' && form.property_type_category === type.key)" 
                                             class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-[#d9bf8c]">
                                            <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </button>
                                </div>

                                <!-- Wohnungsanzahl-Eingabe für MFH/WGH -->
                                <div v-if="form.property_type_category === 'mfh' || form.property_type_category === 'wgh'" 
                                     class="mt-6 rounded-xl border-2 border-[#d9bf8c] bg-[#d9bf8c]/5 p-5">
                                    <label class="block text-sm font-medium text-slate-900 mb-2">
                                        <span v-if="form.property_type_category === 'mfh'">Wie viele Wohneinheiten hat das Mehrfamilienhaus?</span>
                                        <span v-else>Wie viele Einheiten hat das Wohn- und Geschäftshaus?</span>
                                        <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.unit_count"
                                        type="number"
                                        min="4"
                                        required
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        :class="{ 'border-red-500': errors.unit_count?.length }"
                                        :placeholder="form.property_type_category === 'mfh' ? 'z. B. 8' : 'z. B. 6'"
                                    />
                                    <p v-if="errors.unit_count?.length" class="mt-1 text-xs text-red-600">
                                        {{ errors.unit_count[0] }}
                                    </p>
                                    <p v-else class="mt-2 text-xs text-slate-600">
                                        <span v-if="form.property_type_category === 'mfh'">
                                            Mehrfamilienhäuser haben mindestens 4 Wohneinheiten.
                                        </span>
                                        <span v-else>
                                            Bitte geben Sie die Gesamtzahl aller Wohn- und Gewerbeeinheiten an.
                                        </span>
                                    </p>
                                    <div v-if="form.unit_count && form.unit_count >= 4" class="mt-3 rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                                        <strong v-if="form.unit_count <= 10">✓ Online-Ersteinschätzung möglich</strong>
                                        <strong v-else>
                                            ℹ Für Objekte mit mehr als 10 Einheiten erstellen wir ein individuelles Angebot mit separater Preisabstimmung. Sie können die Anfrage trotzdem abschließen.
                                        </strong>
                                    </div>
                                </div>
                                
                                <div
                                    v-if="selectedPropertyType && isRequestOnlyProperty"
                                    class="mt-4 rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm"
                                >
                                    <span class="block text-base font-semibold text-slate-700">
                                        {{ requestOnlyHeadline }}
                                    </span>
                                    <span class="mt-2 block text-sm text-slate-600">
                                        {{ requestOnlyMessage }}
                                    </span>
                                    <span class="mt-4 block text-sm text-slate-600">
                                        Ansprechpartner: {{ supportName }} · Telefon:
                                        <a :href="`tel:${supportPhoneHref}`" class="font-medium underline decoration-slate-400 hover:text-slate-900">
                                            {{ supportPhoneDisplay }}
                                        </a>
                                        · E-Mail:
                                        <a :href="`mailto:${supportEmail}`" class="font-medium underline decoration-slate-400 hover:text-slate-900">
                                            {{ supportEmail }}
                                        </a>
                                    </span>
                                </div>

                                <div v-if="!isRequestOnlyProperty" class="mt-6 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Baujahr <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.baujahr"
                                            type="number"
                                            min="300"
                                            :max="currentYear"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            placeholder="z. B. 1980"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Bauweise</label>
                                        <select
                                            v-model="form.bauweise"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                        >
                                            <option value="fertig">Fertigbauweise</option>         
                                            <option value="massiv">Massivbauweise</option>                                            
                                            <option value="holz">Holzbauweise</option>
                                            <option value="unbekannt">Unbekannt</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Anschaffungsjahr <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.anschaffungsjahr"
                                            type="number"
                                            min="300"
                                            :max="currentYear"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            placeholder="z. B. 2020"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Steuerjahr <span class="text-red-600">*</span>
                                            <span class="relative inline-block ml-1">
                                                <button
                                                    type="button"
                                                    @mouseenter="showSteuerjahrTooltip = true"
                                                    @mouseleave="showSteuerjahrTooltip = false"
                                                    @focus="showSteuerjahrTooltip = true"
                                                    @blur="showSteuerjahrTooltip = false"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-white hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1 transition-colors"
                                                >
                                                    <font-awesome-icon icon="circle-info" class="h-2.5 w-2.5" />
                                                </button>
                                                <div
                                                    v-show="showSteuerjahrTooltip"
                                                    class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-64 px-3 py-2 text-xs text-white bg-slate-700 rounded-lg shadow-lg z-50 pointer-events-none"
                                                >
                                                    Das Jahr der letzten noch nicht abgegebenen Steuererklärung
                                                    <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-slate-700"></div>
                                                </div>
                                            </span>
                                        </label>
                                        <input
                                            v-model="form.steuerjahr"
                                            type="number"
                                            min="300"
                                            :max="currentYear"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            placeholder="z. B. 2025"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 mb-3">
                                            Wird die Immobilie vermietet? <span class="text-red-600">*</span>
                                        </label>
                                        <div class="flex gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    id="vermietet-ja"
                                                    v-model="form.eigennutzung"
                                                    type="radio"
                                                    :value="false"
                                                    class="h-4 w-4 border-slate-300 text-[#d9bf8c] focus:ring-[#d9bf8c]"
                                                />
                                                <span class="text-sm text-slate-700">Ja</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    id="vermietet-nein"
                                                    v-model="form.eigennutzung"
                                                    type="radio"
                                                    :value="true"
                                                    class="h-4 w-4 border-slate-300 text-[#d9bf8c] focus:ring-[#d9bf8c]"
                                                />
                                                <span class="text-sm text-slate-700">Nein</span>
                                            </label>
                                        </div>
                                        <!-- Hinweis bei Nicht-Vermietung -->
                                        <div v-if="form.eigennutzung === true" class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
                                            <div class="flex gap-2">
                                                <svg class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div>
                                                    <strong>Wichtiger Hinweis:</strong> Eine Abschreibung ist nur bei Immobilien möglich, die der Erzielung von Einkünften dienen (z. B. Vermietung, gewerbliche Nutzung). Bei privater Eigennutzung oder unentgeltlicher Überlassung ist keine steuerliche Abschreibung möglich.
                                                </div>
                                            </div>
                                        </div>
                                        <p v-if="errors.eigennutzung?.length" class="mt-2 text-xs text-red-600">
                                            {{ errors.eigennutzung[0] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schritt 2: Sanierungen & Zustandsbewertung -->
                        <div v-show="currentStep === 2" class="space-y-6">
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
                                        class="rounded-xl border border-slate-200 p-4 sm:p-6"
                                    >
                                        <div class="mb-4 flex items-start justify-between gap-3">
                                            <h4 class="text-sm font-semibold text-slate-900 leading-snug">
                                                {{ renovationDisplayConfig[category.key]?.question ?? category.label }}
                                            </h4>
                                            <span
                                                v-if="renovationDisplayConfig[category.key]?.info"
                                                class="relative inline-block"
                                            >
                                                <button
                                                    type="button"
                                                    @mouseenter="activeRenovationInfo = category.key"
                                                    @mouseleave="activeRenovationInfo = null"
                                                    @focus="activeRenovationInfo = category.key"
                                                    @blur="activeRenovationInfo = null"
                                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-slate-600 hover:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-1 transition-colors"
                                                    :aria-label="`Weitere Informationen zu ${renovationDisplayConfig[category.key]?.question ?? category.label}`"
                                                >
                                                    <font-awesome-icon icon="circle-info" class="h-3 w-3" />
                                                </button>
                                                <div
                                                    v-show="activeRenovationInfo === category.key"
                                                    class="absolute right-0 top-full z-50 mt-2 w-64 rounded-lg bg-slate-900 px-3 py-2 text-xs text-white shadow-lg"
                                                    style="white-space: pre-line;"
                                                >
                                                    {{ renovationDisplayConfig[category.key].info }}
                                                </div>
                                            </span>
                                        </div>

                                        <!-- Zeitpunkt als anklickbare Buttons -->
                                        <div class="space-y-3">
                                            <label class="block text-xs font-medium text-slate-700">
                                                Zeitpunkt <span class="text-red-600">*</span>
                                            </label>
                                            <div class="grid grid-cols-1 gap-2">
                                                <button
                                                    v-for="option in timeWindowOptionsOrdered"
                                                    :key="option.key"
                                                    type="button"
                                                    @click="form.renovations[index].time_window_key = option.key; form.renovations[index].extent_percent = option.needsExtent ? (form.renovations[index].extent_percent || 20) : 0"
                                                    class="rounded-lg border-2 px-3 py-2 text-left text-xs font-medium transition-all"
                                                    :class="{
                                                        'border-[#d9bf8c] bg-[#d9bf8c]/10 text-slate-900': form.renovations[index].time_window_key === option.key,
                                                        'border-slate-200 bg-white text-slate-600 hover:border-[#d9bf8c]/50 hover:bg-slate-50': form.renovations[index].time_window_key !== option.key
                                                    }"
                                                >
                                                    {{ option.label }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Umfang (nur bei relevanten Zeitfenstern) -->
                                        <div v-if="needsExtentInput(form.renovations[index].time_window_key)" class="mt-4 space-y-3">
                                            <label class="block text-xs font-medium text-slate-700">
                                                Umfang
                                            </label>
                                            <div class="rounded-lg bg-[#d9bf8c]/10 px-4 py-2 text-center text-sm font-semibold text-slate-900">
                                                {{ getExtentLabel(form.renovations[index].extent_percent) }}
                                            </div>
                                            <input
                                                type="range"
                                                v-model.number="form.renovations[index].extent_percent"
                                                :min="20"
                                                :max="100"
                                                :step="20"
                                                class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#d9bf8c] px-0"
                                            />
                                            <div class="flex justify-between text-xs text-slate-500">
                                                <span class="text-center leading-tight">20 %</span>
                                                <span class="text-center leading-tight">40 %</span>
                                                <span class="text-center leading-tight">60 %</span>
                                                <span class="text-center leading-tight">80 %</span>
                                                <span class="text-center leading-tight">100 %</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schritt 3: Kontakt, Adresse & Datenschutz -->
                        <div v-show="currentStep === 3" class="space-y-6">
                            <div
                                v-if="!isRequestOnlyProperty"
                                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                            >
                                <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                    Ihre Kontaktdaten
                                </h3>
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="contact-name">
                                            Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="contact-name"
                                            v-model.trim="form.contact.name"
                                            type="text"
                                            required
                                            autocomplete="name"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            placeholder="Max Mustermann"
                                        />
                                        <p
                                            v-if="errors['contact.name']?.length"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ errors['contact.name'][0] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="contact-email">
                                            E-Mail-Adresse <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            id="contact-email"
                                            v-model.trim="form.contact.email"
                                            type="email"
                                            required
                                            autocomplete="email"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            placeholder="name@example.de"
                                        />
                                        <p
                                            v-if="errors['contact.email']?.length"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ errors['contact.email'][0] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="contact-phone">
                                            Telefonnummer (optional)
                                        </label>
                                        <input
                                            id="contact-phone"
                                            v-model.trim="form.contact.phone"
                                            type="tel"
                                            inputmode="tel"
                                            autocomplete="tel"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
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
                                    <span v-if="isPriceOnRequestSelection">
                                        Sie erhalten die Berechnung direkt per E-Mail. Den finalen Angebotspreis stimmen wir danach individuell mit Ihnen ab und melden uns ebenfalls per E-Mail.
                                    </span>
                                    <span v-else>
                                        Wir senden Ihnen das Ergebnis sowie ein individuelles Angebot unmittelbar per E-Mail zu.
                                    </span>
                                    Die Telefonnummer hilft uns bei Rückfragen, bleibt jedoch freiwillig.
                                </p>
                            </div>

                            <div
                                v-if="!isRequestOnlyProperty"
                                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                            >
                                <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                    Objektadresse
                                </h3>
                                <p class="mb-4 text-xs text-slate-500">
                                    Hinweis: Diese Angaben beziehen sich auf die Adresse der Immobilie (Objektadresse).
                                </p>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700">
                                            Straße <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.address.street"
                                            type="text"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            :class="{ 'border-red-500': errors['address.street']?.length }"
                                            placeholder="z. B. Musterstraße 123"
                                        />
                                        <p
                                            v-if="errors['address.street']?.length"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ errors['address.street'][0] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            PLZ <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.address.zip"
                                            type="text"
                                            required
                                            maxlength="5"
                                            pattern="\d{5}"
                                            inputmode="numeric"
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            :class="{ 'border-red-500': errors['address.zip']?.length }"
                                            placeholder="z. B. 12345"
                                        />
                                        <p
                                            v-if="errors['address.zip']?.length"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ errors['address.zip'][0] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Ort <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            v-model="form.address.city"
                                            type="text"
                                            required
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#d9bf8c] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c]"
                                            :class="{ 'border-red-500': errors['address.city']?.length }"
                                            placeholder="z. B. Berlin"
                                        />
                                        <p
                                            v-if="errors['address.city']?.length"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ errors['address.city'][0] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="!isRequestOnlyProperty"
                                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                            >
                                <h3 class="mb-4 text-lg font-semibold text-slate-900">
                                    Datenschutz & Zustimmung
                                </h3>
                                <p class="text-sm font-medium text-slate-700">
                                    Sofortiges Ergebnis · 100 % kostenlos & unverbindlich
                                </p>
                                <br></br>
                                <div class="space-y-4 text-sm text-slate-700">
                                    <label class="flex items-start gap-3" for="accept-terms">
                                        <input
                                            id="accept-terms"
                                            v-model="form.acceptTerms"
                                            type="checkbox"
                                            required
                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-[#d9bf8c] focus:ring-[#d9bf8c]"
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
                                                <a href="https://evalio-nutzungsdauer.de/wp-content/uploads/2025/11/Datenschutz.pdf" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">
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
                                        <a href="https://evalio-nutzungsdauer.de/wp-content/uploads/2025/11/EVALIO_AGB´s.pdf" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">AGB</a>
                                        und
                                        <a href="https://evalio-nutzungsdauer.de/wp-content/uploads/2025/11/Datenschutz.pdf" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">Datenschutzerklärung</a>
                                        habe ich zur Kenntnis genommen.
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        <span class="font-semibold">*</span> Pflichtfeld
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="recommendationIsNegative"
                            id="recommendation-negative-box"
                            data-testid="recommendation-negative-box"
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-slate-800 shadow-sm"
                        >
                            <div class="flex flex-col gap-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">
                                        Gutachten aktuell nicht sinnvoll
                                    </h3>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                        {{ recommendationText ?? 'Unsere Berechnung zeigt, dass ein Gutachten aktuell nicht sinnvoll ist.' }}
                                    </p>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                        Wir beraten Sie gerne persönlich und finden gemeinsam den passenden Weg für Ihre Immobilie.
                                    </p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-white/80 p-4 text-sm">
                                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6m-18 0l9 6 9-6M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-slate-900">Kontakt per E-Mail</div>
                                            <a :href="`mailto:${supportEmail}`" class="text-blue-700 hover:underline">
                                                {{ supportEmail }}
                                            </a>
                                        </div>
                                    </div>

                                    <div
                                        v-if="supportPhoneDisplay"
                                        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-white/80 p-4 text-sm"
                                    >
                                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h1.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-1.257.63a11.042 11.042 0 005.516 5.516l.63-1.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-slate-900">Telefonische Beratung</div>
                                            <a :href="`tel:${supportPhoneHref}`" class="text-blue-700 hover:underline">
                                                {{ supportPhoneDisplay }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-500">
                                    Ihr persönlicher Ansprechpartner: {{ supportName }}
                                </p>
                            </div>
                        </div>

                        <div v-if="validationMessages.length" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <h4 class="font-semibold">Formularfehler</h4>
                            <ul class="mt-2 list-disc pl-5">
                                <li v-for="(msg, idx) in validationMessages" :key="idx">{{ msg }}</li>
                            </ul>
                        </div>

                        <!-- Navigations-Buttons -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Zurück-Button (ab Schritt 2) -->
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="goToPreviousStep"
                                :disabled="submitting"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-6 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Zurück
                            </button>

                            <!-- Weiter-Button (Schritt 1 & 2) -->
                            <button
                                v-if="currentStep < 3"
                                type="button"
                                @click="goToNextStep"
                                :disabled="submitting || isRequestOnlyProperty"
                                class="inline-flex items-center justify-center rounded-lg bg-[#d9bf8c] px-6 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40 disabled:hover:bg-[#d9bf8c]/40"
                            >
                                Weiter
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <!-- Berechnen-Button (nur Schritt 3) -->
                            <button
                                v-if="currentStep === 3"
                                type="submit"
                                :disabled="submitting || isRequestOnlyProperty || !form.acceptTerms || !form.property_type_key"
                                class="inline-flex items-center justify-center rounded-lg bg-[#d9bf8c] px-6 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-[#c4a875] focus:outline-none focus:ring-2 focus:ring-[#d9bf8c] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#d9bf8c]/40 disabled:hover:bg-[#d9bf8c]/40"
                            >
                                <svg
                                    v-if="submitting"
                                    class="-ml-1 mr-2 h-4 w-4 animate-spin text-slate-900"
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

                        <!-- Hinweis zu Pflichtfeldern -->
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold">*</span> = Pflichtangaben
                        </p>
                    </form>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
