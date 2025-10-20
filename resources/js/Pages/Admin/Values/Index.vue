<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    propertyTypes: {
        type: Array,
        default: () => [],
    },
});

const createDefaults = () => ({
    label: '',
    key: '',
    gnd: 80,
    price_standard_eur: null,
    request_only: false,
});

const newType = reactive(createDefaults());
const createProcessing = ref(false);
const types = ref(props.propertyTypes.map((type) => ({ ...type })));

watch(
    () => props.propertyTypes,
    (value) => {
        types.value = value.map((type) => ({ ...type }));
    },
    { deep: true }
);

const processing = reactive({});
const deleting = reactive({});

const flash = computed(() => usePage().props.flash ?? {});
const errors = computed(() => usePage().props.errors ?? {});

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

const sanitizeNumber = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);
    return Number.isNaN(number) ? null : number;
};

const updateType = (type) => {
    processing[type.id] = true;

    router.put(route('admin.property-types.update', type.id), {
        label: type.label,
        gnd: sanitizeNumber(type.gnd),
        price_standard_eur: sanitizeNumber(type.price_standard_eur),
        request_only: Boolean(type.request_only),
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing[type.id] = false;
        },
    });
};

const resetNewType = () => {
    Object.assign(newType, createDefaults());
};

const createType = () => {
    createProcessing.value = true;

    router.post(route('admin.property-types.store'), {
        label: newType.label,
        key: newType.key || null,
        gnd: sanitizeNumber(newType.gnd),
        price_standard_eur: sanitizeNumber(newType.price_standard_eur),
        request_only: Boolean(newType.request_only),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            resetNewType();
        },
        onFinish: () => {
            createProcessing.value = false;
        },
    });
};

const deleteType = (type) => {
    if (! window.confirm(`Soll die Immobilienart "${type.label}" wirklich gelöscht werden?`)) {
        return;
    }

    deleting[type.id] = true;

    router.delete(route('admin.property-types.destroy', type.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting[type.id] = false;
        },
    });
};
</script>

<template>
    <Head title="Admin · Werte" />

    <AdminLayout title="Admin · Werte">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900">Werte &amp; Immobilienarten</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Verwalten Sie Bezeichnungen, Gesamtnutzungsdauern und Standardpreise der Immobilienarten.
                </p>
                <p v-if="flash?.success" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ flash.success }}
                </p>
                <p v-if="flash?.error" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </p>
            </div>

            <div class="mb-10 rounded-3xl border border-dashed border-indigo-200 bg-indigo-50/60 p-8 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Neue Immobilienart anlegen</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Legen Sie zusätzliche Immobilienarten an, um weitere Angebote abzubilden. Der Schlüssel wird automatisch generiert, kann aber bei Bedarf überschrieben werden.
                </p>

                <form class="mt-6 grid gap-4 lg:grid-cols-6" @submit.prevent="createType">
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-type-label">Bezeichnung *</label>
                        <input
                            id="new-type-label"
                            v-model="newType.label"
                            type="text"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.label ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                            placeholder="z. B. Zweifamilienhaus"
                            required
                        />
                        <p v-if="errors.label" class="mt-1 text-xs text-red-600">{{ errors.label }}</p>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-type-key">Schlüssel</label>
                        <input
                            id="new-type-key"
                            v-model="newType.key"
                            type="text"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.key ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                            placeholder="z. B. zfh"
                        />
                        <p class="mt-1 text-xs text-slate-500">Nur Kleinbuchstaben, Zahlen, Bindestrich und Unterstrich.</p>
                        <p v-if="errors.key" class="mt-1 text-xs text-red-600">{{ errors.key }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-type-gnd">GND (Jahre)</label>
                        <input
                            id="new-type-gnd"
                            v-model.number="newType.gnd"
                            type="number"
                            min="0"
                            max="200"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.gnd ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                        />
                        <p v-if="errors.gnd" class="mt-1 text-xs text-red-600">{{ errors.gnd }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-type-price">Standardpreis (€)</label>
                        <input
                            id="new-type-price"
                            v-model.number="newType.price_standard_eur"
                            type="number"
                            min="0"
                            step="50"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.price_standard_eur ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                        />
                        <p v-if="errors.price_standard_eur" class="mt-1 text-xs text-red-600">{{ errors.price_standard_eur }}</p>
                    </div>
                    <div class="lg:col-span-6 flex flex-wrap items-center justify-between gap-3 border-t border-indigo-100 pt-4">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600" for="new-type-request-only">
                            <input
                                id="new-type-request-only"
                                v-model="newType.request_only"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Anfragepflichtig
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900"
                                @click="resetNewType"
                                :disabled="createProcessing"
                            >
                                Zurücksetzen
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-indigo-300"
                                :disabled="createProcessing"
                            >
                                <svg
                                    v-if="createProcessing"
                                    class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                Anlegen
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div
                    v-for="type in types"
                    :key="type.id"
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-semibold text-slate-900">{{ type.label }}</p>
                            <p class="text-xs text-slate-500">Schlüssel: {{ type.key }}</p>
                            <p class="mt-1 text-xs text-slate-400">Zuletzt aktualisiert: {{ formatDateTime(type.updated_at) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-slate-600" :for="`request-only-${type.id}`">
                                Anfragepflichtig
                            </label>
                            <input
                                :id="`request-only-${type.id}`"
                                v-model="type.request_only"
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-600" :for="`label-${type.id}`">Bezeichnung</label>
                            <input
                                :id="`label-${type.id}`"
                                v-model="type.label"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" :for="`gnd-${type.id}`">GND (Jahre)</label>
                            <input
                                :id="`gnd-${type.id}`"
                                v-model.number="type.gnd"
                                type="number"
                                min="0"
                                max="200"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" :for="`price-${type.id}`">Standardpreis (€)</label>
                            <input
                                :id="`price-${type.id}`"
                                v-model.number="type.price_standard_eur"
                                type="number"
                                min="0"
                                step="50"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-red-100 disabled:text-red-400"
                            :disabled="processing[type.id] || deleting[type.id]"
                            @click="deleteType(type)"
                        >
                            <svg
                                v-if="deleting[type.id]"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Löschen
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-500"
                            :disabled="processing[type.id] || deleting[type.id]"
                            @click="updateType(type)"
                        >
                            <svg
                                v-if="processing[type.id]"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Aktualisieren
                        </button>
                    </div>
                </div>

                <div v-if="!types.length" class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                    Keine Immobilienarten gefunden.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
