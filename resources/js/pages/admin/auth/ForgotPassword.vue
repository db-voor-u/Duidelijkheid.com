<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue'; // Or separate AdminAuthLayout if preferred, but AuthLayout is likely generic enough or we use a simple Card layout like AdminLogin
import { useForm, Head } from '@inertiajs/vue3';
import { adminRoutes } from '@/lib/routes';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'; // Using Card components to match AdminLogin.vue

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/hoofdbeheerder/forgot-password');
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
        <Head title="Wachtwoord vergeten - Hoofdbeheerder" />

        <Card class="w-full max-w-md">
            <CardHeader class="space-y-1">
                <CardTitle class="text-2xl font-bold text-center">Wachtwoord vergeten</CardTitle>
                <CardDescription class="text-center">
                    Vul je e-mailadres in en we sturen je een link om je wachtwoord opnieuw in te stellen.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600 text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="email">E-mailadres</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <Button class="w-full" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Verstuur reset link
                    </Button>
                    
                    <div class="text-center text-sm text-muted-foreground mt-4">
                        <a :href="adminRoutes.login" class="underline hover:text-primary">
                            Terug naar inloggen
                        </a>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
