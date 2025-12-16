<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminAppLayout from '@/layouts/AdminAppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { adminRoutes } from '@/lib/routes';
import { CheckCircle2 } from 'lucide-vue-next';
import AdminSettingsNav from '@/components/admin/settings/AdminSettingsNav.vue';

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(adminRoutes.settingsPassword, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: adminRoutes.dashboard },
    { title: 'Instellingen', href: adminRoutes.settings },
    { title: 'Wachtwoord', href: adminRoutes.settingsPassword },
];
</script>

<template>
    <Head title="Wachtwoord Instellingen" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl px-4 py-8">
            <h1 class="mb-4 text-3xl font-bold tracking-tight">Instellingen</h1>
            <p class="mb-8 text-muted-foreground">Beheer je veiligheidsinstellingen.</p>

            <div class="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
                <aside class="lg:w-1/5">
                    <AdminSettingsNav />
                </aside>
                
                <div class="flex-1 max-w-4xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>Wachtwoord Wijzigen</CardTitle>
                            <CardDescription>
                                Zorg voor een sterk en veilig wachtwoord om je account te beschermen.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <div class="grid gap-2">
                                    <Label for="current_password">Huidig Wachtwoord</Label>
                                    <Input
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        v-model="form.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                    />
                                    <div v-if="form.errors.current_password" class="text-sm text-red-500">
                                        {{ form.errors.current_password }}
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="password">Nieuw Wachtwoord</Label>
                                    <Input
                                        id="password"
                                        ref="passwordInput"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                    />
                                    <div v-if="form.errors.password" class="text-sm text-red-500">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="password_confirmation">Bevestig Wachtwoord</Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="text-sm text-red-500">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <Button :disabled="form.processing">Opslaan</Button>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p v-if="form.recentlySuccessful" class="flex items-center text-sm text-green-600">
                                            <CheckCircle2 class="mr-1 h-4 w-4" /> Opgeslagen
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminAppLayout>
</template>
