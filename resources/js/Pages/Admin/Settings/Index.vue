<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    contactSettings: {
        type: Array,
        default: () => [],
    },
});

const settings = ref(props.contactSettings.map((setting) => ({ ...setting })));

watch(
    () => props.contactSettings,
    (value) => {
        settings.value = value.map((setting) => ({ ...setting }));
    },
    { deep: true }
);

const page = usePage();

const accountForm = useForm({
    name: page.props.auth?.user?.name ?? '',
    email: page.props.auth?.user?.email ?? '',
});

watch(
    () => page.props.auth?.user,
    (user) => {
        accountForm.name = user?.name ?? '';
        accountForm.email = user?.email ?? '';
    }
);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const processing = reactive({});

const flash = computed(() => page.props.flash ?? {});

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

const updateSetting = (setting) => {
    processing[setting.id] = true;

    router.put(route('admin.contact-settings.update', setting.id), {
        value: setting.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing[setting.id] = false;
        },
    });
};

const updateAccount = () => {
    accountForm.put(route('admin.account.email.update'), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(route('admin.account.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset('current_password', 'password', 'password_confirmation');
        },
        onError: () => {
            passwordForm.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Admin · Einstellungen" />

    <AdminLayout title="Admin · Einstellungen">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900">Einstellungen</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Pflegen Sie allgemeine Kontaktinformationen und technische Parameter.
                </p>
                <p v-if="flash?.success" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ flash.success }}
                </p>
                <p v-if="flash?.error" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </p>
            </div>

            <div class="mb-10 grid gap-6 lg:grid-cols-2">
                <form
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    @submit.prevent="updateAccount"
                >
                    <h2 class="text-lg font-semibold text-slate-900">Zugangsdaten</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Aktualisieren Sie den Anzeigenamen und die E-Mail-Adresse des Admin-Kontos.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="admin-name" value="Anzeigename" />
                            <TextInput
                                id="admin-name"
                                v-model="accountForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                autocomplete="name"
                                placeholder="Evalio Admin"
                            />
                            <InputError class="mt-2" :message="accountForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="admin-email" value="E-Mail-Adresse" />
                            <TextInput
                                id="admin-email"
                                v-model="accountForm.email"
                                type="email"
                                class="mt-1 block w-full"
                                autocomplete="email"
                                required
                            />
                            <InputError class="mt-2" :message="accountForm.errors.email" />
                        </div>
                    </div>

                    <PrimaryButton
                        type="submit"
                        class="mt-6"
                        :class="{ 'opacity-50': accountForm.processing }"
                        :disabled="accountForm.processing"
                    >
                        Zugangsdaten speichern
                    </PrimaryButton>
                </form>

                <form
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    @submit.prevent="updatePassword"
                >
                    <h2 class="text-lg font-semibold text-slate-900">Passwort ändern</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Legen Sie ein neues Passwort für den Administrator fest.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="admin-current-password" value="Aktuelles Passwort" />
                            <TextInput
                                id="admin-current-password"
                                v-model="passwordForm.current_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                                required
                            />
                            <InputError class="mt-2" :message="passwordForm.errors.current_password" />
                        </div>

                        <div>
                            <InputLabel for="admin-password" value="Neues Passwort" />
                            <TextInput
                                id="admin-password"
                                v-model="passwordForm.password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                required
                            />
                            <InputError class="mt-2" :message="passwordForm.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="admin-password-confirmation" value="Passwort bestätigen" />
                            <TextInput
                                id="admin-password-confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                required
                            />
                        </div>
                    </div>

                    <PrimaryButton
                        type="submit"
                        class="mt-6"
                        :class="{ 'opacity-50': passwordForm.processing }"
                        :disabled="passwordForm.processing"
                    >
                        Passwort speichern
                    </PrimaryButton>
                </form>
            </div>

            <div class="divide-y divide-slate-200 rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div
                    v-for="setting in settings"
                    :key="setting.id"
                    class="flex flex-col gap-4 px-6 py-5 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ setting.label || setting.key }}</p>
                        <p class="text-xs text-slate-500">Schlüssel: {{ setting.key }} · Typ: {{ setting.type }}</p>
                        <p class="mt-1 text-xs text-slate-400">Zuletzt aktualisiert: {{ formatDateTime(setting.updated_at) }}</p>
                    </div>
                    <div class="flex w-full flex-col gap-3 md:w-1/2 md:flex-row md:items-center">
                        <input
                            v-model="setting.value"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-indigo-400"
                            :disabled="processing[setting.id]"
                            @click="updateSetting(setting)"
                        >
                            <svg
                                v-if="processing[setting.id]"
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
                </div>
                <div v-if="!settings.length" class="px-6 py-10 text-center text-sm text-slate-500">
                    Keine Einstellungen gefunden.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
