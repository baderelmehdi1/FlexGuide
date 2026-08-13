<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '../../../vendor/tightenco/ziggy';
import { useTranslations } from '../i18n';
import { generatePassword } from '../lib/generatePassword';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
});

const { t } = useTranslations();

const roleLabels = {
    viewer: 'Viewer',
    contributor: 'Contributor',
    approver: 'Approver',
    admin: 'Admin role',
};

const form = useForm({
    role: props.user.role ?? 'viewer',
});

function save() {
    form.patch(route('admin.users.updateRole', props.user.id), { preserveScroll: true });
}

const passwordForm = useForm({
    password: '',
});

function resetPassword() {
    if (!passwordForm.password) {
        return;
    }

    passwordForm.patch(route('admin.users.resetPassword', props.user.id), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <tr class="border-b border-brand-gray/10 even:bg-slate-50">
        <td class="px-3 py-2 text-sm text-brand-navy">{{ user.name }}</td>
        <td class="px-3 py-2 text-sm text-brand-muted">{{ user.email }}</td>
        <td class="px-3 py-2">
            <select
                v-model="form.role"
                class="rounded-md border border-brand-gray/40 px-2 py-1 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
            >
                <option v-for="role in roles" :key="role" :value="role">{{ t(roleLabels[role]) }}</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <button
                type="button"
                class="rounded-md bg-brand-blue px-3 py-1 text-xs font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="form.processing || form.role === user.role"
                @click="save"
            >
                {{ t('Save') }}
            </button>
        </td>
        <td class="px-3 py-2">
            <div class="flex gap-1">
                <input
                    v-model="passwordForm.password"
                    type="text"
                    :placeholder="t('New password')"
                    class="w-36 rounded-md border border-brand-gray/40 px-2 py-1 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <button
                    type="button"
                    class="shrink-0 rounded-md border border-brand-gray/40 px-2 text-xs text-brand-navy transition-colors duration-150 hover:bg-slate-100"
                    @click="passwordForm.password = generatePassword()"
                >
                    {{ t('Generate') }}
                </button>
                <button
                    type="button"
                    class="shrink-0 rounded-md bg-brand-navy px-3 py-1 text-xs font-medium text-white transition-colors duration-150 hover:bg-brand-navy/90 active:scale-[0.98] disabled:opacity-60"
                    :disabled="passwordForm.processing || !passwordForm.password"
                    @click="resetPassword"
                >
                    {{ t('Reset password') }}
                </button>
            </div>
            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password }}</p>
        </td>
    </tr>
</template>
