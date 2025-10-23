<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    discountCodes: {
        type: Array,
        default: () => [],
    },
});

const toUpperCase = (value) => (value ?? '').toString().toUpperCase();

const createDefaults = () => ({
    code: '',
    label: '',
    percent: 10,
    is_active: true,
});

const newCode = reactive(createDefaults());
const createProcessing = ref(false);
const codes = ref(props.discountCodes.map((code) => ({ ...code })));

watch(
    () => props.discountCodes,
    (value) => {
        codes.value = value.map((code) => ({ ...code }));
    },
    { deep: true }
);

const flash = computed(() => usePage().props.flash ?? {});
const errors = computed(() => usePage().props.errors ?? {});

const processing = reactive({});
const deleting = reactive({});

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

const sanitizePercent = (value) => {
    const number = Number(value);
    if (Number.isNaN(number)) {
        return null;
    }

    return Math.max(0, Math.min(100, Math.round(number)));
};

const resetNewCode = () => {
    Object.assign(newCode, createDefaults());
};

const createDiscountCode = () => {
    createProcessing.value = true;

    router.post(route('admin.discount-codes.store'), {
        code: toUpperCase(newCode.code),
        label: newCode.label || null,
        percent: sanitizePercent(newCode.percent),
        is_active: Boolean(newCode.is_active),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            resetNewCode();
        },
        onFinish: () => {
            createProcessing.value = false;
        },
    });
};

const updateDiscountCode = (code) => {
    processing[code.id] = true;

    router.put(route('admin.discount-codes.update', code.id), {
        code: toUpperCase(code.code),
        label: code.label || null,
        percent: sanitizePercent(code.percent),
        is_active: Boolean(code.is_active),
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing[code.id] = false;
        },
    });
};

const deleteDiscountCode = (code) => {
    if (! window.confirm(`Soll der Rabattcode "${code.code}" wirklich gelöscht werden?`)) {
        return;
    }

    deleting[code.id] = true;

    router.delete(route('admin.discount-codes.destroy', code.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting[code.id] = false;
        },
    });
};
</script>

<template>
    <Head title="Admin · Rabattcodes" />

    <AdminLayout title="Admin · Rabattcodes">
        <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900">Rabattcodes verwalten</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Legen Sie Rabattcodes an, um Prozent-Rabatte auf Angebote zu gewähren.
                </p>
                <p v-if="flash?.success" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ flash.success }}
                </p>
                <p v-if="flash?.error" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </p>
            </div>

            <div class="mb-10 rounded-3xl border border-dashed border-indigo-200 bg-indigo-50/60 p-8 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Neuen Rabattcode anlegen</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Codes dürfen Buchstaben, Zahlen, Bindestrich und Unterstrich enthalten.
                </p>

                <form class="mt-6 grid gap-4 lg:grid-cols-6" @submit.prevent="createDiscountCode">
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-code-code">Code *</label>
                        <input
                            id="new-code-code"
                            v-model="newCode.code"
                            type="text"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.code ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                            placeholder="z. B. WELCOME10"
                            required
                        />
                        <p v-if="errors.code" class="mt-1 text-xs text-red-600">{{ errors.code }}</p>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-code-label">Beschreibung</label>
                        <input
                            id="new-code-label"
                            v-model="newCode.label"
                            type="text"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.label ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                            placeholder="z. B. Neukundenaktion"
                        />
                        <p v-if="errors.label" class="mt-1 text-xs text-red-600">{{ errors.label }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-code-percent">Rabatt % *</label>
                        <input
                            id="new-code-percent"
                            v-model.number="newCode.percent"
                            type="number"
                            min="1"
                            max="100"
                            class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="errors.percent ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-200'"
                            required
                        />
                        <p v-if="errors.percent" class="mt-1 text-xs text-red-600">{{ errors.percent }}</p>
                    </div>
                    <div class="flex items-center gap-3 lg:col-span-1">
                        <input
                            id="new-code-active"
                            v-model="newCode.is_active"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="new-code-active">
                            Aktiv
                        </label>
                    </div>
                    <div class="lg:col-span-6 flex items-center justify-end gap-3 border-t border-indigo-100 pt-4">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900"
                            @click="resetNewCode"
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
                </form>
            </div>

            <div class="space-y-6">
                <div
                    v-for="code in codes"
                    :key="code.id"
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-semibold text-slate-900">
                                {{ code.code }}
                                <span v-if="code.label" class="ml-2 text-sm font-normal text-slate-500">({{ code.label }})</span>
                            </p>
                            <p class="text-xs text-slate-400">Zuletzt aktualisiert: {{ formatDateTime(code.updated_at) }}</p>
                            <p class="text-xs text-slate-400">Angelegt: {{ formatDateTime(code.created_at) }}</p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600" :for="`active-${code.id}`">
                            <input
                                :id="`active-${code.id}`"
                                v-model="code.is_active"
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Aktiv
                        </label>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600" :for="`code-${code.id}`">Code</label>
                            <input
                                :id="`code-${code.id}`"
                                v-model="code.code"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" :for="`label-${code.id}`">Beschreibung</label>
                            <input
                                :id="`label-${code.id}`"
                                v-model="code.label"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" :for="`percent-${code.id}`">Rabatt %</label>
                            <input
                                :id="`percent-${code.id}`"
                                v-model.number="code.percent"
                                type="number"
                                min="1"
                                max="100"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-red-100 disabled:text-red-400"
                            :disabled="processing[code.id] || deleting[code.id]"
                            @click="deleteDiscountCode(code)"
                        >
                            <svg
                                v-if="deleting[code.id]"
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
                            :disabled="processing[code.id] || deleting[code.id]"
                            @click="updateDiscountCode(code)"
                        >
                            <svg
                                v-if="processing[code.id]"
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

                <div v-if="!codes.length" class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                    Keine Rabattcodes vorhanden.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
