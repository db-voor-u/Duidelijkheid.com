<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminAppLayout from '@/layouts/AdminAppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { adminRoutes } from '@/lib/routes';
import AdminSettingsNav from '@/components/admin/settings/AdminSettingsNav.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: adminRoutes.dashboard },
    { title: 'Instellingen', href: adminRoutes.settings },
    { title: 'Uiterlijk', href: adminRoutes.settingsAppearance },
];
</script>

<template>
    <Head title="Uiterlijk Instellingen" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl px-4 py-8">
            <h1 class="mb-4 text-3xl font-bold tracking-tight">Instellingen</h1>
            <p class="mb-8 text-muted-foreground">Beheer de weergave van de applicatie.</p>

            <div class="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
                <aside class="lg:w-1/5">
                    <AdminSettingsNav />
                </aside>
                
                <div class="flex-1 max-w-4xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>Thema</CardTitle>
                            <CardDescription>
                                Kies tussen een licht of donker thema, of volg de systeeminstellingen.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <AppearanceTabs />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminAppLayout>
</template>
