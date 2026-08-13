<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy';
import AdminUserRow from '../../Components/AdminUserRow.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';
import { generatePassword } from '../../lib/generatePassword';

const props = defineProps({
    users: { type: Array, required: true },
    roles: { type: Array, required: true },
});

const { t } = useTranslations();

const roleLabels = {
    viewer: 'Viewer',
    contributor: 'Contributor',
    approver: 'Approver',
    admin: 'Admin role',
};

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: props.roles[0] ?? 'viewer',
});

function createUser() {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name', 'email', 'password'),
    });
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('Users') }}</h1>

        <form class="mb-6 grid grid-cols-1 gap-3 rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm sm:grid-cols-5 sm:items-end" @submit.prevent="createUser">
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Name') }}</label>
                <input
                    v-model="createForm.name"
                    type="text"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Email') }}</label>
                <input
                    v-model="createForm.email"
                    type="email"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="createForm.errors.email" class="mt-1 text-xs text-red-600">{{ createForm.errors.email }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Password') }}</label>
                <div class="flex gap-1">
                    <input
                        v-model="createForm.password"
                        type="text"
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    />
                    <button
                        type="button"
                        class="shrink-0 rounded-md border border-brand-gray/40 px-2 text-xs text-brand-navy transition-colors duration-150 hover:bg-slate-100"
                        @click="createForm.password = generatePassword()"
                    >
                        {{ t('Generate') }}
                    </button>
                </div>
                <p v-if="createForm.errors.password" class="mt-1 text-xs text-red-600">{{ createForm.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Role') }}</label>
                <select
                    v-model="createForm.role"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                >
                    <option v-for="role in roles" :key="role" :value="role">{{ t(roleLabels[role]) }}</option>
                </select>
            </div>

            <button
                type="submit"
                class="rounded-md bg-brand-blue px-4 py-1.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="createForm.processing"
            >
                {{ t('Add user') }}
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-brand-gray/20 bg-white shadow-sm">
            <table class="w-full text-start">
                <thead>
                    <tr class="border-b border-brand-gray/20 text-start text-xs font-medium text-brand-muted">
                        <th class="px-3 py-2 text-start">{{ t('Name') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Email') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Role') }}</th>
                        <th class="px-3 py-2 text-start"></th>
                        <th class="px-3 py-2 text-start">{{ t('Reset password') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <AdminUserRow v-for="user in users" :key="user.id" :user="user" :roles="roles" />
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
