<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const breadcrumbs = [
    { title: 'Dashboard', href: '/hoofdbeheerder/dashboard' },
    { title: 'Gebruikers', href: '/hoofdbeheerder/gebruikers' },
    { title: 'Aanmaken', href: '/hoofdbeheerder/gebruikers/aanmaken' },
]

const form = useForm({
    name: '',
    email: '',
    password: '',
})

const submit = () => {
    form.post('/hoofdbeheerder/gebruikers', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <Head title="Gebruiker aanmaken" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-8 mt-8 max-w-xl">
            <Card>
                <CardHeader>
                    <CardTitle>Maak een gebruiker aan</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="name">Naam</Label>
                            <Input v-model="form.name" id="name" required />
                            <span v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</span>
                        </div>
                        <div>
                            <Label for="email">E-mail</Label>
                            <Input v-model="form.email" id="email" type="email" required />
                            <span v-if="form.errors.email" class="text-red-500 text-xs">{{ form.errors.email }}</span>
                        </div>
                        <div>
                            <Label for="password">Wachtwoord</Label>
                            <Input v-model="form.password" id="password" type="password" required />
                            <span v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</span>
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            <span v-if="form.processing" class="mr-2 animate-spin">&#9696;</span>
                            Aanmaken
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
