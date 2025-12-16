<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminAppLayout from '@/layouts/AdminAppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { adminRoutes } from '@/lib/routes';
import { CheckCircle2 } from 'lucide-vue-next';
import AdminSettingsNav from '@/components/admin/settings/AdminSettingsNav.vue';

interface Props {
    status?: string;
}

const props = defineProps<Props>();
const user = usePage().props.auth.user || { name: '', email: '' };

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    // Gebruik de statische route uit adminRoutes (geen route helper nodig)
    form.patch(adminRoutes.settingsProfile, {
        preserveScroll: true,
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: adminRoutes.dashboard },
    { title: 'Instellingen', href: adminRoutes.settings },
    { title: 'Profiel', href: adminRoutes.settingsProfile },
];
</script>

<template>
    <Head title="Profiel Instellingen" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl px-4 py-8">
            <h1 class="mb-4 text-3xl font-bold tracking-tight">Instellingen</h1>
            <p class="mb-8 text-muted-foreground">Beheer je accountinstellingen en voorkeuren.</p>

            <div class="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
                <aside class="lg:w-1/5">
                    <AdminSettingsNav />
                </aside>
                
                <div class="flex-1 max-w-4xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profiel Informatie</CardTitle>
                            <CardDescription>
                                Update je account profiel informatie en e-mailadres.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <div class="grid gap-2">
                                    <Label for="name">Naam</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        autocomplete="name"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="text-sm text-red-500">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email">E-mailadres</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="username"
                                        required
                                    />
                                    <div v-if="form.errors.email" class="text-sm text-red-500">
                                        {{ form.errors.email }}
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
